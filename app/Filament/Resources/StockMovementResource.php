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
use Illuminate\Contracts\Validation\Rule;

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
                    ->rule(function (Get $get, $record) { // $record akan berisi model saat edit
                        return new class ($get, $record) implements Rule {
                            private $get;
                            private $record;

                            public function __construct(Get $get, $record)
                            {
                                $this->get = $get;
                                $this->record = $record;
                            }

                            // Logika utama validasi
                            public function passes($attribute, $value)
                            {
                                $get = $this->get;

                                if ($get('type') !== 'out') {
                                    return true;
                                }

                                $productUnitId = $get('product_unit_id');
                                if (!$productUnitId)
                                    return true;

                                // Ambil stok aktual dari database
                                $currentStock = \App\Models\Inventory::where('product_unit_id', $productUnitId)->first()?->quantity ?? 0;

                                // Ini adalah logika kuncinya
                                $effectiveAvailableStock = $currentStock;
                                // Jika ini adalah form EDIT ($this->record tidak null)
                                if ($this->record) {
                                    // Tambahkan kembali kuantitas lama dari record ini ke stok efektif
                                    $effectiveAvailableStock += abs($this->record->quantity);
                                }

                                // Bandingkan dengan stok efektif
                                if (abs($value) > $effectiveAvailableStock) {
                                    return false;
                                }

                                return true;
                            }

                            // Logika untuk pesan error
                            public function message()
                            {
                                $get = $this->get;
                                $productUnitId = $get('product_unit_id');
                                $currentStock = \App\Models\Inventory::where('product_unit_id', $productUnitId)->first()?->quantity ?? 0;

                                $effectiveAvailableStock = $currentStock;
                                if ($this->record) {
                                    $effectiveAvailableStock += abs($this->record->quantity);
                                }

                                if ($effectiveAvailableStock <= 0) {
                                    return 'Stok untuk produk ini sudah habis (0).';
                                }

                                return 'Jumlah keluar tidak boleh melebihi stok yang tersedia (' . $effectiveAvailableStock . ').';
                            }
                        };
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Id Stock Gudang'),
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