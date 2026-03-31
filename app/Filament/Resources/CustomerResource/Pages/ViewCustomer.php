<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\MealEntry;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewCustomer extends ViewRecord
{
    protected static string $resource = CustomerResource::class;

    protected static ?string $title = 'Detail Pelanggan';

    public ?string $unpaidDateFrom = null;

    public ?string $unpaidDateUntil = null;

    public ?int $unpaidTotal = null;

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('unpaidMealTotal')
                ->label('Total belum lunas')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->modalHeading('Hitung total harga belum lunas')
                ->modalDescription('Pilih rentang berdasarkan waktu makan. Hanya entri dengan status belum lunas yang dijumlahkan.')
                ->schema([
                    DatePicker::make('date_from')
                        ->label('Dari tanggal')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                    DatePicker::make('date_until')
                        ->label('Sampai tanggal')
                        ->required()
                        ->default(now())
                        ->native(false)
                        ->displayFormat('d/m/Y'),
                ])
                ->modalSubmitActionLabel('Hitung')
                ->action(function (array $data): void {
                    $from = Carbon::parse($data['date_from'])->startOfDay();
                    $until = Carbon::parse($data['date_until'])->endOfDay();

                    if ($from->greaterThan($until)) {
                        [$from, $until] = [$until, $from];
                    }

                    $total = MealEntry::query()
                        ->where('customer_id', $this->record->getKey())
                        ->where('paid', false)
                        ->whereBetween('eaten_at', [$from, $until])
                        ->sum('price');

                    $this->unpaidDateFrom = $from->toDateString();
                    $this->unpaidDateUntil = $until->toDateString();
                    $this->unpaidTotal = (int) $total;
                }),
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Total belum lunas')
                ->description(fn (): string => filled($this->unpaidTotal) ? 'Berikut hasil perhitungan terakhir.' : 'Klik tombol "Total belum lunas" untuk menghitung.')
                ->visible(fn (): bool => $this->unpaidTotal !== null)
                ->schema([
                    TextEntry::make('unpaid_range')
                        ->label('Range tanggal')
                        ->state(function (): string {
                            $from = filled($this->unpaidDateFrom) ? Carbon::parse($this->unpaidDateFrom)->format('d/m/Y') : '—';
                            $until = filled($this->unpaidDateUntil) ? Carbon::parse($this->unpaidDateUntil)->format('d/m/Y') : '—';

                            return "{$from} – {$until}";
                        }),
                    TextEntry::make('unpaid_total')
                        ->label('Total harga belum lunas')
                        ->state(fn (): string => 'Rp '.number_format((int) ($this->unpaidTotal ?? 0), 0, ',', '.'))
                        ->weight('bold'),
                ])
                ->columns(2),

            Section::make('Data pelanggan')
                ->columns(2)
                ->schema([
                    TextEntry::make('code')->label('Kode'),
                    TextEntry::make('name')->label('Nama'),
                    TextEntry::make('phone')->label('Nomor HP')->placeholder('—'),
                    TextEntry::make('workplace.name')->label('Tempat Kerja'),
                ]),
        ]);
    }
}
