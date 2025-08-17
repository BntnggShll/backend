<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\ProductResource\Pages;
use App\Filament\Sales\Resources\ProductResource\RelationManagers;
use App\Models\ProductSales;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class ProductResource extends Resource
{
    protected static ?string $model = ProductSales::class;
    protected static ?string $navigationGroup = 'Manajemen Produk';
    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

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
        ->modifyQueryUsing(function (Builder $query) {
            $query->whereHas('salesStocks'); // hanya produk yang punya stok
        })
            ->columns([
                ImageColumn::make('image')
                    ->label('Gambar')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->image)),
                TextColumn::make('nama_produk')
                    ->searchable(),
                TextColumn::make('jenis_produk')->badge(),
                TextColumn::make('productUnits.unit.nama_unit')
                    ->label('Satuan Tersedia')
                    ->listWithLineBreaks()
                    ->badge(),
                TextColumn::make('productUnits.harga_jual')
                    ->label('Harga Satuan')
                    ->listWithLineBreaks(),
                TextColumn::make('stok_saat_ini')
                    ->label('Stok Saat Ini')
                    ->getStateUsing(function (ProductSales $record) {
                        return $record->calculateSalesStockStatus()['display'];
                    })
                    ->description(function (ProductSales $record) {
                        $stockData = $record->calculateSalesStockStatus();
                        $baseUnitName = $record->productUnits()->where('is_base_unit', true)->first()->unit->nama_unit ?? 'satuan dasar';
                        return '(Total: ' . $stockData['net'] . ' ' . $baseUnitName . ')';
                    })

            ])
            ->filters([
                //
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
            'index' => Pages\ListProducts::route('/'),
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
