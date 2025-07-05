<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Tables\Actions\Action;;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use function Laravel\Prompts\multisearch;

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
                TextColumn::make('order.orderItems.product.nama_produk')->searchable(),
                TextColumn::make('order.user.name')->searchable(),
                TextColumn::make('jumlah'),
                TextColumn::make('metode_pembayaran'),
                BadgeColumn::make('status_pembayaran')
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'diproses',
                        'success' => 'selesai',
                        'danger' => '',
                    ]),
                TextColumn::make('tanggal_transaksi'),
                TextColumn::make('updated_at'),
                
            ])
            ->filters([
               
            ])
            ->actions([
                Action::make('accept_payment')
                ->label('Accept Payment')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                // Hanya tampilkan tombol ini jika statusnya 'menunggu'
                ->visible(condition: fn ($record) => $record->status_pembayaran === 'menunggu')
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
                        ->body('Status pembayaran telah berhasil diubah menjadi "Selesai".')
                        ->success()
                        ->send();
                }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
}
