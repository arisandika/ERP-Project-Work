<?php

namespace App\Livewire;

use App\Models\CRM\Customer;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class CustomerPortal extends Component
{
    public $email;
    public $phone; // 5 digit terakhir
    public $error;

    public function mount()
    {
        if (Session::get('customer_portal_authenticated') && Session::get('customer_portal_id')) {
            return redirect()->route('customer-portal.dashboard');
        }
    }

    public function login()
    {
        $this->error = null;

        $this->validate([
            'email' => ['required', 'email'],
            'phone' => ['required', 'digits:5'],
        ], [
            'phone.digits' => 'Masukkan 5 digit terakhir nomor HP Anda.',
        ]);

        $customer = Customer::where('email', $this->email)
            ->whereNull('deleted_at')
            ->first();

        // Lockout check
        if ($customer && $customer->portal_locked_until && now()->lt($customer->portal_locked_until)) {
            $this->error = 'Akun sementara dikunci karena terlalu banyak percobaan. Coba lagi nanti.';
            return;
        }

        $valid = false;

        if ($customer && $customer->phone) {
            $last5 = substr(preg_replace('/\D/', '', $customer->phone), -5);
            $valid = hash_equals($last5, $this->phone);
        }

        if (!$valid) {
            if ($customer) {
                $customer->increment('portal_login_attempts');
                if ($customer->portal_login_attempts >= 5) {
                    $customer->update([
                        'portal_locked_until' => now()->addMinutes(15),
                        'portal_login_attempts' => 0,
                    ]);
                }
            }

            $this->error = 'Email atau nomor HP tidak sesuai.';
            return;
        }

        // reset attempt counter kalau berhasil
        $customer->update([
            'portal_login_attempts' => 0,
            'portal_locked_until' => null,
        ]);

        // WAJIB regenerate session id supaya tidak kena session fixation
        Session::regenerate();

        Session::put([
            'customer_portal_id' => $customer->id,
            'customer_portal_authenticated' => true,
        ]);

        return redirect()->route('customer-portal.dashboard');
    }

    public function render()
    {
        return view('livewire.customer-portal-login')
            ->layout('layouts.external');
    }
}