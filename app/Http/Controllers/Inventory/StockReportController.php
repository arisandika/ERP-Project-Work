<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\Product;
use App\Models\Inventory\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function download(Request $request)
    {
        // Ambil filter dari URL
        $categoryId = $request->get('category_id');
        $status = $request->get('status');
        $warehouseId = $request->get('warehouse_id');

        // Query Product sesuai filter
        $query = Product::with(['category', 'unit', 'productStocks']);

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        // Filter Stock rendah / normal
        if ($status === 'low') {
            $query->whereHas('productStocks', function ($q) use ($warehouseId) {
                if ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
                $q->where('qty', '<=', 10);
            });
        } elseif ($status === 'normal') {
            $query->whereHas('productStocks', function ($q) use ($warehouseId) {
                if ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId);
                }
                $q->where('qty', '>', 10);
            });
        }

        $products = $query->get();

        // Ambil nama gudang untuk header laporan
        $warehouseName = $warehouseId ? Warehouse::find($warehouseId)?->warehouse_name : 'Semua Gudang';

        // Buat PDF dari view Blade
        $pdf = Pdf::loadView('pdf.stock-report', [
            'products' => $products,
            'warehouse' => $warehouseName,
            'status' => $status,
            'categoryId' => $categoryId,
            'date' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'portrait');

        // Unduh file
        return $pdf->download('laporan_stok_' . now()->format('Ymd_His') . '.pdf');
    }
}
