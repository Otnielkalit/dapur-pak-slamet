<x-filament-panels::page>
    <div
        x-data
        x-init="$nextTick(() => {
            const focusCode = () => $refs.mealEntryScanCodeInput?.focus({ preventScroll: true })
            focusCode()
            setTimeout(focusCode, 50)
            setTimeout(focusCode, 200)
        })"
        x-on:meal-entry-scan-focus-code.window="$nextTick(() => $refs.mealEntryScanCodeInput?.focus({ preventScroll: true }))"
        x-on:meal-entry-scan-focus-price.window="$nextTick(() => $refs.mealEntryScanPriceInput?.focus({ preventScroll: true }))"
        x-on:livewire:navigated.window="$nextTick(() => setTimeout(() => $refs.mealEntryScanCodeInput?.focus({ preventScroll: true }), 80))"
    >
        {{ $this->content }}
    </div>
</x-filament-panels::page>
