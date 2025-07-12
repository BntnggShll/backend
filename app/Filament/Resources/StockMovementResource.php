<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Validation\Rule;

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
                    ->reactive(),
                Forms\Components\Select::make('sales_id')
                    ->options(
                        User::where('role', 'sales')->pluck('name', 'id')
                    )
                    ->label('Sales')
                    ->searchable(['stock_sales.name'])
                    ->preload()
                    ->required()
                    ->visible(fn($get) => $get('type') === 'out'),
                Forms\Components\TextInput::make('quantity')
                    ->required()
                    ->numeric()
                    ->helperText('Masukkan jumlah stok. Sistem akan menyesuaikan otomatis.')
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, $set) => $set('quantity', abs($state)))
                    ->rule(function ($get) {
                        return new class($get) implements Rule {
                            private $get;
    
                            public function __construct($get)
                            {
                                $this->get = $get;
                            }
    
                            public function passes($attribute, $value)
                            {
                                $get = $this->get;
    
                                // 1. Hanya jalankan validasi ini jika tipe transaksi adalah 'out'
                                if ($get('type') !== 'out') {
                                    return true;
                                }
    
                                $productUnitId = $get('product_unit_id');
                                if (!$productUnitId) {
                                    return true; // Biarkan rule 'required' yang menangani ini
                                }
    
                                // 2. Ambil stok saat ini dari tabel ringkasan 'inventories'
                                $currentStock = \App\Models\Inventory::where('product_unit_id', $productUnitId)
                                                    ->first()?->quantity ?? 0;
    
                                // 3. Cek jika stok yang diminta melebihi yang tersedia
                                if (abs($value) > $currentStock) {
                                    return false; // Gagal validasi
                                }
    
                                return true; // Lolos validasi
                            }
    
                            public function message()
                            {
                                $get = $this->get;
                                $productUnitId = $get('product_unit_id');
                                $currentStock = \App\Models\Inventory::where('product_unit_id', $productUnitId)
                                                    ->first()?->quantity ?? 0;
    
                                if ($currentStock <= 0) {
                                    return 'Stok untuk produk ini sudah habis (0).';
                                }
    
                                return 'Jumlah keluar tidak boleh melebihi stok saat ini (' . $currentStock . ').';
                            }
                        };
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ]),
                Tables\Columns\TextColumn::make('stock_sales.name')
                    ->label('Nama Sales'),
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