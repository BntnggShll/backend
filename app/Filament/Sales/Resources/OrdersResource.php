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

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $label = 'Pesanan';

    protected static ?string $navigationGroup = 'Manajemen Pesanan';
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
                TextColumn::make('order_number')
                    ->label('Id Order'),
                TextColumn::make('orderItems.productunit.product.nama_produk')
                    ->label('Barang Dikirim')
                    ->limit(30)
                    ->badge(),
                TextColumn::make('orderItems')
                    ->label('Jumlah')
                    ->getStateUsing(function ($record) {
                        // Pastikan relasi orderItems ada
                        return $record->orderItems->map(function ($item) {
                            return $item->jumlah . ' ' . $item->productunit->unit->nama_unit;
                        })->join('<br>');
                    })
                    ->html()
                    ->color(fn($record) => $record->orderItems->count() > 1 ? 'success' : 'gray'),


                TextColumn::make('user.name')
                    ->label('Nama pembeli')
                    ->searchable(),
                TextColumn::make('total_harga')
                    ->label('Total Harga'),
                TextColumn::make('shipping_cost')
                    ->label('Biaya Pengiriman'),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'selesai',
                        'warning' => 'diproses',
                        'info' => 'dibayar',
                        'gray' => 'dikirim',
                        'danger' => 'batal',
                    ]),
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
                    ->label('Status Pembayaran'),
                TextColumn::make('created_at')
                    ->label('Dipesan'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui'),
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
