<?php
namespace App\Livewire;

use App\Models\AfterSales\ReturnRequest;
use App\Models\CRM\Customer;
use App\Models\Inventory\SerialNumber;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class CustomerPortalDashboard extends Component
{
    public Customer $customer;

    public function mount()
    {
        $this->customer = request()->attributes->get('portal_customer');
    }

    public function logout()
    {
        Session::forget(['customer_portal_id', 'customer_portal_authenticated']);
        Session::regenerate();

        return redirect()->route('customer-portal.login');
    }

    public function getStatusBadgeColor(string $status): array
    {
        return ReturnRequest::getCustomerStatusBadge($status);
    }

    public function render()
    {
        $returns = ReturnRequest::where('customer_id', $this->customer->id)
            ->with(['serialNumber.product'])
            ->latest()
            ->paginate(10);

        $allReturns = ReturnRequest::where('customer_id', $this->customer->id)->get();

        $stats = [
            'total'          => $allReturns->count(),
            'in_progress'    => $allReturns->whereNotIn('status', [
                ReturnRequest::STATUS_RETURNED_TO_CLIENT,
                ReturnRequest::STATUS_REJECTED,
                ReturnRequest::STATUS_WARRANTY_REJECTED,
            ])->count(),
            'completed'      => $allReturns->where('status', ReturnRequest::STATUS_RETURNED_TO_CLIENT)->count(),
            'total_products' => SerialNumber::where('customer_id', $this->customer->id)
                ->where('status', SerialNumber::STATUS_SOLD)
                ->count(),
        ];

        return view('livewire.customer-portal-dashboard', [
            'returns'      => $returns,
            'stats'        => $stats,
            'statusLabels' => ReturnRequest::getCustomerStatusLabels(),
        ])->layout('layouts.external'); // <- tambahkan ini
    }
}
