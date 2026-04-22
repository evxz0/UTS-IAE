<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel POS') }} - Point of Sale</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Midtrans Snap -->
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ env('MIDTRANS_CLIENT_KEY') }}"></script>
</head>
<body class="font-sans antialiased text-gray-900 bg-gray-50 flex h-screen overflow-hidden" x-data="posApp()">

    <!-- Sidebar / Nav for POS -->
    <div class="w-20 bg-white border-r flex flex-col items-center py-6 shadow-sm z-10">
        <div class="w-10 h-10 bg-blue-600 text-white rounded-lg flex items-center justify-center font-bold text-xl mb-8">
            P
        </div>
        <nav class="flex-1 flex flex-col gap-6">
            <a href="#" class="p-3 text-blue-600 bg-blue-50 rounded-lg" title="POS">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z" />
                </svg>
            </a>
            <!-- Logout Button -->
            <form method="POST" action="{{ route('logout') }}" class="mt-auto">
                @csrf
                <button type="submit" class="p-3 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Logout">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                    </svg>
                </button>
            </form>
        </nav>
    </div>

    <!-- Main Content: Product Grid -->
    <main class="flex-1 flex flex-col bg-gray-50 h-full overflow-hidden">
        <!-- Header -->
        <header class="h-20 bg-white border-b px-8 flex items-center justify-between z-0">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Point of Sale</h1>
                <p class="text-sm text-gray-500">Select products to add to cart</p>
            </div>
            
            <div class="relative w-72">
                <input type="text" x-model="search" class="w-full border-gray-300 focus:border-blue-500 focus:ring-blue-500 rounded-lg shadow-sm" placeholder="Search product...">
            </div>
        </header>

        <!-- Product Grid Area -->
        <div class="flex-1 p-8 overflow-y-auto">
            <!-- Categories -->
            <div class="flex gap-3 mb-6 overflow-x-auto pb-2">
                <button @click="activeCategory = null" :class="activeCategory === null ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-4 py-2 border rounded-full text-sm font-medium transition whitespace-nowrap">All Items</button>
                @foreach($categories as $cat)
                <button @click="activeCategory = {{ $cat->id }}" :class="activeCategory === {{ $cat->id }} ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 hover:bg-gray-50'" class="px-4 py-2 border rounded-full text-sm font-medium transition whitespace-nowrap">{{ $cat->name }}</button>
                @endforeach
            </div>

            <!-- Products -->
            <div class="grid grid-cols-3 xl:grid-cols-4 gap-6">
                <!-- Alpine Template for Products -->
                <template x-for="product in filteredProducts" :key="product.id">
                    <div @click="addToCart(product)" class="bg-white rounded-xl shadow-sm border p-4 cursor-pointer hover:border-blue-500 hover:shadow-md transition">
                        <div class="w-full h-32 bg-gray-100 rounded-lg mb-4 flex items-center justify-center text-gray-400 overflow-hidden">
                            <template x-if="product.image_url">
                                <img :src="product.image_url" class="object-cover w-full h-full">
                            </template>
                            <template x-if="!product.image_url">
                                <span>No Image</span>
                            </template>
                        </div>
                        <h3 class="font-semibold text-gray-800 truncate" x-text="product.name"></h3>
                        <p class="text-blue-600 font-bold mt-1" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(product.price)"></p>
                    </div>
                </template>
            </div>
            
            <!-- Empty State -->
            <div x-show="filteredProducts.length === 0" class="text-center py-20 text-gray-500">
                No products found.
            </div>
        </div>
    </main>

    <!-- Right Sidebar: Cart -->
    <aside class="w-96 bg-white border-l shadow-xl flex flex-col h-full z-20">
        <div class="p-6 border-b">
            <h2 class="text-xl font-bold text-gray-800">Current Order</h2>
            <p class="text-sm text-gray-500 mt-1">Order #<span x-text="Math.floor(Math.random() * 90000) + 10000"></span></p>
        </div>

        <!-- Cart Items List -->
        <div class="flex-1 p-6 overflow-y-auto flex flex-col gap-4">
            <template x-for="item in cart" :key="item.id">
                <div class="flex items-center justify-between border-b pb-4">
                    <div class="flex-1 overflow-hidden pr-2">
                        <h4 class="font-medium text-gray-800 truncate" x-text="item.name"></h4>
                        <p class="text-sm text-gray-500" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(item.price)"></p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button @click="decreaseQty(item)" class="w-8 h-8 rounded bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gray-200">-</button>
                        <span class="font-medium" x-text="item.qty"></span>
                        <button @click="increaseQty(item)" class="w-8 h-8 rounded bg-blue-100 flex items-center justify-center text-blue-600 hover:bg-blue-200">+</button>
                    </div>
                </div>
            </template>
            
            <div x-show="cart.length === 0" class="text-center text-sm text-gray-400 mt-10">
                Cart is empty
            </div>
        </div>

        <!-- Cart Summary & Actions -->
        <div class="p-6 bg-gray-50 border-t">
            <div class="flex justify-between mb-3 text-gray-600">
                <span>Subtotal</span>
                <span x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(subtotal)"></span>
            </div>
            <div class="flex justify-between mb-3 text-gray-600">
                <span>Tax (11%)</span>
                <span x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(tax)"></span>
            </div>
            <div class="flex justify-between mb-4 text-gray-600 border-b pb-4">
                <span>Discount</span>
                <span>Rp 0</span>
            </div>
            <div class="flex justify-between mb-6 text-xl font-bold text-gray-800">
                <span>Total</span>
                <span class="text-blue-600" x-text="'Rp ' + new Intl.NumberFormat('id-ID').format(total)"></span>
            </div>

            <!-- Payment Method Toggle -->
            <div class="flex gap-2 mb-4">
                <button @click="paymentMethod = 'cash'" :class="paymentMethod === 'cash' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border'" class="flex-1 py-2 rounded-lg text-sm font-bold transition">Cash</button>
                <button @click="paymentMethod = 'qris'" :class="paymentMethod === 'qris' ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border'" class="flex-1 py-2 rounded-lg text-sm font-bold transition">QRIS / Tf</button>
            </div>

            <!-- Payment Action -->
            <button @click="checkout()" :disabled="cart.length === 0 || isProcessing" :class="cart.length === 0 || isProcessing ? 'bg-gray-300 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'" class="w-full text-white font-bold py-4 rounded-xl shadow-md transition text-lg flex items-center justify-center gap-2">
                <span x-show="!isProcessing">Pay Now</span>
                <span x-show="isProcessing">Processing...</span>
            </button>
        </div>
    </aside>

    <script>
    function posApp() {
        return {
            products: @json($products ?? []),
            search: '',
            activeCategory: null,
            cart: [],
            paymentMethod: 'cash',
            isProcessing: false,
            
            get filteredProducts() {
                return this.products.filter(p => {
                    const matchSearch = p.name.toLowerCase().includes(this.search.toLowerCase());
                    const matchCategory = this.activeCategory === null || p.category_id === this.activeCategory;
                    return matchSearch && matchCategory;
                });
            },
            
            addToCart(product) {
                const existing = this.cart.find(item => item.id === product.id);
                if (existing) {
                    if (existing.qty < product.stock) existing.qty++;
                } else {
                    if (product.stock > 0) this.cart.push({ ...product, qty: 1 });
                }
            },
            
            increaseQty(item) {
                if (item.qty < item.stock) item.qty++;
            },
            
            decreaseQty(item) {
                if (item.qty > 1) {
                    item.qty--;
                } else {
                    this.cart = this.cart.filter(i => i.id !== item.id);
                }
            },
            
            get subtotal() {
                return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
            },
            
            get tax() {
                return this.subtotal * 0.11;
            },
            
            get total() {
                return this.subtotal + this.tax;
            },
            
            async checkout() {
                this.isProcessing = true;
                try {
                    const response = await fetch('/checkout', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            cart: this.cart,
                            payment_method: this.paymentMethod
                        })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        if (this.paymentMethod === 'cash') {
                            alert('Cash Payment Successful!');
                            this.cart = [];
                            window.open('/receipt/' + data.transaction_id, '_blank');
                        } else if (data.snap_token) {
                            snap.pay(data.snap_token, {
                                onSuccess: (result) => {
                                    alert('Payment Success!');
                                    this.cart = [];
                                    window.open('/receipt/' + data.transaction_id, '_blank');
                                },
                                onPending: (result) => {
                                    alert('Waiting for payment...');
                                },
                                onError: (result) => {
                                    alert('Payment failed');
                                },
                                onClose: () => {
                                    alert('Payment popup closed');
                                }
                            });
                        }
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    alert('Checkout failed: ' + error.message);
                } finally {
                    this.isProcessing = false;
                }
            }
        }
    }
    </script>
</body>
</html>
