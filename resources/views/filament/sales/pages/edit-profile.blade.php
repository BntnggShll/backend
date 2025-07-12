<x-filament-panels::page>
    {{-- Form ini akan memanggil method `save` saat di-submit --}}
    <form wire:submit="save">
        {{-- Baris ini akan secara ajaib merender semua field yang kita definisikan di method form() --}}
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Simpan Perubahan
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>