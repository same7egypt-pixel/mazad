<?php

namespace App\Filament\Resources\Countries\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CountryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                TextInput::make('code')
                    ->required()
                    ->uppercase()
                    ->length(2)
                    ->unique(ignoreRecord: true),
                TextInput::make('timezone')
                    ->required()
                    ->maxLength(64),
                Select::make('currency_id')
                    ->relationship('currency', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('platform_commission_rate')
                    ->label('نسبة عمولة المنصة')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->step(0.01)
                    ->suffix('%')
                    ->helperText('تُحفظ هذه النسبة مع كل طلب جديد عند إغلاق مزاد ناجح، ولا تغيّر الطلبات السابقة.')
                    ->required(),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
