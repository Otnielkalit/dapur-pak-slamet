<?php

namespace App\Filament\Resources;

use App\Exports\MealEntryTableExport;
use App\Filament\Resources\MealEntryResource\Pages\ListMealEntries;
use App\Models\MealEntry;
use App\Models\Workplace;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MealEntryResource extends Resource
{
    protected static ?string $model = MealEntry::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Entry Makanan';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return (bool) $record->paid;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('eaten_at', 'desc')
            ->searchable()
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->filtersFormColumns([
                'default' => 1,
                'sm' => 2,
                'lg' => 4,
            ])
            ->columns([
                TextColumn::make('customer_code')->label('Code')->searchable()->sortable(),
                TextColumn::make('customer_name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('customer_phone')->label('Nomor HP'),
                TextColumn::make('workplace_name')->label('Tempat Kerja')->sortable(),
                TextColumn::make('eaten_at')
                    ->label('Tanggal Makan')
                    // DB menyimpan UTC; tampilkan jam Indonesia (WIB).
                    ->dateTime('Y-m-d H:i', 'Asia/Jakarta')
                    ->sortable(),
                TextColumn::make('price')->label('Harga')->money('IDR')->sortable(),
                TextColumn::make('paid')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'lunas' : 'belum lunas'),
                TextColumn::make('paid_at')
                    ->label('Tanggal lunas')
                    ->dateTime('Y-m-d H:i', 'Asia/Jakarta')
                    ->sortable()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('workplace_id')
                    ->label('Tempat Kerja')
                    ->placeholder('Semua tempat kerja')
                    ->options(fn (): array => Workplace::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
                SelectFilter::make('paid')
                    ->label('Status')
                    ->placeholder('Semua status')
                    ->options([
                        1 => 'lunas',
                        0 => 'belum lunas',
                    ]),
                Filter::make('eaten_at')
                    ->label('Tanggal makan (dari – sampai)')
                    ->columns(2)
                    ->columnSpan(['default' => 'full', 'lg' => 2])
                    ->schema([
                        DatePicker::make('from')->label('Dari'),
                        DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(filled($data['from'] ?? null), fn (Builder $q) => $q->whereDate('eaten_at', '>=', $data['from']))
                            ->when(filled($data['until'] ?? null), fn (Builder $q) => $q->whereDate('eaten_at', '<=', $data['until']));
                    }),
            ])
            ->headerActions([
                Action::make('exportCsv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->extraAttributes(['tabindex' => '-1'])
                    ->action(fn ($livewire) => MealEntryTableExport::downloadCsv($livewire->getTableQueryForExport())),
            ])
            ->recordActions([
                Action::make('togglePaid')
                    ->label(fn (MealEntry $record): string => $record->paid ? 'Set belum lunas' : 'Set lunas')
                    ->icon(fn (MealEntry $record): string => $record->paid ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (MealEntry $record): string => $record->paid ? 'warning' : 'success')
                    ->button()
                    ->action(function (MealEntry $record): void {
                        $record->togglePaid(! $record->paid);
                    }),
                DeleteAction::make()
                    ->button()
                    ->visible(fn (MealEntry $record): bool => (bool) $record->paid),
                Action::make('holdCustomer')
                    ->label('Hold Pelanggan')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->button()
                    ->requiresConfirmation()
                    ->modalHeading('Hold Pelanggan?')
                    ->modalDescription('Pelanggan ini tidak akan bisa melakukan transaksi makan sampai blokir dibuka.')
                    ->visible(fn (MealEntry $record): bool => ! $record->paid && ! $record->customer?->is_blocked)
                    ->action(function (MealEntry $record) {
                        $customer = $record->customer;
                        if (! $customer) return;

                        $customer->update(['is_blocked' => true]);

                        Notification::make()
                            ->title('Pelanggan di-HOLD')
                            ->body("{$customer->name} telah diblokir dari transaksi.")
                            ->danger()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('markPaid')
                    ->label('Set lunas')
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            $record->togglePaid(true);
                        }
                    }),
                BulkAction::make('markUnpaid')
                    ->label('Set belum lunas')
                    ->action(function ($records): void {
                        foreach ($records as $record) {
                            $record->togglePaid(false);
                        }
                    }),
            ]);
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListMealEntries::route('/'),
        ];
    }
}
