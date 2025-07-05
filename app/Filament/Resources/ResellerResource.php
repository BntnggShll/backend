<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResellerResource\Pages;
use App\Filament\Resources\ResellerResource\RelationManagers;
use App\Models\Reseller;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ResellerResource extends Resource
{
    protected static ?string $model = Reseller::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen User';

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
                    ->label('Nama'),
                TextColumn::make('no_hp'),
                TextColumn::make('nama_toko'),
                TextColumn::make('user.alamat')
                    ->label('Alamat'),
                    BadgeColumn::make('status')
                    ->colors([
                        'success' => 'aktif',
                        'danger' => 'tidak_aktif',
                    ])
                    ->formatStateUsing(function (string $state): string {
                        return match ($state) {
                            'aktif' => 'Aktif',
                            'tidak_aktif' => 'Tidak Aktif',
                            default => ucfirst($state),
                        };
                    }),
                TextColumn::make('created_at')->label('Tanggal bergabung'),
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
            'index' => Pages\ListResellers::route('/'),
            'create' => Pages\CreateReseller::route('/create'),
            'edit' => Pages\EditReseller::route('/{record}/edit'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }
}
