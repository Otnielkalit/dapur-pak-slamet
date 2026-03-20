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
                        'x-init' => <<<'JS'
                            $nextTick(() => {
                                const focusCode = (opts = {}) => {
                                    const el = $refs.mealEntryScanCodeInput
                                    if (el && ! el.disabled) {
                                        el.focus({ preventScroll: true, ...opts })
                                    }
                                }
                                focusCode()
                                setTimeout(() => focusCode(), 50)
                                setTimeout(() => focusCode(), 200)
                                setTimeout(() => focusCode(), 600)
                            })
                        JS,
                        'x-on:meal-entry-scan-focus-code.window' => '$nextTick(() => $refs.mealEntryScanCodeInput?.focus({ preventScroll: true }))',
                        'x-on:meal-entry-scan-focus-price.window' => '$nextTick(() => $refs.mealEntryScanPriceInput?.focus({ preventScroll: true }))',
                        'x-on:livewire:navigated.window' => '$nextTick(() => { setTimeout(() => $refs.mealEntryScanCodeInput?.focus({ preventScroll: true }), 80) })',
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
