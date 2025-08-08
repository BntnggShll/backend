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
                TextColumn::make('sales.name')
                    ->label('Nama Sales'),
                TextColumn::make('productUnit.product.nama_produk')
                    ->searchable()
                    ->label('Nama Produk'),
                TextColumn::make('quantity')
                    ->label('jumah Produk'),
                TextColumn::make('status'),
                TextColumn::make('stock_movement_id')
                    ->label('Id Stok Gudang'),


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
