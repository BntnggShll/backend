<?php

namespace App\Filament\Resources\StockMovementResource\Pages;

use App\Filament\Resources\StockMovementResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Cek jika tipe transaksi adalah 'out' dan ada kuantitas
        if (isset($data['type']) && $data['type'] === 'out' && isset($data['quantity'])) {
            // Ubah kuantitas menjadi nilai negatif absolutnya
            $data['quantity'] = -abs((int)$data['quantity']);
        } else if (isset($data['quantity'])) {
            // Untuk kasus lain (seperti 'in'), pastikan nilainya selalu positif
             $data['quantity'] = abs((int)$data['quantity']);
        }

        // Kembalikan array data yang sudah dimodifikasi
        return $data;
    }
}
