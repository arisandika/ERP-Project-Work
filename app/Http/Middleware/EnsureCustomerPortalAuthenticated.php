<?php
namespace App\Http\Middleware;

use App\Models\CRM\Customer;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class EnsureCustomerPortalAuthenticated
{
    public function handle(Request $request, Closure $next)
    {
        $customerId = Session::get('customer_portal_id');

        if (! $customerId) {
            return redirect()->route('customer-portal.login');
        }

        $customer = Customer::where('id', $customerId)
            ->whereNull('deleted_at')
            ->first();

        if (! $customer) {
            Session::forget(['customer_portal_id', 'customer_portal_authenticated']);
            return redirect()->route('customer-portal.login');
        }

        // taruh instance customer di request supaya component tidak perlu query ulang
        $request->attributes->set('portal_customer', $customer);

        return $next($request);
    }
}
