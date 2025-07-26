<x-filament-panels::page>
    {{-- Grid utama untuk layout 2 kolom --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Kolom Kiri: Daftar Produk --}}
        <div class="lg:col-span-2 space-y-6">
            @forelse ($productsByUnit as $unitName => $products)
                <x-filament::section>
                    <x-slot name="heading">
                        Stok Unit: {{ $unitName }}
                    </x-slot>

                    <div class="divide-y divide-gray-200 dark:divide-white/10">
                        @foreach ($products as $product)
                            @php
                                // Ambil stok yang dimiliki sales untuk produk ini
                                $availableStock = $salesStocks[$product['id']] ?? 0;
                            @endphp

                            {{-- Hanya tampilkan produk jika stoknya ada di tangan sales --}}
                            @if ($availableStock > 0)
                                <div class="flex items-center justify-between py-4">
                                    {{-- Informasi Produk --}}
                                    <div>
                                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                                            {{ $product['product']['nama_produk'] }}
                                        </p>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">
                                            Stok Anda: <span class="font-medium text-primary-600">{{ $availableStock }}</span>
                                        </p>
                                    </div>

                                    {{-- Tombol Interaktif Plus/Minus --}}
                                    <div class="flex items-center gap-3">
                                        <x-filament::icon-button
                                            icon="heroicon-m-minus"
                                            label="Kurangi"
                                            wire:click="decrementQuantity({{ $product['id'] }})"
                                            :disabled="($orderQuantities[$product['id']] ?? 0) <= 0"
                                            size="sm"
                                        />

                                        <span class="text-lg font-bold w-10 text-center">
                                            {{ $orderQuantities[$product['id']] ?? 0 }}
                                        </span>

                                        <x-filament::icon-button
                                            icon="heroicon-m-plus"
                                            label="Tambah"
                                            wire:click="incrementQuantity({{ $product['id'] }})"
                                            :disabled="($orderQuantities[$product['id']] ?? 0) >= $availableStock"
                                            size="sm"
                                        />
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </x-filament::section>
            @empty
                {{-- Tampilan jika sales tidak memegang stok apapun --}}
                <x-filament::section>
                    <div class="text-center py-12">
                        <x-heroicon-o-x-circle class="mx-auto h-12 w-12 text-gray-400" />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">Stok Kosong</h3>
                        <p class="mt-1 text-sm text-gray-500">Anda belum memegang stok produk apapun.</p>
                    </div>
                </x-filament::section>
            @endforelse
        </div>

        {{-- Kolom Kanan: Sidebar Ringkasan & Aksi --}}
        <div class="lg:col-span-1">
            <div class="sticky top-24 space-y-6">
                {{-- Bagian Ringkasan Pesanan --}}
                <x-filament::section>
                    <x-slot name="heading">
                        Ringkasan Pesanan
                    </x-slot>

                    @php
                        $cartItems = $this->getCartItems();
                        $totalPrice = 0;
                    @endphp

                    @if (empty($cartItems))
                        <p class="text-sm text-gray-500 text-center py-4">Keranjang masih kosong.</p>
                    @else
                        <div class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($cartItems as $productUnitId => $quantity)
                                @php
                                    $itemUnit = \App\Models\ProductUnit::find($productUnitId);
                                    $itemPrice = $itemUnit->harga_jual * $quantity;
                                    $totalPrice += $itemPrice;
                                @endphp
                                <div class="flex justify-between py-2 text-sm">
                                    <p class="text-gray-600 dark:text-gray-300">
                                        {{ $quantity }}x {{ $itemUnit->product->nama_produk }} ({{ $itemUnit->unit->nama_unit }})
                                    </p>
                                    <p class="font-medium text-gray-900 dark:text-white">
                                        Rp {{ number_format($itemPrice, 0, ',', '.') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>

                        <div class="border-t border-gray-200 dark:border-white/10 pt-4 mt-4">
                            <div class="flex justify-between font-bold text-base">
                                <span>Total</span>
                                <span>Rp {{ number_format($totalPrice, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    @endif
                </x-filament::section>
                
                {{-- Bagian Metode Pembayaran (Hanya muncul jika ada item di keranjang) --}}
                @if (!empty($cartItems))
                    <x-filament::section>
                        <x-slot name="heading">
                            Metode Pembayaran
                        </x-slot>

                        <x-filament::input.select wire:model.live="paymentMethod">
                            <option value="cash" selected>Cash</option>
                            <option value="transfer">Transfer Bank</option>
                            <option value="qris">QRIS</option>
                        </x-filament::input.select>
                    </x-filament::section>

                    {{-- Tombol Aksi Utama --}}
                    <div class="fi-page-actions">
                        @foreach ($this->getActions() as $action)
                            {{ $action }}
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-filament-panels::page>
