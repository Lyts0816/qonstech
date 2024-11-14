<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PositionResource\Pages;
use App\Filament\Resources\PositionResource\RelationManagers;
use App\Models\Position;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PositionResource extends Resource
{
    protected static ?string $model = Position::class;
    protected static ?string $navigationGroup = "Employee Details";

    protected static ?string $title = 'Position';

    protected static ?string $breadcrumb = "Position";

    protected static ?string $navigationLabel = 'Position';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    // protected static ?string $navigationGroup = "Projects/Assign";

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('PositionName')
                ->required(fn (string $context) => $context === 'create')
                ->unique(ignoreRecord: true)
                ->rules('regex:/^[^\d]*$/'),

        TextInput::make('HourlyRate')
            ->required(fn (string $context) => $context === 'create')
            ->numeric() 
            ->reactive() 
            ->afterStateUpdated(function (callable $set, $state) {
                
                $monthlySalary = $state * 8 * 22; 
                $set('MonthlySalary', $monthlySalary); 
            }),

            TextInput::make('MonthlySalary')
                ->required(fn (string $context) => $context === 'create')
                ->numeric() 
                ->readOnly(),      
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('PositionName')->searchable(),
                TextColumn::make('MonthlySalary'),
                TextColumn::make('HourlyRate'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                ->hidden(fn($record) => $record->trashed()),

                Tables\Actions\DeleteAction::make()->label('Deactivate')
                ->modalSubmitActionLabel('Deactivate')
                ->modalHeading('Deactivate Position')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('Position Deactivated'),

                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
	{
		return parent::getEloquentQuery()
			->withoutGlobalScopes([
				SoftDeletingScope::class,
			]);
	}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPositions::route('/'),
            'create' => Pages\CreatePosition::route('/create'),
            'edit' => Pages\EditPosition::route('/{record}/edit'),
        ];
    }
}
