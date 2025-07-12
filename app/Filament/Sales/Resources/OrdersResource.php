<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\OrdersResource\Pages;
use App\Filament\Sales\Resources\OrdersResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;


class OrdersResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


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
            TextColumn::make('user.name')
                ->label('Nama Pelanggan')
                ->searchable(),

            TextColumn::make('orderItems')
                ->label('Detail Pesanan')
                ->getStateUsing(function (\App\Models\Order $record): string {
                    // Di sini, kita menerima $record, yaitu seluruh objek Order untuk baris ini.
                    // Kita bisa dengan bebas mengakses relasi orderItems dari sini.
                    return $record->orderItems->map(function ($item) {
                        // Path yang benar sesuai kode Anda sebelumnya
                        $productName = $item->productunit->product->nama_produk ?? 'N/A';
                        $unitName = $item->productunit->unit->nama_unit ?? '';
                        $quantity = $item->jumlah ?? 'N/A';
                        $price = number_format($item->harga ?? 0, 0, ',', '.');
                        
                        return "• {$quantity} {$unitName} - {$productName} Rp {$price}";
                    })->implode('<br>');
                })
                ->html()
                ->searchable(query: function (Builder $query, string $search): Builder {
                    // PERBAIKAN 2: Path di whereHas juga disesuaikan
                    return $query->whereHas('orderItems.productunit.product', function ($query) use ($search) {
                        $query->where('nama_produk', 'like', "%{$search}%");
                    });
                }),

            TextColumn::make('total_harga')
                ->label('Total Harga')
                ->numeric(
                    decimalPlaces: 0,
                    decimalSeparator: ',',
                    thousandsSeparator: '.'
                )
                ->prefix('Rp '),
        ])
            ->filters([
                //
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
            'index' => Pages\ListOrders::route('/'),
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
        return parent::getEloquentQuery()->with([
            'user', 
            'orderItems.productunit.product',
            'orderItems.productunit.unit' 
        ]);
    }
}
