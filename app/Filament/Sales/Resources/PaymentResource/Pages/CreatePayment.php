<?php

namespace App\Filament\Sales\Resources\PaymentResource\Pages;

use App\Filament\Sales\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;
}
