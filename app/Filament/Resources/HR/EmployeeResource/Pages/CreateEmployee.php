<?php
namespace App\Filament\Resources\HR\EmployeeResource\Pages;

use App\Filament\Resources\HR\EmployeeResource;
use App\Models\HR\Employee;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    public function getTitle(): string
    {
        return 'Tambah Karyawan';
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Ambil data roles dari form state (karena di-dehydrate)
            $roleIds = $this->form->getState()['roles'] ?? [];

            // 2. Buat atau cari User
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => ucwords(strtolower($data['full_name'])),
                    'password' => Hash::make($data['password']),
                ]
            );

            // 3. Buat data employee
            // Hapus 'roles' dari array $data agar tidak error saat create model Employee
            unset($data['roles']);
            unset($data['password']); // password juga tidak ada di tabel employee biasanya

            $employee = new Employee();
            $employee->fill([
                'user_id' => $user->id,
                'email' => $data['email'],
                'department_id' => $data['department_id'] ?? null,
                'office_id' => $data['office_id'] ?? null,
                'full_name' => ucwords(strtolower($data['full_name'])),
                'position' => $data['position'],
                'contract_type' => $data['contract_type'] ?? 'contract',
                'status' => $data['status'] ?? 'active',
                'phone_number' => $data['phone_number'],
                'address' => $data['address'] ?? null,
                'photo' => $data['photo'] ?? null,
                'identity_number' => $data['identity_number'] ?? null,
                'birth_place' => $data['birth_place'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'gender' => $data['gender'] ?? null,
                'marital_status' => $data['marital_status'] ?? null,
                'education_level' => $data['education_level'] ?? null,
                'join_date' => $data['join_date'] ?? null,
                'can_wfa' => $data['can_wfa'] ?? false,
                'can_unlock_shift' => $data['can_unlock_shift'] ?? false,
                'shift_id' => $data['shift_id'] ?? null,
            ]);

            $employee->save();

            // 4. Sinkronisasi Role ke USER (Bukan ke Employee)
            if (!empty($roleIds)) {
                // Jika menggunakan Spatie Permission, syncRoles bisa menerima ID
                $user->syncRoles($roleIds);
            }

            return $employee;
        });
    }
}
