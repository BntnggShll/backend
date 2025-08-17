<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ResellerResource\Pages;
use App\Models\Reseller;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\SelectFilter;
 

class ResellerResource extends Resource
{
    protected static ?string $model = Reseller::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form tidak diperlukan karena canCreate dan canEdit false
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Nama')
                    ->searchable(),
                TextColumn::make('user.no_telp')
                    ->label('No Telepon')
                    ->searchable(),
                TextColumn::make('nama_toko')
                    ->searchable(),
                TextColumn::make('user.alamat')
                    ->label('Alamat')
                    ->searchable(),
                BadgeColumn::make('status')
                    ->colors([
                        'success' => 'terima',
                        'danger' => 'tolak',
                        'warning' => 'proses',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
                TextColumn::make('created_at')
                    ->label('Tanggal Bergabung')
                    ->dateTime('d M Y')
                    ->sortable(),
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
        return parent::getEloquentQuery()
        ->join('users', 'resellers.user_id', '=', 'users.id')
        ->where('users.role', 'reseller')
        ->select('resellers.*');
    }
}
