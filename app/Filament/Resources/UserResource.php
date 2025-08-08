<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';
    protected static ?string $navigationGroup = 'Manajemen Pengguna';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('no_telp')
                    ->label('No Telepone'),
                TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('alamat')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->sortable()
                    ->label('Tanggal bergabung')
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'customer' => 'Customer',
                        'reseller' => 'Reseller',
                    ])
            ])
            ->actions([
                Action::make('Terima Reseller')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(User $record) => $record->reseller?->status === 'proses')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan')
                    ->modalDescription('Apakah Anda yakin menerima pendaftaran reseller ini?')
                    ->modalSubmitActionLabel('Ya, Terima')
                    ->action(function (User $record) {
                        $record->reseller?->update(['status' => 'terima']);
                        $record->update(['role' => 'reseller']);
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Reseller Diterima')
                            ->body('Pendaftaran reseller telah berhasil diterima.')
                    ),

                Action::make('Tolak Reseller')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(User $record) => $record->reseller?->status === 'proses')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penolakan')
                    ->modalDescription('Apakah Anda yakin menolak pendaftaran reseller ini? Peran pengguna akan dikembalikan ke customer.')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->action(function (User $record) {
                        $record->reseller?->update(['status' => 'tolak']);
                        

                        Notification::make()
                            ->success()
                            ->title('Reseller Ditolak')
                            ->body('Pendaftaran reseller telah ditolak dan perannya dikembalikan ke customer.')
                            ->send();
                    }),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
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
            ->where('role', 'customer');
    }



}
