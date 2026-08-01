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

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Mutasi nama menjadi Title Case
        $data['full_name'] = ucwords(strtolower($data['full_name'] ?? ''));

        return DB::transaction(function () use ($record, $data) {

            // 1. UPDATE DATA USER & ROLE
            if ($record->user) {
                $userUpdate = [
                    'name'  => $data['full_name'],
                    'email' => $data['email'] ?? $record->user->email,
                ];

                if (! empty($data['password'])) {
                    $userUpdate['password'] = Hash::make($data['password']);
                }

                $record->user->update($userUpdate);

                // Sinkronisasi Role ke tabel User
                if (isset($data['roles'])) {
                    // Karena di Form kita pakai pluck('name', 'name'), $data['roles'] isinya langsung nama role
                    $record->user->syncRoles($data['roles']);
                }
            }

            // 2. BERSIHKAN ARRAY DATA
            // Hapus password dan roles agar tidak menyebabkan error "Column not found"
            // saat query update ke tabel nx_employees
            unset($data['password']);
            unset($data['roles']);

            // 3. UPDATE DATA EMPLOYEE
            $record->update($data);

            return $record;
        });
    }
}
