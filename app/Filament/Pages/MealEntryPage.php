<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\InteractsWithMealEntryScanForm;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form as FormLayout;
use Filament\Schemas\Schema;

class MealEntryPage extends Page
{
    use InteractsWithMealEntryScanForm;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Entry Data Makanan';

    protected static ?string $title = 'Entry Data Makanan';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.meal-entry';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                FormLayout::make([
                    EmbeddedSchema::make('mealEntryScanForm'),
                ])
                    ->id('meal-entry-form'),
            ]);
    }
}
