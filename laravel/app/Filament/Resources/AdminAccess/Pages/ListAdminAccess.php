<?php

namespace App\Filament\Resources\AdminAccess\Pages;

use App\Filament\Resources\AdminAccess\AdminAccessResource;
use Filament\Resources\Pages\ListRecords;

class ListAdminAccess extends ListRecords
{
    protected static string $resource = AdminAccessResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
