<?php

namespace App\Filament\Resources\Auctions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AuctionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'title')
                    ->required(),
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->required(),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                TextInput::make('starting_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('current_price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('reserve_price')
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('minimum_increment')
                    ->required()
                    ->numeric(),
                DateTimePicker::make('start_time')
                    ->required(),
                DateTimePicker::make('end_time')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('upcoming'),
                Select::make('winner_id')
                    ->relationship('winner', 'name'),
                TextInput::make('bid_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('version')
                    ->required()
                    ->numeric()
                    ->default(0),
            ]);
    }
}
