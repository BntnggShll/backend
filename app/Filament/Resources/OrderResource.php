<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Manajemen Produk';
    protected static ?string $navigationLabel = 'Pesanan';

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
                TextColumn::make('shipment.sales.name')
                    ->label('Pengirim'),
                TextColumn::make('shipment.perkiraan_pengiriman')
                    ->label('Perkiraan Pengiriman'),
                BadgeColumn::make('shipment.status_pengiriman')
                    ->colors([
                        'warning' => 'diproses',
                        'info' => 'dikirim',
                        'success' => 'diterima',
                    ])
                    ->label('Status Pengiriman'),
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
            ])
            ->filters([
                Filter::make('user_name')
                    ->form([
                        TextInput::make('name')->label('Nama Pembeli'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query->when(
                            $data['name'],
                            fn($query, $name) => $query->whereHas('user', fn($q) => $q->where('name', 'like', "%{$name}%"))
                        );
                    }),

                // Filter berdasarkan rentang tanggal (created_at)
                Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('Dari Tanggal'),
                        DatePicker::make('until')->label('Sampai Tanggal'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn($q, $from) => $q->whereDate('created_at', '>=', $from))
                            ->when($data['until'], fn($q, $until) => $q->whereDate('created_at', '<=', $until));
                    }),
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
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
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
