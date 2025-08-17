<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\ShipmentResource\Pages;
use App\Filament\Sales\Resources\ShipmentResource\RelationManagers;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $label = 'Pengiriman';
    protected static ?string $navigationGroup = 'Manajemen Pengiriman';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_penerima')
                    ->searchable(),
                TextColumn::make('nomor_telp'),
                TextColumn::make('catatan')
                    ->wrap(),
                TextColumn::make('alamat_pengantaran')
                    ->wrap(),
                TextColumn::make('perkiraan_pengiriman'),
                TextColumn::make('order.orderItems.productunit.product.nama_produk')
                    ->label('Nama Produk')
                    ->wrap(),
                TextColumn::make('order.orderItems.jumlah')
                    ->label('Jumlah Barang'),
                TextColumn::make('order.orderItems.productunit.unit.nama_unit')
                    ->label('Satuan'),
                TextColumn::make('order.orderItems.productunit.harga_jual')
                    ->label('Harga Satuan'),
                TextColumn::make('order.shipping_cost')
                    ->label('Biaya Pengiriman'),
                TextColumn::make('order.total_harga')
                    ->label('Total Pembayaran'),
                BadgeColumn::make('status_pengiriman')
                    ->colors([
                        'warning' => 'diproses',
                        'success' => 'diterima',
                        'info' => 'dikirim',
                    ]),
                BadgeColumn::make('order.payments.status_pembayaran')
                    ->label('Status Pembayaran')
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'diproses',
                        'gray' => 'kadarluasa',
                        'danger' => 'gagal',
                        'success' => 'selesai',
                    ]),
                TextColumn::make('order.payments.metode_pembayaran')
                    ->label('Metode Pembayaran'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('Terima Pembayaran')
                    ->label('Terima Pembayaran')
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->visible(condition: fn($record) => $record->order?->payments?->metode_pembayaran === 'Cash')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->modalDescription('Menerima pembayaran dari pelanggan')
                    ->modalSubmitActionLabel('Ya')
                    ->action(function ($record) {
                        $record->order?->payments?->update(['status_pembayaran' => 'menunggu']);
                        $record->order?->update(['status' => 'selesai']);
                        $record->update([
                            'status_pengiriman' => 'diterima',
                        ]);

                        // Kirim notifikasi sukses
                        Notification::make()
                            ->title('Pembayaran Diterima')
                            ->body('Status pembayaran telah diterima.')
                            ->success()
                            ->send();
                    }),
                Action::make('Selesaikan pengiriman')
                    ->label('Selesaikan pengiriman')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->visible(condition: fn($record) => $record->order?->payments?->metode_pembayaran !== 'Cash' && !is_null($record->order?->payments))
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi pengiriman selesai')
                    ->modalDescription('Produk sudah di kirim ke penerima')
                    ->modalSubmitActionLabel('Ya')
                    ->action(function ($record) {
                        $record->order?->update(['status' => 'selesai']);
                        $record->update([
                            'status_pengiriman' => 'diterima',
                        ]);

                        // Kirim notifikasi sukses
                        Notification::make()
                            ->title('Pengiriman selesai')
                            ->body('Produk sudah berhasil di antarkan.')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $query->where('status_pengiriman', 'dikirim');
        $user = auth()->user();
        if ($user && $user->role === 'sales') {
            $query->where('sales_id', $user->id);
        }
        return $query;
    }
    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
