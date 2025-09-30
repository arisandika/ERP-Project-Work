<?php

namespace App\Filament\Resources\HR\EmployeeResource\Pages;

use App\Filament\Resources\HR\EmployeeResource;
use App\Models\HR\Employee;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {

            $user = User::create([
                'name' => ucwords(strtolower($data['full_name'])),
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $employee = new Employee([
                'user_id' => $user->id,
                'email' => $data['email'],
                'department_id' => $data['department_id'],
                'full_name' => ucwords(strtolower($data['full_name'])),
                'position' => $data['position'],
                'contract_type' => $data['contract_type'] ?? 'contract',
                'status' => $data['status'] ?? 'active',
                'phone_number' => $data['phone_number'],
                'address' => $data['address'] ?? null,
                'photo' => $data['photo'] ?? null,
                // Face recognition fields (hidden for now)
                'face_embeddings' => $data['face_embeddings'] ?? null,
                'face_embedding_path' => $data['face_embedding_path'] ?? null,
                'face_landmarks' => $data['face_landmarks'] ?? null,
            ]);

            $employee->user()->associate($user);
            $employee->save();

            if (isset($data['roles'])) {

                $roleNames = DB::table('roles')
                    ->whereIn('id', (array) $data['roles'])
                    ->pluck('name')
                    ->toArray();

                $employee->syncRoles($roleNames);
                $user->syncRoles($roleNames);
            }

            return $employee;
        });
    }
}
