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
    public function downloadPdf(Request $request)
    {
        try {
            // Get filter parameters
            $fromDate = $request->get('from_date');
            $untilDate = $request->get('until_date');
            
            // Build query
            $query = StockTransaction::with(['product.unit']);
            
            if ($fromDate) {
                $query->whereDate('transaction_date', '>=', $fromDate);
            }
            
            if ($untilDate) {
                $query->whereDate('transaction_date', '<=', $untilDate);
            }
            
            $transactions = $query->orderBy('transaction_date', 'desc')->get();
            
            // Prepare data
            $transactionsData = $transactions->map(function ($transaction) {
                return [
                    'transaction_date' => $transaction->transaction_date,
                    'product_name' => $transaction->product->product_name ?? '-',
                    'type' => $transaction->type,
                    'quantity' => $transaction->quantity,
                    'unit_symbol' => $transaction->product->unit->symbol ?? 'pcs',
                    'notes' => $transaction->notes ?? '-',
                ];
            });
            
            // Generate HTML
            $html = view('pdf.transaction-report', [
                'transactions' => $transactionsData,
                'fromDate' => $fromDate,
                'untilDate' => $untilDate,
            ])->render();
            
            // Generate PDF
            $pdf = Pdf::loadHTML($html)->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'dejavu sans',
            ]);
            
            $pdf->setPaper('a4', 'landscape');
            
            // Download PDF
            $filename = 'Laporan-Transaksi-' . now()->format('Y-m-d') . '.pdf';
            return $pdf->download($filename);
            
        } catch (\Throwable $e) {
            \Log::error('PDF Generation Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }
    
    public function downloadStockReportPdf(Request $request)
    {
        try {
            // Get filter parameters
            $categoryId = $request->get('category_id');
            
            // Build query
            $query = Product::with(['category', 'unit', 'productStocks']);
            
            // Get category name for filter display
            $categoryFilter = 'Semua Kategori';
            if ($categoryId) {
                $category = Category::find($categoryId);
                if ($category) {
                    $categoryFilter = $category->name;
                    $query->where('category_id', $categoryId);
                }
            }
            
            $products = $query->orderBy('product_name')->get();
            
            // Prepare data
            $productsData = $products->map(function ($product) {
                return [
                    'kode_barang' => 'BRG-' . str_pad($product->id, 6, '0', STR_PAD_LEFT),
                    'product_name' => $product->product_name ?? '-',
                    'category_name' => $product->category->name ?? '-',
                    'total_stock' => $product->productStocks->sum('qty'),
                    'unit_symbol' => $product->unit->symbol ?? $product->unit->name ?? 'pcs',
                    'price' => $product->price ?? 0,
                ];
            });
            
            // Generate HTML
            $html = view('pdf.stock-report', [
                'products' => $productsData,
                'categoryFilter' => $categoryFilter,
            ])->render();
            
            // Generate PDF
            $pdf = Pdf::loadHTML($html)->setOption([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'dejavu sans',
            ]);
            
            $pdf->setPaper('a4', 'landscape');
            
            // Download PDF
            $filename = 'Laporan-Stok-Barang-' . now()->format('Y-m-d') . '.pdf';
            return $pdf->download($filename);
            
        } catch (\Throwable $e) {
            \Log::error('Stock Report PDF Generation Error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membuat PDF: ' . $e->getMessage());
        }
    }
}
