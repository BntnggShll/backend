<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Manajemen Stok';
    protected static ?string $label = 'Riwayat Stok Gudang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_unit_id')
                    ->relationship('productUnit', 'id', fn ($query) => $query->whereNull('parent_id'))
                    ->getOptionLabelFromRecordUsing(fn($record) => "{$record->product->nama_produk} - {$record->unit->nama_unit}")
                    ->searchable(['product.nama_produk', 'unit.nama_unit'])
                    ->preload()
                    ->required()
                    ->reactive()
                    ->label('Produk dan Satuan'),
                Forms\Components\Select::make('type')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar'
                    ])
                    ->required()
                    ->reactive()
                    ->label('Tipe Gerakan'),
                Forms\Components\Select::make('sales_id')
                    ->options(
                        User::where('role', 'sales')->pluck('name', 'id')
                    )
                    ->label('Sales')
                    ->searchable()
                    ->preload()
                    ->required(fn(Get $get) => $get('type') === 'out')
                    ->visible(fn(Get $get) => $get('type') === 'out'),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->minValue(1) // Aturan sederhana, karena validasi utama ada di model
                    ->helperText('Masukkan jumlah stok. Validasi stok akan dilakukan saat menyimpan.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, Forms\Set $set) => $set('quantity', abs($state))),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('productUnit.product.nama_produk')
                    ->label('Produk')
                    ->searchable(),
                Tables\Columns\TextColumn::make('productUnit.unit.nama_unit')
                    ->label('Satuan')
                    ->badge(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->label('Kuantitas'),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'in',
                        'danger' => 'out',
                    ])->label('Tipe'),
                Tables\Columns\TextColumn::make('sales.name')
                    ->label('Nama Sales')
                    ->default('-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Waktu')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'in' => 'Stok Masuk',
                        'out' => 'Stok Keluar',
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
