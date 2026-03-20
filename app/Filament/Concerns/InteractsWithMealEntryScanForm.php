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
                            ->extraInputAttributes([
                                'autocomplete' => 'off',
                                'autocorrect' => 'off',
                                'spellcheck' => 'false',
                                'inputmode' => 'numeric',
                                'x-ref' => 'mealEntryScanCodeInput',
                                'wire:keydown.enter.prevent' => 'mealEntryScanLoadCustomer',
                            ]),
                    ]),
                Section::make('Data pelanggan')
                    ->compact()
                    ->description('Isi harga, lalu Enter untuk menyimpan.')
                    ->visible(fn (): bool => $this->mealEntryScanCustomerLoaded)
                    ->schema([
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
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextInput::make('price')
                            ->label('Harga')
                            ->prefix('Rp')
                            ->placeholder('15.000')
                            ->extraInputAttributes([
                                'autocomplete' => 'off',
                                'inputmode' => 'numeric',
                                'x-ref' => 'mealEntryScanPriceInput',
                                'wire:keydown.enter.prevent' => 'mealEntryScanCreateEntry',
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

            $this->dispatch('meal-entry-scan-focus-code');

            return;
        }

        $this->mealEntryScanCustomerLoaded = true;
        $this->mealEntryScanCustomerId = $customer->id;

        $this->mealEntryScanData['customer_code'] = $customer->code;
        $this->mealEntryScanData['customer_name'] = $customer->name;
        $this->mealEntryScanData['customer_phone'] = $customer->phone ?? '';
        $this->mealEntryScanData['workplace_name'] = $customer->workplace?->name ?? '';
        $this->mealEntryScanData['price'] = '';

        $this->dispatch('meal-entry-scan-focus-price');
    }

    public function mealEntryScanCreateEntry(): void
    {
        if (! $this->mealEntryScanCustomerLoaded || ! $this->mealEntryScanCustomerId) {
            $this->mealEntryScanReset();
            $this->dispatch('meal-entry-scan-focus-code');

            return;
        }

        $price = (int) preg_replace('/[^0-9]/', '', (string) ($this->mealEntryScanData['price'] ?? ''));

        if ($price <= 0) {
            Notification::make()
                ->title('Harga harus diisi')
                ->danger()
                ->send();

            $this->dispatch('meal-entry-scan-focus-price');

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

        $this->dispatch('meal-entry-scan-focus-code');

        $this->afterMealEntryScanCreated();
    }

    /**
     * Dipanggil setelah entri baru berhasil disimpan (mis. refresh tabel di halaman list).
     */
    protected function afterMealEntryScanCreated(): void {}

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
