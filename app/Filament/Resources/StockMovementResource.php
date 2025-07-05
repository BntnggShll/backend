<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Manajemen Produk';
    protected static ?string $label = 'Riwayat Stok';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_unit_id')
                    ->relationship('productUnit', 'id') 
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->product->nama_produk} - {$record->unit->nama_unit}")
                    ->searchable(['product.nama_produk', 'unit.nama_unit'])
                    ->preload()
                    ->required()
                    ->label('Produk dan Satuan'),

                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->helperText('Gunakan angka negatif (-) untuk stok keluar.'),

                Forms\Components\Select::make('type')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar'
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('productUnit.product.nama_produk')
                    ->label('Produk')
                    ->searchable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('productUnit.unit.nama_unit')
                    ->label('Satuan')
                    ->badge(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Waktu'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'in' => 'In',
                        'out' => 'Out',
                    ])
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
            'edit' => Pages\EditStockMovement::route('/{record}/edit'),
        ];
    }    
}