<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Manajemen Pembayaran';
    protected static ?string $navigationLabel = 'Pembayaran';

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
                TextColumn::make('order.id')
                    ->label('Id Order'),
                TextColumn::make('order.orderItems.productunit.product.nama_produk')
                    ->label('Produk')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('order.orderItems.productunit.product', function ($q) use ($search) {
                            $q->where('nama_produk', 'like', "%{$search}%");
                        });
                    })
                    ->badge(),
                TextColumn::make('order.user.name')
                    ->label('Pelanggan')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('order.user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('total_pembayaran'),
                TextColumn::make('metode_pembayaran'),
                BadgeColumn::make('status_pembayaran')
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'diproses',
                        'success' => 'selesai',
                        'danger' => 'gagal',
                        'gray' => 'kadarluarsa',
                    ]),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),

            ])
            ->filters([

            ])
            ->actions([
                Action::make('accept_payment')
                    ->label('Terima pembayaran')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    // Hanya tampilkan tombol ini jika statusnya 'menunggu'
                    ->visible(condition: fn($record) => $record->status_pembayaran === 'menunggu' && $record->order->payments->metode_pembayaran === 'Cash')
                    // Minta konfirmasi dari user
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pembayaran')
                    ->modalDescription('Apakah Anda yakin ingin menerima pembayaran ini dan mengubah status menjadi "Selesai"?')
                    ->modalSubmitActionLabel('Ya, Terima Pembayaran')
                    // Logika yang akan dijalankan saat tombol dikonfirmasi
                    ->action(function ($record) {
                        $record->update([
                            'status_pembayaran' => 'selesai'
                        ]);

                        // Kirim notifikasi sukses
                        Notification::make()
                            ->title('Pembayaran Diterima')
                            ->body('Status pembayaran telah diterima.')
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
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
