<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\CustomerResource\Pages\EditCustomer;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Models\Customer;
use App\Models\Workplace;
use App\Support\WhatsAppLink;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Pelanggan';

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
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

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('code')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('phone')
                ->label('Nomor HP')
                ->tel()
                ->live()
                ->nullable()
                ->maxLength(30)
                ->helperText(
                    'Isi nomor lalu klik tombol Kirim WA untuk uji coba sebelum menyimpan. Pesan dikirim lewat WhatsApp yang sedang Anda login (disarankan nomor bisnis '.config('whatsapp.business_number').').',
                )
                ->suffixAction(
                    Action::make('sendWelcomeWhatsApp')
                        ->label('Kirim WA')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('success')
                        ->tooltip('Buka WhatsApp dengan pesan sambutan ke nomor di atas')
                        ->url(fn (Get $get): string => WhatsAppLink::buildWelcomeUrl($get('phone')) ?? '#')
                        ->openUrlInNewTab()
                        ->disabled(fn (Get $get): bool => WhatsAppLink::normalizeToWaDigits($get('phone')) === null),
                    true,
                ),
            Select::make('workplace_id')
                ->relationship('workplace', 'name')
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('phone')->label('Nomor HP'),
                TextColumn::make('workplace.name')->label('Tempat Kerja')->sortable(),
                TextColumn::make('is_blocked')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'danger' : 'success')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Diblokir' : 'Aktif'),
            ])
            ->filters([
                SelectFilter::make('workplace_id')
                    ->label('Nama perusahaan')
                    ->placeholder('Semua perusahaan')
                    ->options(fn (): array => Workplace::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->button(),
                EditAction::make()
                    ->button(),
                Action::make('unblock')
                    ->label('Buka Blokir')
                    ->icon('heroicon-o-play-circle')
                    ->color('success')
                    ->button()
                    ->visible(fn (Customer $record): bool => (bool) $record->is_blocked)
                    ->requiresConfirmation()
                    ->action(function (Customer $record) {
                        $record->update(['is_blocked' => false]);
                        Notification::make()
                            ->title('Blokir Dibuka')
                            ->body("{$record->name} sekarang bisa bertransaksi kembali.")
                            ->success()
                            ->send();
                    }),
                Action::make('block')
                    ->label('Hold')
                    ->icon('heroicon-o-pause-circle')
                    ->color('danger')
                    ->button()
                    ->visible(fn (Customer $record): bool => ! $record->is_blocked)
                    ->requiresConfirmation()
                    ->action(function (Customer $record) {
                        $record->update(['is_blocked' => true]);
                        Notification::make()
                            ->title('Pelanggan di-HOLD')
                            ->body("{$record->name} telah diblokir.")
                            ->danger()
                            ->send();
                    }),
                DeleteAction::make()
                    ->button(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }

    // Untuk versi awal (1 admin lokal), izinkan semua aksi CRUD.
    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return true;
    }

    public static function canEdit(Model $record): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        return true;
    }

    public static function canDelete(Model $record): bool
    {
        return true;
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view' => ViewCustomer::route('/{record}'),
            'edit' => EditCustomer::route('/{record}/edit'),
        ];
    }
}
