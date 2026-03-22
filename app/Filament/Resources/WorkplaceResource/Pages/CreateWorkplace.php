<?php

namespace App\Filament\Resources\WorkplaceResource\Pages;

use App\Filament\Resources\WorkplaceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkplace extends CreateRecord
{
    protected static string $resource = WorkplaceResource::class;

    protected static ?string $title = 'Tambah Tempat Kerja';

    protected function getRedirectUrl(): string
    {
        return $this->getResourceUrl('index');
    }
}
