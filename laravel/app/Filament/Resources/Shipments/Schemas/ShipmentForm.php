<?php

namespace App\Filament\Resources\Shipments\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ShipmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('provider_id')
                    ->relationship('provider', 'name'),
                TextInput::make('fulfilment_type')
                    ->required(),
                TextInput::make('tracking_number'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Textarea::make('shipping_address')
                    ->columnSpanFull(),
                DateTimePicker::make('shipped_at'),
                DateTimePicker::make('delivered_at'),
            ]);
    }
}
