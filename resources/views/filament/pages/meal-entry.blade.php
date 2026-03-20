<x-filament-panels::page>
    <div
        x-data="{}"
        x-init="$nextTick(() => {
            const focusById = (id) => {
                const el = document.getElementById(id)
                if (el && ! el.disabled) el.focus({ preventScroll: true })
            }
            const run = () => focusById('meal-entry-scan-code-input')
            run()
            ;[50, 200, 600, 1200].forEach((ms) => setTimeout(run, ms))
        })"
        x-on:meal-entry-scan-focus-code.window="$nextTick(() => {
            const run = () => document.getElementById('meal-entry-scan-code-input')?.focus({ preventScroll: true })
            run()
            ;[0, 50, 150, 400].forEach((ms) => setTimeout(run, ms))
        })"
        x-on:meal-entry-scan-focus-price.window="$nextTick(() => {
            const run = () => document.getElementById('meal-entry-scan-price-input')?.focus({ preventScroll: true })
            run()
            ;[0, 50, 150, 400].forEach((ms) => setTimeout(run, ms))
        })"
        x-on:livewire:navigated.window="$nextTick(() => {
            const run = () => document.getElementById('meal-entry-scan-code-input')?.focus({ preventScroll: true })
            setTimeout(run, 80)
            ;[200, 500].forEach((ms) => setTimeout(run, ms))
        })"
    >
        {{ $this->content }}
    </div>
</x-filament-panels::page>
