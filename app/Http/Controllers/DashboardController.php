<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        // Total stats
        $totalRevenue = Transaction::where('status', 'success')->sum('total_amount');
        $totalTransactions = Transaction::where('status', 'success')->count();
        $totalProducts = Product::count();

        // Chart Data (Last 7 Days Revenue)
        $chartData = Transaction::where('status', 'success')
            ->where('created_at', '>=', Carbon::now()->subDays(7))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        $chartLabels = $chartData->pluck('date')->toArray();
        $chartValues = $chartData->pluck('total')->toArray();

        // Top 5 Products
        $topProducts = DB::table('transaction_items')
            ->join('products', 'transaction_items.product_id', '=', 'products.id')
            ->join('transactions', 'transaction_items.transaction_id', '=', 'transactions.id')
            ->select('products.name', DB::raw('SUM(transaction_items.quantity) as total_sold'))
            ->where('transactions.status', 'success')
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        return view('dashboard', compact('totalRevenue', 'totalTransactions', 'totalProducts', 'chartLabels', 'chartValues', 'topProducts'));
    }
}
