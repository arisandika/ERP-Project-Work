<?php
namespace App\Filament\Resources\Sales\SalesPersonResource\Pages;

use App\Filament\Resources\Sales\SalesPersonResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EditSalesPerson extends EditRecord
{
    protected static string $resource = SalesPersonResource::class;

    public function getTitle(): string
    {
        return 'Edit PIC Sales';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['type'] ?? null) === 'internal' && ! empty($data['employee_id'])) {
            $emp = \App\Models\HR\Employee::find($data['employee_id']);
            if ($emp) {
                $data['full_name'] = $emp->full_name ?? $data['full_name'] ?? null;
                $data['email']     = $emp->email ?? $data['email'] ?? null;
                $data['phone']     = $emp->phone_number ?? $data['phone'] ?? null;
            }
        }

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $data['full_name'] = ucwords(strtolower($data['full_name'] ?? ''));

        return DB::transaction(function () use ($record, $data) {

            // 1. UPDATE / BUAT USER & SYNC ROLE
            if ($record->user) {
                $userUpdate = [
                    'name'  => $data['full_name'],
                    'email' => $data['email'] ?? $record->user->email,
                ];

                if (! empty($data['password'])) {
                    $userUpdate['password'] = Hash::make($data['password']);
                }

                $record->user->update($userUpdate);

                if (isset($data['roles'])) {
                    $record->user->syncRoles($data['roles']);
                }
            } elseif (! empty($data['email'])) {
                // Data lama yang belum punya user_id -> buat baru saat diedit
                $user = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name'     => $data['full_name'],
                        'password' => Hash::make($data['password'] ?? str()->random(12)),
                    ]
                );

                if (isset($data['roles'])) {
                    $user->syncRoles($data['roles']);
                }

                $data['user_id'] = $user->id;
            }

            // 2. BERSIHKAN ARRAY DATA yang bukan kolom nx_sales_people
            unset($data['password']);
            unset($data['roles']);
            unset($data['employee_id']);

            // 3. UPDATE DATA SALES PERSON
            $record->update($data);

            return $record;
        });
    }
}
