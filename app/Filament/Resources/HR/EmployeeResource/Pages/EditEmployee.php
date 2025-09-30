<?php
namespace App\Filament\Resources\HR\EmployeeResource\Pages;

use App\Filament\Resources\HR\EmployeeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditEmployee extends EditRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {

            /** @var \App\Models\HR\Employee $record */

            if ($record->user) {
                $userUpdate = [
                    'name'  => ucwords(strtolower($data['full_name'])),
                    'email' => $data['email'] ?? $record->user->email,
                ];

                // Only update password if provided
                if (! empty($data['password'])) {
                    $userUpdate['password'] = Hash::make($data['password']);
                }

                $record->user->update($userUpdate);

                // Sync roles if provided
                if (isset($data['roles'])) {
                    // Get role names from the 'roles' table based on IDs
                    $roleNames = DB::table('roles')
                        ->whereIn('id', (array) $data['roles'])
                        ->pluck('name')
                        ->toArray();

                    $record->user->syncRoles($roleNames); // Sync roles for the user
                }
            }

            // --- Prepare Employee data ---
            $employeeData = [
                'full_name'           => ucwords(strtolower($data['full_name'])),
                'department_id'       => $data['department_id'],
                'position'            => $data['position'],
                'contract_type'       => $data['contract_type'] ?? $record->contract_type,
                'status'              => $data['status'] ?? $record->status,
                'phone_number'        => $data['phone_number'],
                'address'             => $data['address'] ?? null,
                'photo'               => $data['photo'] ?? $record->photo,
                // Face recognition fields (hidden)
                'face_embeddings'     => $data['face_embeddings'] ?? $record->face_embeddings,
                'face_embedding_path' => $data['face_embedding_path'] ?? $record->face_embedding_path,
                'face_landmarks'      => $data['face_landmarks'] ?? $record->face_landmarks,
            ];

            $record->update($employeeData);

            // Sync roles for employee model if roles provided
            if (isset($data['roles'])) {
                // Get role names from the 'roles' table based on IDs
                $roleNames = DB::table('roles')
                    ->whereIn('id', (array) $data['roles'])
                    ->pluck('name')
                    ->toArray();

                $record->syncRoles($roleNames); // Sync roles for the employee
            }

            return $record;
        });
    }
}
