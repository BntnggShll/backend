<?php

namespace App\Filament\Sales\Resources;

use App\Filament\Sales\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Tables\Actions\Action;
;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use function Laravel\Prompts\multisearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Manajemen Pesanan';
    protected static ?string $navigationLabel = 'Pembayaran';

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
                TextColumn::make('order.id')
                    ->label('Id Order'),
                TextColumn::make('order.orderItems.productunit.product.nama_produk')
                    ->label('Produk')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('order.orderItems.productunit.product', function ($q) use ($search) {
                            $q->where('nama_produk', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('order.user.name')
                    ->label('Pelanggan')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->orWhereHas('order.user', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('total_pembayaran'),
                TextColumn::make('metode_pembayaran'),
                BadgeColumn::make('status_pembayaran')
                    ->colors([
                        'warning' => 'menunggu',
                        'info' => 'diproses',
                        'success' => 'selesai',
                        'danger' => 'gagal',
                        'gray' => 'kadarluarsa',
                    ]),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at'),

            ])
            ->filters([

            ])
            ->actions([
                
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
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
            ->whereHas('order', function (Builder $query) {
                $query->where('user_id', Auth::id());
            });
    }
}
