<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\ShipmentResource\Pages;
use App\Filament\Sales\Resources\ShipmentResource\RelationManagers;
use App\Models\Shipment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Notification;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

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
                TextColumn::make('order.user.name')
                ->label('Nama Cu    mer'),
                TextColumn::make('order.user.alamat')
                ->label('Alamat'),
                TextColumn::make('order.user.no_telp')
                ->label('No Telepone'),
                TextColumn::make('order.user.email')
                ->label('Email'),
                TextColumn::make('order.total_harga')
                ->label('Total Harga'),
                TextColumn::make('order.shipping_cost')
                ->label('Biaya Pengantaran'),
                TextColumn::make('perkiraan_pengiriman'),
                BadgeColumn::make('status_pengiriman')
                    ->colors([
                        'warning' => 'diproses',
                        'info' => 'dikirim',
                        'success' => 'diterima'
                    ]),
            ])
            ->filters([
                //
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
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $query->where('status_pengiriman', 'dikirim');
        $user = auth()->user();
        if ($user && $user->role === 'sales') {
            $query->where('sales_id', $user->id);
        }return $query;
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
