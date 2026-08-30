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
        return match ($status) {
            ReturnRequest::STATUS_RECEIVED           => ['bg' => 'bg-blue-100 dark:bg-blue-900/20', 'text' => 'text-blue-600 dark:text-blue-400', 'dot' => '#2563eb'],
            ReturnRequest::STATUS_SENT_TO_VENDOR     => ['bg' => 'bg-purple-100 dark:bg-purple-900/20', 'text' => 'text-purple-600 dark:text-purple-400', 'dot' => '#9333ea'],
            ReturnRequest::STATUS_INTERNAL_REPAIR    => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/20', 'text' => 'text-yellow-600 dark:text-yellow-400', 'dot' => '#ca8a04'],
            ReturnRequest::STATUS_READY_FOR_RETURN   => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/20', 'text' => 'text-cyan-600 dark:text-cyan-400', 'dot' => '#0891b2'],
            ReturnRequest::STATUS_RETURNED_TO_CLIENT => ['bg' => 'bg-green-100 dark:bg-green-900/20', 'text' => 'text-green-600 dark:text-green-400', 'dot' => '#16a34a'],
            ReturnRequest::STATUS_REJECTED           => ['bg' => 'bg-red-100 dark:bg-red-900/20', 'text' => 'text-red-600 dark:text-red-400', 'dot' => '#dc2626'],
            default                                  => ['bg' => 'bg-gray-100 dark:bg-gray-900/20', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => '#6b7280'],
        };
    }

    public function render()
    {
        return view('livewire.customer-portal-return-detail', [
            'statusLabels' => ReturnRequest::getStatusLabels(),
        ])->layout('layouts.external');
    }
}