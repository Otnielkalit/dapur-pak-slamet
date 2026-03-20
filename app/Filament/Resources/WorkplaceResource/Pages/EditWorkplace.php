<?php

namespace App\Filament\Resources\WorkplaceResource\Pages;

use App\Filament\Resources\WorkplaceResource;
use Filament\Resources\Pages\EditRecord;

class EditWorkplace extends EditRecord
{
    protected static string $resource = WorkplaceResource::class;
    protected static ?string $title = 'Edit Tempat Kerja';
}

