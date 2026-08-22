<?php

namespace App\Filament\Resources\Withdrawals\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class WithdrawalForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('wallet_id')
                    ->relationship('wallet', 'id')
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('requested'),
                TextInput::make('destination_type')
                    ->required(),
                Textarea::make('destination_details')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('reviewed_by')
                    ->numeric(),
                DateTimePicker::make('processed_at'),
            ]);
    }
}
