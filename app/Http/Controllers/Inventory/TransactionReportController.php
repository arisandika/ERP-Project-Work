<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Product;
use App\Models\Inventory\Category;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionReportController extends Controller
{
    public function download(Request $request)
    {
        // 1. Ambil filter dari request
        $fromDate  = $request->get('from_date');
        $untilDate = $request->get('until_date');

        // 2. Query transaksi stok + relasi
        $query = StockTransaction::with([
            'product.unit',
            'warehouse',
            'creator',
        ]);

        // 3. Filter periode tanggal
        if ($fromDate) {
            $query->whereDate('transaction_date', '>=', $fromDate);
        }

        if ($untilDate) {
            $query->whereDate('transaction_date', '<=', $untilDate);
        }

        // 4. Ambil data
        $transactions = $query
            ->orderBy('transaction_date', 'desc')
            ->get();

        // 5. Info tambahan untuk header laporan
        $printDate = now()->format('d M Y H:i');

        // 6. Generate PDF
        $pdf = Pdf::loadView('pdf.transaction-report', [
            'transactions' => $transactions,
            'fromDate'     => $fromDate,
            'untilDate'    => $untilDate,
            'printDate'    => $printDate,
        ])
            ->setPaper('a4', 'portrait');

        // 7. Download file
        return $pdf->download(
            'laporan_transaksi_stok_' . now()->format('Ymd_His') . '.pdf'
        );
    }
}
