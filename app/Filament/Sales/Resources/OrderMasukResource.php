<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\OrderMasukResource\Pages;
use App\Filament\Sales\Resources\OrderMasukResource\RelationManagers;
use App\Models\OrderMasuk;
use Filament\Tables\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderMasukResource extends Resource
{
    protected static ?string $model = OrderMasuk::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_penerima'),
                TextColumn::make('nomor_telp'),
                TextColumn::make('catatan')
                    ->wrap(),
                TextColumn::make('alamat_pengantaran')
                    ->wrap(),
                TextColumn::make('perkiraan_pengiriman'),
                BadgeColumn::make('status_pengiriman')
                    ->colors([
                        'warning'=>'diproses',
                        'success'=>'diterima',
                        'info'=>'dikirim',
                    ]),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('Pengiriman')
                    ->label('Pengiriman')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->visible(condition: fn($record) => $record->status_pengiriman === 'diproses')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Pengiriman')
                    ->modalDescription('Lakukan Pengiriman')
                    ->modalSubmitActionLabel('Ya')
                    ->action(function ($record) {
                        $record->update([
                            'status_pengiriman' => 'dikirim',
                            'sales_id' => auth()->id(),
                        ]);

                        // Kirim notifikasi sukses
                        Notification::make()
                            ->title('Pembayaran Diterima')
                            ->body('Status pembayaran telah berhasil diubah menjadi "Selesai".')
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
            'index' => Pages\ListOrderMasuks::route('/'),
            'create' => Pages\CreateOrderMasuk::route('/create'),
            'edit' => Pages\EditOrderMasuk::route('/{record}/edit'),
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
    public static function getEloquentQuery(): Builder{
        return parent::getEloquentQuery()
            ->whereNull('sales_id' )
            ->where('status_pengiriman','diproses');
    }
}
