<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkplaceResource\Pages\CreateWorkplace;
use App\Filament\Resources\WorkplaceResource\Pages\EditWorkplace;
use App\Filament\Resources\WorkplaceResource\Pages\ListWorkplaces;
use App\Models\Workplace;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class WorkplaceResource extends Resource
{
    protected static ?string $model = Workplace::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Tempat Kerja';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('name')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => ListWorkplaces::route('/'),
            'create' => CreateWorkplace::route('/create'),
            'edit' => EditWorkplace::route('/{record}/edit'),
        ];
    }
}
