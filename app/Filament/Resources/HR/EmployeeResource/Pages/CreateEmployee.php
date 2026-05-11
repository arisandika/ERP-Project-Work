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

            // REFAKTORISASI MENTOR: Smart Auto-Link
            // Cari User berdasarkan email. Jika ketemu, gunakan user tersebut.
            // Jika tidak ketemu, buat User baru dengan atribut di dalam array kedua.
            $user = User::firstOrCreate(
                ['email'    => $data['email']],
                [
                    'name'     => ucwords(strtolower($data['full_name'])),
                    'password' => Hash::make($data['password']),
                ]
            );

            // Jika user sudah ada sebelumnya namun input form mengirimkan password baru,
            // Opsional: Anda bisa menambahkan logika $user->update(['password' => ...]) di sini jika diinginkan.

            // Buat data employee yang terhubung dengan user
            $employee = new Employee([
                'user_id'          => $user->id,
                'email'            => $data['email'],
                'department_id'    => $data['department_id'] ?? null,
                'office_id'        => $data['office_id'] ?? null,
                'full_name'        => ucwords(strtolower($data['full_name'])),
                'position'         => $data['position'],
                'contract_type'    => $data['contract_type'] ?? 'contract',
                'status'           => $data['status'] ?? 'active',
                'phone_number'     => $data['phone_number'],
                'address'          => $data['address'] ?? null,
                'photo'            => $data['photo'] ?? null,

                // Personal information
                'identity_number'  => $data['identity_number'] ?? null,
                'birth_place'      => $data['birth_place'] ?? null,
                'birth_date'       => $data['birth_date'] ?? null,
                'gender'           => $data['gender'] ?? null,
                'marital_status'   => $data['marital_status'] ?? null,
                'education_level'  => $data['education_level'] ?? null,
                'join_date'        => $data['join_date'] ?? null,

                // Permission toggles
                'can_wfa'          => $data['can_wfa'] ?? false,
                'can_unlock_shift' => $data['can_unlock_shift'] ?? false,
            ]);

            // Gunakan metode associate alih-alih set properti manual (Best Practice)
            $employee->user()->associate($user);
            $employee->save();

            // Sinkronisasi peran (roles)
            if (isset($data['roles'])) {
                $roleNames = DB::table('roles')
                    ->whereIn('id', (array) $data['roles'])
                    ->pluck('name')
                    ->toArray();

                // $employee->syncRoles($roleNames);
                $user->syncRoles($roleNames); // Memastikan User juga mendapat role yang sama
            }

            return $employee;
        });
    }

}
