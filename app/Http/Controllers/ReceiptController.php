<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;

class ReceiptController extends Controller
{
    public function show($id)
    {
        $transaction = Transaction::with('items.product')->findOrFail($id);
        
        // Ensure user can only view their own receipts unless admin/owner
        if (auth()->user()->role === 'kasir' && $transaction->user_id !== auth()->id()) {
            abort(403);
        }

        return view('pos.receipt', compact('transaction'));
    }
}
