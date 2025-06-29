<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShipmentResource\Pages;
use App\Filament\Resources\ShipmentResource\RelationManagers;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Manajemen Produk';

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
                TextColumn::make('order.orderItems.product.nama_produk')
                    ->label('Barang Dikirim')
                    ->limit(30)
                    ->wrap()
                    ->searchable(),
                TextColumn::make('order.user.name')
                    ->label('Nama pembeli')
                    ->searchable(),
                TextColumn::make('order.total_harga')
                    ->label('Total harga'),
                TextColumn::make('perkiraan_pengiriman'),
                BadgeColumn::make('status_pengiriman')
                    ->colors([
                        'diproses' => 'warning',
                        'dikirim' => 'info',
                        'diterima' => 'success',
                    ]),
                TextColumn::make('created_at')
                    ->label('Dibuat'),
                TextColumn::make('updated_at')
                    ->label('Diperbarui'),
            ])
            ->filters([
                SelectFilter::make('status_pengiriman')
                    ->options([
                        'diproses' => 'Diproses',
                        'dikirim' => 'Dikirim',
                        'diterima' => 'Diterima',
                    ])
            ])
            ->actions([
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
            'index' => Pages\ListShipments::route('/'),
            'create' => Pages\CreateShipment::route('/create'),
            'edit' => Pages\EditShipment::route('/{record}/edit'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }
}
