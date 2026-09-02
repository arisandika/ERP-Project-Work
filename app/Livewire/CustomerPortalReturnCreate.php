<?php
namespace App\Livewire;

use App\Models\AfterSales\ReturnRequest;
use App\Models\CRM\Customer;
use App\Models\Inventory\SerialNumber;
use App\Models\Sales\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomerPortalReturnCreate extends Component
{
    use WithFileUploads;

    public Customer $customer;

    // ==== STEP STATE ====
    public int $step = 1;

    // ==== STEP 1: Invoice ====
    public string $invoice_number_input = '';
    public ?Invoice $invoice            = null;
    public $invoiceItems                = [];
    public bool $invoicePrefilled       = false; // prefilled via email link

    // ==== STEP 2: Pilih item + keluhan ====
    public ?int $selected_invoice_item_id = null;
    public $selectedItem                  = null; // InvoiceItem model, dipilih dari invoiceItems
    public string $warranty_type          = '';
    public string $issue_description      = '';

    // ==== STEP 3: SN scan / qty ====
    public bool $productIsSerialized        = false;
    public ?string $scannedSerialNumber     = null;
    public ?int $matchedSerialNumberId      = null;
    public string $matchedSerialNumberLabel = '';
    public ?int $qty                        = null;

    // ==== STEP 4: Upload bukti ====
    public $evidenceUploads = [];

    public function mount()
    {
        $this->customer = request()->attributes->get('portal_customer');

        // prefill invoice jika datang lewat link email token
        $invoiceId = request()->query('invoice_id') ?: request()->query('invoice');
        if ($invoiceId) {
            $this->invoice = Invoice::where('id', $invoiceId)
                ->where('nx_customer_id', $this->customer->id)
                ->with(['items'])
                ->first();

            if ($this->invoice) {
                $this->invoice_number_input = $this->invoice->invoice_number;
                $this->invoiceItems         = $this->invoice->items->where('item_type', 'product')->values();
                $this->invoicePrefilled     = true;
            }
        }
    }

    // =========================================================
    // STEP 1 — Cari & validasi invoice
    // =========================================================
    public function findInvoice()
    {
        if ($this->invoicePrefilled) {
            $this->step = 2;
            return;
        }

        $this->resetErrorBag();
        $this->invoice      = null;
        $this->invoiceItems = [];

        $this->validate([
            'invoice_number_input' => 'required|string|min:3',
        ], [], ['invoice_number_input' => 'Nomor Invoice']);

        $invoice = Invoice::where('nx_customer_id', $this->customer->id)
            ->where('invoice_number', $this->invoice_number_input)
            ->with(['items'])
            ->first();

        if (! $invoice) {
            $this->addError('invoice_number_input', 'Nomor invoice tidak ditemukan atau bukan milik Anda.');
            return;
        }

        $this->invoice = $invoice;

        // hanya item bertipe 'product' yang bisa diretur
        $this->invoiceItems = $invoice->items
            ->where('item_type', 'product')
            ->values();
    }

    public function proceedToStep2(int $invoiceItemId)
    {
        $item = collect($this->invoiceItems)->firstWhere('id', $invoiceItemId);

        if (! $item || ! $this->invoice) {
            $this->addError('invoice_number_input', 'Item tidak valid.');
            return;
        }

        $this->selected_invoice_item_id = $item->id;
        $this->selectedItem             = $item;
        $this->step                     = 2;
    }

    // =========================================================
    // STEP 2 — Detail keluhan
    // =========================================================
    public function proceedToStep3()
    {
        $this->validate([
            'warranty_type'     => 'required|in:supplier,store',
            'issue_description' => 'required|string|min:10|max:1000',
        ]);

        // cek apakah produk ini serialized
        $product                   = \App\Models\Inventory\Product::find($this->selectedItem->item_id);
        $this->productIsSerialized = (bool) ($product->is_serialized ?? false);

        $this->step = 3;
    }

    // =========================================================
    // STEP 3 — Scan SN (jika serialized) / input qty
    // =========================================================
    public function updatedScannedSerialNumber($value)
    {
        if (! $value || ! $this->invoice) {
            return;
        }

        $this->matchedSerialNumberId    = null;
        $this->matchedSerialNumberLabel = '';

        // VALIDASI SERVER-SIDE: SN harus benar milik customer ini
        // dan idealnya terhubung ke produk pada invoice item yang dipilih
        $serial = SerialNumber::where('serial_number', $value)
            ->where('customer_id', $this->customer->id)
            ->where('product_id', $this->selectedItem->item_id)
            ->first();

        if (! $serial) {
            $this->addError('scannedSerialNumber', 'SN tidak ditemukan atau tidak sesuai dengan produk & akun Anda.');
            $this->scannedSerialNumber = null;
            return;
        }

        // cek belum ada RMA aktif untuk SN ini
        $activeRma = ReturnRequest::where('serial_number_id', $serial->id)
            ->whereNotIn('status', [ReturnRequest::STATUS_RETURNED_TO_CLIENT])
            ->exists();

        if ($activeRma) {
            $this->addError('scannedSerialNumber', 'SN ini sudah memiliki pengajuan return yang sedang berjalan.');
            $this->scannedSerialNumber = null;
            return;
        }

        $this->matchedSerialNumberId    = $serial->id;
        $this->matchedSerialNumberLabel = $serial->serial_number;
    }

    public function resetScan()
    {
        $this->scannedSerialNumber      = null;
        $this->matchedSerialNumberId    = null;
        $this->matchedSerialNumberLabel = '';
        $this->resetErrorBag('scannedSerialNumber');
    }

    public function proceedToStep4()
    {
        if ($this->productIsSerialized) {
            if (! $this->matchedSerialNumberId) {
                $this->addError('scannedSerialNumber', 'Silakan scan SN produk terlebih dahulu.');
                return;
            }
        } else {
            $this->validate([
                'qty' => 'required|integer|min:1|max:' . (int) $this->selectedItem->qty,
            ], [], ['qty' => 'Jumlah barang']);
        }

        $this->step = 4;
    }

    // =========================================================
    // STEP 4 — Upload bukti & submit
    // =========================================================
    public function removeEvidence(int $index)
    {
        unset($this->evidenceUploads[$index]);
        $this->evidenceUploads = array_values($this->evidenceUploads);
    }

    public function submit()
    {
        $this->validate([
            'evidenceUploads'   => 'required|array|min:1|max:5',
            'evidenceUploads.*' => 'image|max:5120', // 5MB per file
        ], [], ['evidenceUploads' => 'Bukti kerusakan/kendala']);

        DB::beginTransaction();

        try {
            $paths = [];
            foreach ($this->evidenceUploads as $file) {
                $paths[] = $file->store('rma-evidence/' . $this->customer->id, 'public');
            }

            ReturnRequest::create([
                'rma_number'        => ReturnRequest::generateRmaNumber(),
                'customer_id'       => $this->customer->id,
                'invoice_id'        => $this->invoice->id,
                'invoice_item_id'   => $this->selectedItem->id,
                'serial_number_id'  => $this->productIsSerialized ? $this->matchedSerialNumberId : null,
                'product_id'        => $this->productIsSerialized ? null : $this->selectedItem->item_id,
                'qty'               => $this->productIsSerialized ? 1 : $this->qty,
                'warranty_type'     => $this->warranty_type,
                'status'            => ReturnRequest::STATUS_RECEIVED,
                'received_date'     => now(), // <-- tambahkan ini
                'issue_description' => $this->issue_description,
                'evidence_files'    => $paths,
                'created_by'        => null,
                'source'            => ReturnRequest::SOURCE_CUSTOMER_PORTAL,
            ]);
            
            DB::commit();

            session()->flash('message', 'Pengajuan return berhasil dikirim. Tim kami akan segera memproses.');

            return redirect()->route('customer-portal.dashboard');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);
            session()->flash('error', 'Terjadi kesalahan saat mengirim pengajuan. Silakan coba lagi.');
        }
    }

    // =========================================================
    // Navigasi mundur
    // =========================================================
    public function backTo(int $targetStep)
    {
        $this->step = max(1, $targetStep);
    }

    public function render()
    {
        return view('livewire.customer-portal-return-create')
            ->layout('layouts.external');
    }
}
