<?php

namespace App\Filament\Concerns;

use App\Models\Customer;
use App\Models\MealEntry;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

trait InteractsWithMealEntryScanForm
{
    public const MEAL_ENTRY_SCAN_CODE_INPUT_ID = 'meal-entry-scan-code-input';

    public const MEAL_ENTRY_SCAN_PRICE_INPUT_ID = 'meal-entry-scan-price-input';

    /**
     * @var array<string, string>
     */
    public array $mealEntryScanData = [
        'code' => '',
        'price' => '',
        'customer_code' => '',
        'customer_name' => '',
        'customer_phone' => '',
        'workplace_name' => '',
    ];

    public bool $mealEntryScanCustomerLoaded = false;

    public ?int $mealEntryScanCustomerId = null;

    public function defaultMealEntryScanForm(Schema $schema): Schema
    {
        return $schema->statePath('mealEntryScanData');
    }

    public function mealEntryScanForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Scan / ketik kode')
                    ->compact()
                    ->description('Tekan Enter setelah scan atau selesai mengetik kode.')
                    ->schema([
                        TextInput::make('code')
                            ->label('Kode pelanggan')
                            ->placeholder('Contoh: 1, 2, 42…')
                            ->autofocus(fn (): bool => ! $this->mealEntryScanCustomerLoaded)
                            ->extraInputAttributes(function (): array {
                                $attrs = [
                                    'id' => self::MEAL_ENTRY_SCAN_CODE_INPUT_ID,
                                    'autocomplete' => 'off',
                                    'autocorrect' => 'off',
                                    'spellcheck' => 'false',
                                    'inputmode' => 'numeric',
                                    'style' => 'font-size: 2.25rem; line-height: 2.5rem; padding-top: 1.25rem; padding-bottom: 1.25rem;',
                                    'tabindex' => '1',
                                    'x-ref' => 'mealEntryScanCodeInput',
                                    'wire:keydown.enter.prevent' => 'mealEntryScanLoadCustomer',
                                ];

                                // Scanner "System" sering pakai suffix Tab (bukan Enter). Hanya saat belum load:
                                // setelah load, biarkan Tab pindah ke field harga (tabindex 2).
                                if (! $this->mealEntryScanCustomerLoaded) {
                                    $attrs['wire:keydown.tab.prevent'] = 'mealEntryScanLoadCustomer';
                                }

                                return $attrs;
                            }),
                    ]),
                Section::make('Data pelanggan')
                    ->compact()
                    ->description('Isi harga di atas, lalu Enter untuk menyimpan. Detail pelanggan ada di bawah.')
                    ->visible(fn (): bool => $this->mealEntryScanCustomerLoaded)
                    ->schema([
                        TextInput::make('price')
                            ->label('Harga')
                            ->prefix('Rp')
                            ->placeholder('15.000')
                            ->extraInputAttributes([
                                'id' => self::MEAL_ENTRY_SCAN_PRICE_INPUT_ID,
                                'autocomplete' => 'off',
                                'inputmode' => 'numeric',
                                'style' => 'font-size: 2.25rem; line-height: 2.5rem; padding-top: 1.25rem; padding-bottom: 1.25rem;',
                                'tabindex' => '2',
                                'x-ref' => 'mealEntryScanPriceInput',
                                'wire:keydown.enter.prevent' => 'mealEntryScanCreateEntry',
                                'wire:keydown.tab.prevent' => 'mealEntryScanCreateEntry',
                            ])
                            ->live()
                            ->afterStateUpdated(function (Set $set, mixed $state): void {
                                if ($state === null || $state === '') {
                                    return;
                                }

                                $digits = preg_replace('/\D/', '', (string) $state);

                                if ($digits === '') {
                                    if ((string) $state !== '') {
                                        $set('price', '');
                                    }

                                    return;
                                }

                                if (strlen($digits) > 15) {
                                    $digits = substr($digits, 0, 15);
                                }

                                $formatted = number_format((int) $digits, 0, ',', '.');

                                if ($formatted !== (string) $state) {
                                    $set('price', $formatted);
                                }
                            })
                            ->columnSpanFull(),
                        TextInput::make('customer_code')
                            ->label('Kode')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('customer_name')
                            ->label('Nama')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('customer_phone')
                            ->label('Nomor HP')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),
                        TextInput::make('workplace_name')
                            ->label('Tempat kerja')
                            ->disabled()
                            ->dehydrated(false)
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }

    public function mealEntryScanLoadCustomer(): void
    {
        $this->mealEntryScanData['code'] = trim((string) ($this->mealEntryScanData['code'] ?? ''));

        if ($this->mealEntryScanData['code'] === '') {
            return;
        }

        $customer = Customer::query()
            ->with('workplace')
            ->where('code', $this->mealEntryScanData['code'])
            ->first();

        if (! $customer) {
            $this->mealEntryScanReset();

            Notification::make()
                ->title('Pelanggan tidak ada')
                ->danger()
                ->send();

            $this->mealEntryScanFocusCode();

            return;
        }

        $this->mealEntryScanCustomerLoaded = true;
        $this->mealEntryScanCustomerId = $customer->id;

        $this->mealEntryScanData['customer_code'] = $customer->code;
        $this->mealEntryScanData['customer_name'] = $customer->name;
        $this->mealEntryScanData['customer_phone'] = $customer->phone ?? '';
        $this->mealEntryScanData['workplace_name'] = $customer->workplace?->name ?? '';
        $this->mealEntryScanData['price'] = '';

        $this->mealEntryScanFocusPrice();
    }

    public function mealEntryScanCreateEntry(): void
    {
        if (! $this->mealEntryScanCustomerLoaded || ! $this->mealEntryScanCustomerId) {
            $this->mealEntryScanReset();
            $this->mealEntryScanFocusCode();

            return;
        }

        $price = (int) preg_replace('/[^0-9]/', '', (string) ($this->mealEntryScanData['price'] ?? ''));

        if ($price <= 0) {
            Notification::make()
                ->title('Harga harus diisi')
                ->danger()
                ->send();

            $this->mealEntryScanFocusPrice();

            return;
        }

        $savedName = (string) ($this->mealEntryScanData['customer_name'] ?? '');

        DB::transaction(function () use ($price): void {
            $customer = Customer::query()
                ->with('workplace')
                ->findOrFail($this->mealEntryScanCustomerId);

            $workplace = $customer->workplace;

            MealEntry::query()->create([
                'customer_id' => $customer->id,
                'workplace_id' => $workplace->id,

                'customer_code' => $customer->code,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'workplace_name' => $workplace->name,

                'eaten_at' => now(),
                'price' => $price,
                'paid' => false,
                'paid_at' => null,
            ]);
        });

        Notification::make()
            ->title('Entri tersimpan')
            ->body($savedName.' — Rp '.number_format($price, 0, ',', '.'))
            ->icon('heroicon-o-check-circle')
            ->success()
            ->send();

        $this->mealEntryScanReset();

        // Refresh tabel dulu (list), baru fokus ke kode — supaya re-render tidak mencuri fokus.
        $this->afterMealEntryScanCreated();

        $this->mealEntryScanFocusCode();
    }

    /**
     * Dipanggil setelah entri baru berhasil disimpan (mis. refresh tabel di halaman list).
     */
    protected function afterMealEntryScanCreated(): void {}

    protected function mealEntryScanFocusCode(): void
    {
        $this->dispatch('meal-entry-scan-focus-code');
        $this->js($this->mealEntryScanFocusCodeJsExpression());
    }

    protected function mealEntryScanFocusPrice(): void
    {
        $this->dispatch('meal-entry-scan-focus-price');
        $this->js($this->mealEntryScanFocusPriceJsExpression());
    }

    protected function mealEntryScanFocusCodeJsExpression(): string
    {
        $id = self::MEAL_ENTRY_SCAN_CODE_INPUT_ID;

        return <<<JS
            (function () {
                const id = '{$id}';
                const run = () => {
                    const el = document.getElementById(id);
                    if (el && ! el.disabled) {
                        el.focus({ preventScroll: true });
                    }
                };
                run();
                [1, 50, 120, 300, 600, 1000].forEach((ms) => setTimeout(run, ms));
            })();
        JS;
    }

    protected function mealEntryScanFocusPriceJsExpression(): string
    {
        $id = self::MEAL_ENTRY_SCAN_PRICE_INPUT_ID;

        return <<<JS
            (function () {
                const id = '{$id}';
                const run = () => {
                    const el = document.getElementById(id);
                    if (el && ! el.disabled) {
                        el.focus({ preventScroll: true });
                    }
                };
                run();
                [1, 50, 120, 300, 600].forEach((ms) => setTimeout(run, ms));
            })();
        JS;
    }

    protected function mealEntryScanReset(): void
    {
        $this->mealEntryScanCustomerLoaded = false;
        $this->mealEntryScanCustomerId = null;

        $this->mealEntryScanData = [
            'code' => '',
            'price' => '',
            'customer_code' => '',
            'customer_name' => '',
            'customer_phone' => '',
            'workplace_name' => '',
        ];
    }
}
