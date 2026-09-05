<?php

namespace App\Livewire;

use App\Models\AfterSales\ReturnRequest;
use App\Models\CRM\Customer;
use Livewire\Component;

class CustomerPortalReturnDetail extends Component
{
    public Customer $customer;
    public ReturnRequest $returnRequest;

    public function mount(string $rma)
    {
        $this->customer = request()->attributes->get('portal_customer');

        /** @var ReturnRequest|null $returnRequest */
        $returnRequest = ReturnRequest::where('rma_number', $rma)
            ->where('customer_id', $this->customer->id)
            ->with(['serialNumber.product', 'invoice', 'invoiceItem', 'purchaseReturn', 'procurementClaim'])
            ->first();

        if (! $returnRequest) {
            abort(404, 'RMA tidak ditemukan atau bukan milik Anda.');
        }

        $this->returnRequest = $returnRequest;
    }

    public function getStatusBadgeColor(string $status): array
    {
        return ReturnRequest::getCustomerStatusBadge($status);
    }

    public function render()
    {
        return view('livewire.customer-portal-return-detail', [
            'statusLabels' => ReturnRequest::getCustomerStatusLabels(),
        ])->layout('layouts.external');
    }
}