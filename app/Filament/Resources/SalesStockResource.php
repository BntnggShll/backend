<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesStockResource\Pages;
use App\Filament\Resources\SalesStockResource\RelationManagers;
use App\Models\sales_stocks;
use App\Models\SalesStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesStockResource extends Resource
{
    protected static ?string $model = sales_stocks::class;
    protected static ?string $label = 'Stok Sales';
    protected static ?string $navigationGroup = 'Manajemen Stok';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

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
                    ->label('Nama Produk'),
                TextColumn::make('quantity')
                    ->label('jumah Produk'),
                TextColumn::make('status'),
                TextColumn::make('stock_movement_id')
                    ->label('Id Stok Gudang'),


            ])
            ->filters([
                SelectFilter::make('sales')
                    ->relationship('sales', 'name', fn(Builder $query) => $query->where('role', 'sales'))
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
}
