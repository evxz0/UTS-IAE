<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard Overview') }}
        </h2>
    </x-slot>

    <!-- Include Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Pendapatan</span>
                    <span class="text-3xl font-bold text-gray-800">Rp {{ number_format($totalRevenue, 0, ',', '.') }}</span>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Total Transaksi</span>
                    <span class="text-3xl font-bold text-blue-600">{{ number_format($totalTransactions) }}</span>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100 flex flex-col">
                    <span class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-2">Produk Terdaftar</span>
                    <span class="text-3xl font-bold text-gray-800">{{ number_format($totalProducts) }}</span>
                </div>
            </div>

            <!-- Chart & Top Products -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Revenue Chart -->
                <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Grafik Penjualan 7 Hari Terakhir</h3>
                    <div class="relative h-72 w-full">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800 mb-6">Penjualan Terbanyak</h3>
                    <div class="space-y-4">
                        @forelse($topProducts as $item)
                        <div class="flex items-center justify-between border-b border-gray-100 pb-3 last:border-0">
                            <div>
                                <h4 class="font-semibold text-gray-800 text-sm">{{ $item->name }}</h4>
                                <span class="text-xs text-gray-500">Terjual</span>
                            </div>
                            <div class="bg-blue-50 text-blue-700 px-3 py-1 rounded-full text-sm font-bold">
                                {{ $item->total_sold }}
                            </div>
                        </div>
                        @empty
                        <div class="text-center py-6 text-gray-500 text-sm">
                            Belum ada transaksi.
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Chart Configuration -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            // Format dates simply
            const rawLabels = @json($chartLabels);
            const data = @json($chartValues);
            
            const labels = rawLabels.map(date => {
                const d = new Date(date);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            });

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Pendapatan (Rp)',
                        data: data,
                        borderColor: '#2563EB',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 3,
                        pointBackgroundColor: '#2563EB',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { borderDash: [4, 4], color: '#E5E7EB' },
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        },
                        x: {
                            grid: { display: false }
                        }
                    }
                }
            });
        });
    </script>
</x-app-layout>
