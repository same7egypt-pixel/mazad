<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->required(),
                Select::make('city_id')
                    ->relationship('city', 'name')
                    ->required(),
                Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->required(),
                TextInput::make('title')
                    ->required(),
                Textarea::make('description')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('condition')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('draft'),
                DateTimePicker::make('approved_at'),
                TextInput::make('approved_by')
                    ->numeric(),
            ]);
    }
}
