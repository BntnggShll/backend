<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\ProductResource\Pages;
use App\Filament\Sales\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
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
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->image)),
                Tables\Columns\TextColumn::make('nama_produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('jenis_produk')->badge(),
                Tables\Columns\TextColumn::make('productUnits.unit.nama_unit')
                    ->label('Satuan Tersedia')
                    ->listWithLineBreaks()
                    ->badge(),
                Tables\Columns\TextColumn::make('productUnits.harga_jual')
                    ->label('Harga Satuan')
                    ->listWithLineBreaks(),
                TextColumn::make('stok_saat_ini')
                    ->label('Stok Saat Ini')
                    ->getStateUsing(function (Product $record) {
                        return $record->calculateStock()['display'];
                    })
                    ->description(function (Product $record) {
                        $stockData = $record->calculateStock();
                        $baseUnitName = $record->productUnits()->where('is_base_unit', true)->first()->unit->nama_unit ?? 'satuan dasar';
                        return '(Total: ' . $stockData['total_in_base'] . ' ' . $baseUnitName . ')';
                    })
                    ->sortable(false),
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
