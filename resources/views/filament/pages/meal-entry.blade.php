<x-filament-panels::page>
    <div
        x-data
        x-on:meal-entry-scan-focus-code.window="$nextTick(() => $refs.mealEntryScanCodeInput?.focus())"
        x-on:meal-entry-scan-focus-price.window="$nextTick(() => $refs.mealEntryScanPriceInput?.focus())"
    >
        {{ $this->content }}
    </div>
</x-filament-panels::page>
