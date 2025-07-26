<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\OrdersResource\Pages;
use App\Filament\Sales\Resources\OrdersResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;


class OrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


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
            TextColumn::make('id')
            ->label('Id Order'),
        TextColumn::make('orderItems.productunit.product.nama_produk')
            ->label('Barang Dikirim')
            ->limit(30)
            ->wrap(),
        TextColumn::make('user.name')
            ->label('Nama pembeli')
            ->searchable(),
        TextColumn::make('total_harga')
            ->label('Total Harga')
            ->prefix('Rp '),
        TextColumn::make('shipping_cost')
            ->label('Biaya Pengiriman')
            ->prefix('Rp '),
        TextColumn::make('payments.metode_pembayaran')
            ->label('Metode Pembayaran'),
        BadgeColumn::make('payments.status_pembayaran')
            ->colors([
                'warning' => 'menunggu',
                'info' => 'diproses',
                'success' => 'selesai',
                'danger' => 'gagal',
                'gray' => 'kadaluarsa',
            ])
            ->label('Status Pengiriman'),
        TextColumn::make('created_at')
            ->label('Dipesan'),
        TextColumn::make('updated_at')
            ->label('Diperbarui'),
        ])
            ->filters([
                //
            ])
            ->actions([
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
            'index' => Pages\ListOrders::route('/'),
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
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());   
    }
}
