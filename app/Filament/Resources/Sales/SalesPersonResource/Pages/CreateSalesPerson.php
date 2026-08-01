<?php
namespace App\Filament\Resources\Sales\SalesPersonResource\Pages;

use App\Filament\Resources\Sales\SalesPersonResource;
use App\Models\Sales\SalesPerson;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateSalesPerson extends CreateRecord
{
    protected static string $resource = SalesPersonResource::class;

    public function getTitle(): string
    {
        return 'Tambah PIC Sales';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // 1. Ambil data roles dari form state
            $roles = $this->form->getState()['roles'] ?? [];

            // 2. Buat atau cari User berdasarkan email
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'     => ucwords(strtolower($data['full_name'] ?? '')),
                    'password' => Hash::make($data['password'] ?? str()->random(12)),
                ]
            );

            // Kalau ternyata user sudah ada sebelumnya tapi belum punya password
            // (mis. dibuat otomatis dari tempat lain), boleh update password bila diisi
            if ($user->wasRecentlyCreated === false && ! empty($data['password'])) {
                // biarkan saja sesuai helper text form: "password diabaikan jika email sudah terdaftar"
            }

            // 3. Bersihkan data yang bukan kolom nx_sales_people
            unset($data['roles']);
            unset($data['password']);
            unset($data['employee_id']);

            $salesPerson = new SalesPerson();
            $salesPerson->fill([
                'user_id'   => $user->id,
                'type'      => $data['type'] ?? null,
                'full_name' => ucwords(strtolower($data['full_name'] ?? '')),
                'email'     => $user->email,
                'phone'     => $data['phone'] ?? null,
                'status'    => $data['status'] ?? 'active',
            ]);

            $salesPerson->save();

            // 4. Sinkronisasi Role ke USER
            if (! empty($roles)) {
                $user->syncRoles($roles);
            }

            return $salesPerson;
        });
    }
}
