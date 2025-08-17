<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\SalesStockResource\Pages;
use App\Filament\Sales\Resources\SalesStockResource\RelationManagers;
use App\Models\sales_stocks;
use App\Models\SalesStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class SalesStockResource extends Resource
{
    protected static ?string $model = sales_stocks::class;
    protected static ?string $label = 'Stok';
    protected static ?string $navigationGroup = 'Manajemen Produk';

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

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
                TextColumn::make('stock_movement_id')
                    ->label('Id Stok Gudang'),
                TextColumn::make('sales.name')
                    ->label('Nama Sales'),
                TextColumn::make('productUnit.product.nama_produk')
                    ->searchable()
                    ->label('Nama Produk'),
                TextColumn::make('quantity')
                    ->label('jumah Produk'),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                    ]),
                TextColumn::make('productUnit.unit.nama_unit')
                    ->label('Satuan unit'),
                TextColumn::make('created_at')
                    ->label('Tanggal')


            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Tipe Transaksi')
                    ->options([
                        'in' => 'Masuk',
                        'out' => 'Keluar',
                    ])
                    ->default(null)
                    ->placeholder('Semua')
                    ]);
            // ->actions([
            //     Tables\Actions\DeleteAction::make()
            //         ->visible(fn(Model $record) => $record->status === 'out')
            //         ->after(function (Model $record) {
            //             if ($record->status === 'out') {
            //                 $productUnit = $record->productUnit;

            //                 if ($productUnit) {
            //                     // 1. Catat pengembalian di unit transaksi
            //                     sales_stocks::create([
            //                         'sales_id' => $record->sales_id,
            //                         'product_unit_id' => $productUnit->id,
            //                         'quantity' => $record->quantity,
            //                         'status' => 'in',
            //                         'order_id' => $record->order_id,
            //                     ]);

            //                     // 2. Cari unit tertinggi
            //                     $topUnit = $productUnit->product->productUnits
            //                         ->sortBy('conversion_rate')
            //                         ->first();

            //                     // 3. Konversi jumlah ke unit tertinggi
            //                     $convertedQty = $record->quantity / $productUnit->conversion_rate;

            //                     // 4. Tambahkan stok di unit tertinggi (insert "in" ke sales_stocks)
            //                     sales_stocks::create([
            //                         'sales_id' => $record->sales_id,
            //                         'product_unit_id' => $topUnit->id,
            //                         'quantity' => $convertedQty,
            //                         'status' => 'in',
            //                         'order_id' => $record->order_id,
            //                     ]);
            //                 }
            //             }
            //         })
            // ]);

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
            'index' => Pages\ListSalesStocks::route('/'),
            'create' => Pages\CreateSalesStock::route('/create'),
            'edit' => Pages\EditSalesStock::route('/{record}/edit'),
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
            ->where('sales_id', Auth::id());
    }

}
