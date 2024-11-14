<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WeekPeriodResource\Pages;
use App\Filament\Resources\WeekPeriodResource\RelationManagers;
use App\Models\WeekPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WeekPeriodResource extends Resource
{
    protected static ?string $model = WeekPeriod::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Periods';
    protected static ?string $pluralLabel = 'Periods';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('Month')
                ->options([
                    1 => 'January',
                    2 => 'February',
                    3 => 'March',
                    4 => 'April',
                    5 => 'May',
                    6 => 'June',
                    7 => 'July',
                    8 => 'August',
                    9 => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December'
                ])
                ->required(fn (string $context) => $context === 'create')
                ->label('Month')
                ->default(date('n'))
                ->options(fn () => collect([
                    1 => 'January',
                    2 => 'February',
                    3 => 'March',
                    4 => 'April',
                    5 => 'May',
                    6 => 'June',
                    7 => 'July',
                    8 => 'August',
                    9 => 'September',
                    10 => 'October',
                    11 => 'November',
                    12 => 'December'
                ])->filter(fn ($label, $key) => $key >= date('n'))),
                    

                Forms\Components\TextInput::make('Year')
                    ->required()
                    ->numeric()
                    ->label('Year')
                    ->default(date('Y'))
                    ->readOnly(),

                Forms\Components\Select::make('Category')
                    ->options([
                        'Weekly' => 'Weekly',
                        'Kinsenas' => 'Kinsenas',
                    ])
                    ->required()
                    ->reactive()
                    ->label('Category'),
                    
                Forms\Components\Select::make('Type')
                    ->options(function (callable $get) {
                        // Dynamically update the options based on the selected Category
                        if ($get('Category') === 'Weekly') {
                            return [
                                'Week 1' => 'Week 1',
                                'Week 2' => 'Week 2',
                                'Week 3' => 'Week 3',
                                'Week 4' => 'Week 4',
                            ];
                        } elseif ($get('Category') === 'Kinsenas') {
                            return [
                                '1st Kinsena' => '1st Kinsena',
                                '2nd Kinsena' => '2nd Kinsena',
                            ];
                        }
                        return [];
                    })
                    ->required()
                    ->reactive()
                    ->label('Type')
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        $month = $get('Month');
                        $year = $get('Year');
    
                        // Ensure Month and Year are valid before calculating dates
                        if (!empty($month) && !empty($year)) {
                            $month = str_pad($month, 2, '0', STR_PAD_LEFT);

                            if ($get('Category') === 'Weekly') {
                                // Set StartDate and EndDate based on selected week
                                switch ($state) {
                                    case 'Week 1':
                                        $set('StartDate', "$year-$month-01");
                                        $set('EndDate', "$year-$month-07");
                                        break;
                                    case 'Week 2':
                                        $set('StartDate', "$year-$month-08");
                                        $set('EndDate', "$year-$month-14");
                                        break;
                                    case 'Week 3':
                                        $set('StartDate', "$year-$month-15");
                                        $set('EndDate', "$year-$month-21");
                                        break;
                                    case 'Week 4':
                                        $set('StartDate', "$year-$month-22");
                                        $set('EndDate', "$year-$month-" . cal_days_in_month(CAL_GREGORIAN, $month, $year));
                                        break;
                                    default:
                                        $set('StartDate', null);
                                        $set('EndDate', null);
                                }
                            } elseif ($get('Category') === 'Kinsenas') {
                                // Set StartDate and EndDate for Kinsenas
                                if ($state === '1st Kinsena') {
                                    $set('StartDate', "$year-$month-01");
                                    $set('EndDate', "$year-$month-15");
                                } elseif ($state === '2nd Kinsena') {
                                    $set('StartDate', "$year-$month-16");
                                    $set('EndDate', "$year-$month-" . cal_days_in_month(CAL_GREGORIAN, $month, $year));
                                }
                            }
                        }
                    }),

                Forms\Components\DatePicker::make('StartDate')
                    ->required()
                    ->label('Start Date'),
                Forms\Components\DatePicker::make('EndDate')
                    ->required()
                    ->label('End Date'),
            ]);
    }
    
    
    

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('StartDate')->label('Start Date')->date('m, d, Y')->sortable(),
                Tables\Columns\TextColumn::make('EndDate')->label('End Date')->date('m, d, Y')->sortable(),
                Tables\Columns\TextColumn::make('Month')
                ->sortable()
                ->formatStateUsing(function ($state) {
                    $months = [
                        1 => 'January',
                        2 => 'February',
                        3 => 'March',
                        4 => 'April',
                        5 => 'May',
                        6 => 'June',
                        7 => 'July',
                        8 => 'August',
                        9 => 'September',
                        10 => 'October',
                        11 => 'November',
                        12 => 'December'
                    ];
                    return $months[$state] ?? $state;
                }),
                Tables\Columns\TextColumn::make('Year')->sortable(),
                Tables\Columns\TextColumn::make('Category'),
                Tables\Columns\TextColumn::make('Type'),
            ])
            ->filters([
                Filter::make('StartDate')
                ->label('Start Date'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Define any relationships here if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWeekPeriods::route('/'),
            'create' => Pages\CreateWeekPeriod::route('/create'),
            'edit' => Pages\EditWeekPeriod::route('/{record}/edit'),
        ];
    }
}
