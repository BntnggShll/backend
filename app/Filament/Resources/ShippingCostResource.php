<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ShippingCostResource\Pages;
use App\Filament\Resources\ShippingCostResource\RelationManagers;
use App\Models\ShippingRates;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ShippingCostResource extends Resource
{
    protected static ?string $model = ShippingRates::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $label = 'Biaya Pengiriman';
    protected static ?string $navigationLabel = 'Pengiriman';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nama_daerah'),
                TextInput::make('cost')
                    ->label('Biaya')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama_daerah')
                    ->label('Nama Kecematan')
                    ->searchable(),
                TextColumn::make('cost')
                    ->label('Biaya'),
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
            'index' => Pages\ListShippingCosts::route('/'),
            'create' => Pages\CreateShippingCost::route('/create'),
            'edit' => Pages\EditShippingCost::route('/{record}/edit'),
        ];
    }
}
