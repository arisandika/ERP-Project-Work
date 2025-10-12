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

    public function getTitle(): string
    {
        return 'Edit Karyawan';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {

            /** @var \App\Models\HR\Employee $record */

            // Update data user terkait
            if ($record->user) {
                $userUpdate = [
                    'name'  => ucwords(strtolower($data['full_name'])),
                    'email' => $data['email'] ?? $record->user->email,
                ];

                // Hanya update password jika ada input baru
                if (! empty($data['password'])) {
                    $userUpdate['password'] = Hash::make($data['password']);
                }

                $record->user->update($userUpdate);

                // Sinkronisasi roles jika disertakan
                if (isset($data['roles'])) {
                    $roleNames = DB::table('roles')
                        ->whereIn('id', (array) $data['roles'])
                        ->pluck('name')
                        ->toArray();

                    $record->user->syncRoles($roleNames);
                }
            }

            // Siapkan data employee
            $employeeData = [
                'full_name'        => ucwords(strtolower($data['full_name'])),
                'email'            => $data['email'] ?? $record->email,
                'department_id'    => $data['department_id'] ?? null,
                'office_id'        => $data['office_id'] ?? null,
                'position'         => $data['position'] ?? $record->position,
                'contract_type'    => $data['contract_type'] ?? $record->contract_type,
                'status'           => $data['status'] ?? $record->status,
                'phone_number'     => $data['phone_number'],
                'address'          => $data['address'] ?? null,
                'photo'            => $data['photo'] ?? $record->photo,

                // Personal information
                'national_id'      => $data['national_id'] ?? $record->national_id,
                'identity_number'  => $data['identity_number'] ?? $record->identity_number,
                'birth_place'      => $data['birth_place'] ?? $record->birth_place,
                'birth_date'       => $data['birth_date'] ?? $record->birth_date,
                'gender'           => $data['gender'] ?? $record->gender,
                'marital_status'   => $data['marital_status'] ?? $record->marital_status,
                'education_level'  => $data['education_level'] ?? $record->education_level,
                'join_date'        => $data['join_date'] ?? $record->join_date,

                // Permission toggles
                'can_wfa'          => $data['can_wfa'] ?? $record->can_wfa,
                'can_unlock_shift' => $data['can_unlock_shift'] ?? $record->can_unlock_shift,

                // (Optional future fields)
                // 'face_embeddings'     => $data['face_embeddings'] ?? $record->face_embeddings,
                // 'face_embedding_path' => $data['face_embedding_path'] ?? $record->face_embedding_path,
                // 'face_landmarks'      => $data['face_landmarks'] ?? $record->face_landmarks,
            ];

            // Update data employee
            $record->update($employeeData);

            // Sinkronisasi roles untuk model employee
            if (isset($data['roles'])) {
                $roleNames = DB::table('roles')
                    ->whereIn('id', (array) $data['roles'])
                    ->pluck('name')
                    ->toArray();

                $record->syncRoles($roleNames);
            }

            return $record;
        });
    }
}
