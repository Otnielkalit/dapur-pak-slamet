<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkplaceResource\Pages\CreateWorkplace;
use App\Filament\Resources\WorkplaceResource\Pages\EditWorkplace;
use App\Filament\Resources\WorkplaceResource\Pages\ListWorkplaces;
use App\Models\Workplace;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Resources\Pages\PageRegistration;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Actions\EditAction;
use Filament\Tables\Actions\EditAction as TableEditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Actions\ExportAction;
use Filament\Actions\DeleteBulkAction as TableDeleteBulkAction;
use Filament\Actions\BulkAction;
use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Database\Eloquent\Model;

class WorkplaceResource extends Resource
{
    protected static ?string $model = Workplace::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
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
                \Filament\Actions\CreateAction::make(),
            ])
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
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

