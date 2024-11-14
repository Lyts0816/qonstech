<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HolidayResource\Pages;
use App\Filament\Resources\HolidayResource\RelationManagers;
use App\Models\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HolidayResource extends Resource
{
	protected static ?string $model = Holiday::class;

	protected static ?string $navigationIcon = 'heroicon-o-calendar-date-range';
	protected static ?string $navigationGroup = "Employee Payroll";

	public static function form(Form $form): Form
	{
		return $form
			->schema([
				Section::make('Holiday')
					->schema([
						TextInput::make('HolidayName')
                            ->label('Holiday Name')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->unique(ignoreRecord: true)
                            ->rules([
                                'regex:/^[a-zA-Z\s]*$/', // Ensures no digits and no special characters are present
                                'min:3',                 // Ensures the holiday name is at least 3 characters long
                                'max:30'                 // Ensures the holiday name is no more than 50 characters long
                            ])
                            ->validationMessages([
                                'regex' => 'The holiday name must not contain any digits or special characters.',
                                'min' => 'The holiday name must be at least 3 characters long.',
                                'max' => 'The holiday name must not exceed 30 characters.'
                            ]),

                        DatePicker::make('HolidayDate')
                            ->label('Holiday Date')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->rules([
                                'date', // Ensures the value is a valid date
                            ])
                            ->validationMessages([
                                'required' => 'The holiday date is required.',
                                'date' => 'The holiday date must be a valid date.',
                            ]),

						Select::make('HolidayType')
							->label('Holiday Type')
							->required(fn(string $context) => $context === 'create' || $context === 'edit')
							->options([
								'Regular' => 'Regular',
								'Special' => 'Special'
							])->native(false),

						
					])->columns(3)->collapsible(true),
			]);
	}

	public static function table(Table $table): Table
	{
		return $table
			->columns([
				TextColumn::make('HolidayName')
					->label('Holiday Name')
					->searchable(['HolidayName']),

				TextColumn::make('HolidayDate')
					->label('Holiday Date'),

				TextColumn::make('HolidayType')
					->label('Holiday Type'),

				TextColumn::make('HolidayType')
					->label('Holiday Type'),

			])
			
			->filters([
				SelectFilter::make('HolidayType')
					->label('Filter by Holiday Type')
					->options(
						Holiday::query()
							->pluck('HolidayType', 'HolidayType')
							->toArray()
					)
					->searchable()
					->multiple()
					->preload(),
			])
			->actions([
				Tables\Actions\EditAction::make()
                ->hidden(fn($record) => $record->trashed()),

                Tables\Actions\DeleteAction::make()->label('Deactivate')
                ->modalSubmitActionLabel('Deactivate')
                ->modalHeading('Deactivate Holiday')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('Holiday Deactivated'),

                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
			])
			->bulkActions([
				// Tables\Actions\BulkActionGroup::make([
				// 	Tables\Actions\DeleteBulkAction::make(),
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
			'index' => Pages\ListHolidays::route('/'),
			'create' => Pages\CreateHoliday::route('/create'),
			'edit' => Pages\EditHoliday::route('/{record}/edit'),
		];
	}
}
