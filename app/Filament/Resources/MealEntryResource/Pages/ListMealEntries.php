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
                        'x-data' => '{}',
                        'x-init' => <<<'JS'
                            $nextTick(() => {
                                const lockTableSearchTabOrder = () => {
                                    // Agar scanner/tab tidak lari ke kotak search tabel,
                                    // sementara kita memaksa fokus ke input scan/kode.
                                    const nodes = document.querySelectorAll(
                                        'input[type=search], input[name=tableSearch], input[wire\\:model*=tableSearch]'
                                    )

                                    nodes.forEach((el) => el?.setAttribute('tabindex', '-1'))
                                }

                                lockTableSearchTabOrder()

                                const focusById = (id) => {
                                    const el = document.getElementById(id)
                                    if (el && ! el.disabled) {
                                        el.focus({ preventScroll: true })
                                    }
                                }
                                const codeId = 'meal-entry-scan-code-input'
                                const run = () => focusById(codeId)
                                run()
                                ;[50, 200, 600, 1200].forEach((ms) => setTimeout(run, ms))
                            })
                        JS,
                        'x-on:meal-entry-scan-focus-code.window' => <<<'JS'
                            $nextTick(() => {
                                const lockTableSearchTabOrder = () => {
                                    const nodes = document.querySelectorAll(
                                        'input[type=search], input[name=tableSearch], input[wire\\:model*=tableSearch]'
                                    )

                                    nodes.forEach((el) => el?.setAttribute('tabindex', '-1'))
                                }

                                lockTableSearchTabOrder()

                                const run = () => document.getElementById('meal-entry-scan-code-input')?.focus({ preventScroll: true })
                                run()
                                ;[0, 50, 150, 400].forEach((ms) => setTimeout(run, ms))
                            })
                        JS,
                        'x-on:meal-entry-scan-focus-price.window' => <<<'JS'
                            $nextTick(() => {
                                const lockTableSearchTabOrder = () => {
                                    const nodes = document.querySelectorAll(
                                        'input[type=search], input[name=tableSearch], input[wire\\:model*=tableSearch]'
                                    )

                                    nodes.forEach((el) => el?.setAttribute('tabindex', '-1'))
                                }

                                lockTableSearchTabOrder()

                                const run = () => document.getElementById('meal-entry-scan-price-input')?.focus({ preventScroll: true })
                                run()
                                ;[0, 50, 150, 400].forEach((ms) => setTimeout(run, ms))
                            })
                        JS,
                        'x-on:livewire:navigated.window' => <<<'JS'
                            $nextTick(() => {
                                const lockTableSearchTabOrder = () => {
                                    const nodes = document.querySelectorAll(
                                        'input[type=search], input[name=tableSearch], input[wire\\:model*=tableSearch]'
                                    )

                                    nodes.forEach((el) => el?.setAttribute('tabindex', '-1'))
                                }

                                lockTableSearchTabOrder()

                                const run = () => document.getElementById('meal-entry-scan-code-input')?.focus({ preventScroll: true })
                                setTimeout(run, 80)
                                ;[200, 500].forEach((ms) => setTimeout(run, ms))
                            })
                        JS,
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
