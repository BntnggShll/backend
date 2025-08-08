<x-filament::widget>
    <x-filament::card>
        <h2 class="text-xl font-bold mb-4">Ringkasan Penjualan Berdasarkan Role</h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
            <div class="bg-black shadow rounded-xl p-6 border">
                <p class="text-sm text-muted-foreground mb-1">Penjualan oleh Sales</p>
                <p class="text-2xl font-bold text-success">Rp {{ number_format($data['sales'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-black shadow rounded-xl p-6 border">
                <p class="text-sm text-muted-foreground mb-1">Penjualan oleh Reseller</p>
                <p class="text-2xl font-bold text-info">Rp {{ number_format($data['reseller'], 0, ',', '.') }}</p>
            </div>

            <div class="bg-black shadow rounded-xl p-6 border">
                <p class="text-sm text-muted-foreground mb-1">Penjualan oleh Customer</p>
                <p class="text-2xl font-bold text-primary">Rp {{ number_format($data['customer'], 0, ',', '.') }}</p>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
