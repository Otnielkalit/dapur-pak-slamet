<?php

namespace App\Filament\Resources\MealEntryResource\Pages;

use App\Filament\Concerns\InteractsWithMealEntryScanForm;
use App\Filament\Resources\MealEntryResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\Form as FormLayout;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Schema;
use Filament\View\PanelsRenderHook;

class ListMealEntries extends ListRecords
{
    use InteractsWithMealEntryScanForm;

    protected static string $resource = MealEntryResource::class;

    protected static ?string $title = 'Entry Makanan';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getTabsContentComponent(),
                FormLayout::make([
                    EmbeddedSchema::make('mealEntryScanForm'),
                ])
                    ->id('meal-entry-scan-inline')
                    ->extraAttributes([
                        'class' => 'mb-6',
                        'x-data' => '',
                        'x-on:meal-entry-scan-focus-code.window' => '$nextTick(() => $refs.mealEntryScanCodeInput?.focus())',
                        'x-on:meal-entry-scan-focus-price.window' => '$nextTick(() => $refs.mealEntryScanPriceInput?.focus())',
                    ]),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make(),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    protected function afterMealEntryScanCreated(): void
    {
        $this->flushCachedTableRecords();
    }
}
