<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\DeliveryOrder;
use Illuminate\Http\Request;

class DeliveryOrderTrackingController extends Controller
{
    public function show($do_number)
    {
        $do = DeliveryOrder::with(['items', 'customer'])->where('do_number', $do_number)->firstOrFail();
        return view('delivery-order.tracking', compact('do'));
    }

    public function markAsDelivered(Request $request, $do_number)
    {
        $do = DeliveryOrder::where('do_number', $do_number)->firstOrFail();

        if (in_array($do->status, ['delivered', 'cancelled'])) {
            return back()->with('error', 'Status Surat Jalan ini sudah selesai atau dibatalkan.');
        }

        $request->validate(['penerima' => 'required|string|max:100']);

        $do->update([
            'status' => 'delivered',
            'proof_notes' => 'Dikonfirmasi via sistem tracking oleh: ' . $request->penerima,
        ]);

        return back()->with('success', 'Barang berhasil dikonfirmasi diterima!');
    }
}
