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
        return match ($status) {
            ReturnRequest::STATUS_RECEIVED           => ['bg' => 'bg-blue-100 dark:bg-blue-900/20', 'text' => 'text-blue-600 dark:text-blue-400', 'dot' => '#2563eb'],
            ReturnRequest::STATUS_SENT_TO_VENDOR     => ['bg' => 'bg-purple-100 dark:bg-purple-900/20', 'text' => 'text-purple-600 dark:text-purple-400', 'dot' => '#9333ea'],
            ReturnRequest::STATUS_INTERNAL_REPAIR    => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/20', 'text' => 'text-yellow-600 dark:text-yellow-400', 'dot' => '#ca8a04'],
            ReturnRequest::STATUS_READY_FOR_RETURN   => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/20', 'text' => 'text-cyan-600 dark:text-cyan-400', 'dot' => '#0891b2'],
            ReturnRequest::STATUS_RETURNED_TO_CLIENT => ['bg' => 'bg-green-100 dark:bg-green-900/20', 'text' => 'text-green-600 dark:text-green-400', 'dot' => '#16a34a'],
            default                                  => ['bg' => 'bg-gray-100 dark:bg-gray-900/20', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => '#6b7280'],
        };
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
            ])->count(),
            'completed'      => $allReturns->where('status', ReturnRequest::STATUS_RETURNED_TO_CLIENT)->count(),
            'total_products' => SerialNumber::where('customer_id', $this->customer->id)
                ->where('status', SerialNumber::STATUS_SOLD)
                ->count(),
        ];

        return view('livewire.customer-portal-dashboard', [
            'returns'      => $returns,
            'stats'        => $stats,
            'statusLabels' => ReturnRequest::getStatusLabels(),
        ])->layout('layouts.external'); // <- tambahkan ini
    }
}
