<?php
// Pages/CreateVisitAssignment.php
namespace App\Filament\Resources\SalesActivity\VisitAssignmentResource\Pages;

use App\Filament\Resources\SalesActivity\VisitAssignmentResource;
use App\Models\HR\Employee;
use App\Models\Sales\SalesPerson;
use Filament\Resources\Pages\CreateRecord;

class CreateVisitAssignment extends CreateRecord
{
    protected static string $resource = VisitAssignmentResource::class;

    public function getTitle(): string
    {
        return 'Tambah Kunjungan Baru';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan assigned_by terisi meski field di-disable
        if (empty($data['assigned_by'])) {
            $data['assigned_by'] = Employee::where('user_id', auth()->id())->value('id');
        }

        // Normalisasi morphMap — simpan alias bukan class name penuh
        // (sudah ditangani oleh morphMap di AppServiceProvider,
        //  tapi ini sebagai fallback safety)
        $data['assigned_to_type'] = match ($data['assigned_to_type']) {
            Employee::class => 'employee',
            SalesPerson::class => 'salesperson',
            default => $data['assigned_to_type'],
        };

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}