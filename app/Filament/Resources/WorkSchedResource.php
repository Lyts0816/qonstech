<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WorkSchedResource\Pages;
use App\Filament\Resources\WorkSchedResource\RelationManagers;
use App\Models\WorkSched;
use Doctrine\DBAL\Schema\Schema;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WorkSchedResource extends Resource
{
    protected static ?string $model = WorkSched::class;

    protected static ?string $navigationLabel = 'Work Schedule';
    protected static ?string $navigationGroup = "Employee Details";

    protected static ?string $title = 'Work Schedule';

    protected static ?string $breadcrumb = "Work Schedule";


    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    // protected static ?string $navigationGroup = "Projects/Assign";

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('')
                    ->schema([
                        TextInput::make('ScheduleName')
                            ->label('Schedule Name')
                            ->required(fn(string $context) => $context === 'create')
                            ->unique(ignoreRecord: true)
                            ->rules('regex:/^[^\d]*$/'),

                        TextInput::make('RegularHours')
                            ->label('Number of Work Hours')
                            ->numeric()
                            ->maxLength(2)
                            ->maxValue(12),

                        Select::make('ScheduleType')
                            ->label('Schedule Type')
                            ->required(fn(string $context) => $context === 'create')
                            ->options([
                                'Fixed' => 'Fixed',
                                'Flexible' => 'Flexible'
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'Flexible') {
                                    $set('monday', 0);
                                    $set('tuesday', 0);
                                    $set('wednesday', 0);
                                    $set('thursday', 0);
                                    $set('friday', 0);
                                    $set('saturday', 0);
                                    $set('sunday', 0);
                                    $set('CheckinOne', '00:00');
                                    $set('CheckoutOne', '00:00');
                                    $set('CheckinTwo', '00:00');
                                    $set('CheckoutTwo', '00:00');
                                }else{
                                    $set('RegularHours', 8);
                                    $set('monday', 1);
                                    $set('tuesday', 1);
                                    $set('wednesday', 1);
                                    $set('thursday', 1);
                                    $set('friday', 1);
                                    $set('saturday', 1);
                                    $set('sunday', 0);
                                    $set('CheckinOne', '08:00');
                                    $set('CheckoutOne', '12:00');
                                    $set('CheckinTwo', '13:00');
                                    $set('CheckoutTwo', '17:00');
                                }
                            })
                    ])->compact()->columns(),



                Section::make('Days')
                    ->schema([
                        Toggle::make('monday'),

                        Toggle::make('tuesday'),

                        Toggle::make('wednesday'),

                        Toggle::make('thursday'),

                        Toggle::make('friday'),

                        Toggle::make('saturday'),

                        Toggle::make('sunday')

                    ])
                    ->compact()
                    ->columns(7)
                    ->collapsible(true)->hidden(fn($get) => $get('ScheduleType') === 'Fixed'),

                Section::make('Shift Schedule')

                    ->schema([
                        Section::make('Morning Shift')
                            ->schema([

                                TextInput::make('CheckinOne')
                                    ->label('Check-in Time')
                                    ->type('time')
                                    ->required(fn(string $context) => $context === 'create'),


                                TextInput::make('CheckoutOne')
                                    ->label('Check-out Time')
                                    ->type('time')
                                    ->after('CheckinOne')
                                    ->required(fn(string $context) => $context === 'create'),

                            ])->collapsible(true)->columns(2)->compact()->columnSpan(1)->hidden(fn($get) => $get('ScheduleType') === 'Flexible'),

                        Section::make('Afternoon Shift')
                            ->schema([
                                TextInput::make('CheckinTwo')
                                    ->label('Check-in Time')
                                    ->type('time')
                                    ->required(fn(string $context) => $context === 'create'),

                                TextInput::make('CheckoutTwo')
                                    ->label('Check-out Time')
                                    ->type('time')
                                    ->after('CheckinTwo')
                                    ->required(fn(string $context) => $context === 'create'),
                            ])->collapsible(true)->columns(2)->compact()->columnSpan(1)->hidden(fn($get) => $get('ScheduleType') === 'Flexible'),

                    ])->columns(2)->compact()->hidden(fn($get) => $get('ScheduleType') === 'Flexible'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('ScheduleName')->searchable(),
                TextColumn::make('ScheduleType')->searchable(),
                IconColumn::make('monday')->boolean()->label('Monday'),
                IconColumn::make('tuesday')->boolean()->label('Tuesday'),
                IconColumn::make('wednesday')->boolean()->label('Wednesday'),
                IconColumn::make('thursday')->boolean()->label('Thursday'),
                IconColumn::make('friday')->boolean()->label('Friday'),
                IconColumn::make('saturday')->boolean()->label('Saturday'),
                IconColumn::make('sunday')->boolean()->label('Sunday'),
                TextColumn::make('CheckinOne'),
                TextColumn::make('CheckoutOne'),
                TextColumn::make('CheckinTwo'),
                TextColumn::make('CheckoutTwo'),
            ])

            ->filters([
                SelectFilter::make('ScheduleName')
                    ->label('Filter by ScheduleName')
                    ->options(
                        WorkSched::query()
                            ->pluck('ScheduleName', 'ScheduleName')
                            ->toArray()
                    )
                    ->searchable()
                    ->multiple()
                    ->preload(),

                SelectFilter::make('ScheduleType')
                    ->label('Filter by ScheduleType')
                    ->options(
                        WorkSched::query()
                            ->pluck('ScheduleType', 'ScheduleType')
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
                ->modalHeading('Deactivate Work Schedule')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('Work Schedule Deactivated'),

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
            'index' => Pages\ListWorkScheds::route('/'),
            'create' => Pages\CreateWorkSched::route('/create'),
            'edit' => Pages\EditWorkSched::route('/{record}/edit'),
        ];
    }
}
