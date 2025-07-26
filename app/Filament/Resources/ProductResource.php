<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Manajemen Produk';
    protected static ?string $navigationLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('nama_produk')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('jenis_produk')
                            ->options([
                                'obat herbal' => 'Obat Herbal',
                                'obat komersial' => 'Obat Komersial',
                            ])
                            ->required(),
                        Forms\Components\FileUpload::make('image')
                            ->label('Gambar Produk')
                            ->image()
                            ->imagePreviewHeight('150')
                            ->directory('produk')
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp']),
                    ])->columns(2),

                Forms\Components\Section::make('Satuan dan Harga Bertingkat')
                    ->description('Urutkan dari satuan terbesar ke terkecil. Satuan pertama akan menjadi induk.')
                    ->schema([
                        Forms\Components\Repeater::make('productUnits')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('unit_id')
                                    ->relationship('unit', 'nama_unit')
                                    ->required()
                                    ->label('Satuan')
                                    ->searchable()
                                    ->preload(),
                                Forms\Components\TextInput::make('conversion_rate')
                                    ->required()
                                    ->numeric()
                                    ->label('Konversi ke Induk')
                                    ->helperText('Berapa banyak satuan ini utk 1 satuan di atasnya. Isi 1 untuk satuan terbesar.')
                                    ->default(1),
                                Forms\Components\TextInput::make('harga_jual')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp'),
                                Forms\Components\Toggle::make('is_base_unit')
                                    ->label('Ini Satuan Terkecil?')
                                    ->helperText('Aktifkan jika ini adalah satuan paling dasar (e.g., butir, pcs).')
                                    ->reactive(),
                                Forms\Components\TextInput::make('min_stock_level')
                                    ->numeric()
                                    ->label('Stok Minimum')
                                    ->visible(fn($get) => $get('is_base_unit') === true)

                            ])
                            ->columns(4)
                            ->orderColumn('id')
                            ->cloneable()
                            ->addActionLabel('Tambah Tingkatan Satuan')
                            ->reorderable(false)
                    ]),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Gambar')
                    ->getStateUsing(fn($record) => asset('storage/' . $record->image)),
                Tables\Columns\TextColumn::make('nama_produk')->searchable(),
                Tables\Columns\TextColumn::make('jenis_produk')->badge(),
                Tables\Columns\TextColumn::make('productUnits.unit.nama_unit')
                    ->label('Satuan Tersedia')
                    ->listWithLineBreaks()
                    ->badge(),
                Tables\Columns\TextColumn::make('productUnits.harga_jual')
                    ->label('Harga Satuan')
                    ->listWithLineBreaks()
                    ->badge(),
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
        ;
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
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
