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
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Notifications\Notification;

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
                TextColumn::make('no_telp')
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
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'proses' => 'Proses',
                        'terima' => 'Terima',
                        'tolak' => 'Tolak',
                    ])
            ])
            ->actions([
                Action::make('Terima Reseller')
                    ->label('Terima')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => $record->status === 'proses')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan')
                    ->modalDescription('Apakah Anda yakin menerima pendaftaran reseller ini?')
                    ->modalSubmitActionLabel('Ya, Terima')
                    ->action(function ($record) {
                        $record->update(['status' => 'terima']);
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
                    ->visible(fn($record) => $record->status === 'proses')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penolakan')
                    ->modalDescription('Apakah Anda yakin menolak pendaftaran reseller ini? Peran pengguna akan dikembalikan ke customer.')
                    ->modalSubmitActionLabel('Ya, Tolak')
                    ->action(function (Model $record) {
                        if (!$record->user) {
                            Notification::make()
                                ->danger()
                                ->title('Aksi Gagal')
                                ->body('Relasi ke data pengguna tidak ditemukan.')
                                ->send();
                            return; 
                        }

                        $user = $record->user;
                        $user->role = 'customer';
                        $user->save();

                        $record->update(['status' => 'tolak']);

                        Notification::make()
                            ->success()
                            ->title('Reseller Ditolak')
                            ->body('Pendaftaran reseller telah ditolak dan perannya dikembalikan ke customer.')
                            ->send();
                    }),
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
    
    /**
     * PERBAIKAN: Query diubah untuk menampilkan pendaftar dengan status 'proses'
     * ATAU yang perannya sudah 'reseller'.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        ->join('users', 'resellers.user_id', '=', 'users.id')
        ->where('users.role', 'reseller')
        ->select('resellers.*');
    }
}
