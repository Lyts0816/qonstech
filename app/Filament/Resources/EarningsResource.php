<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EarningsResource\Pages;
use App\Filament\Resources\EarningsResource\RelationManagers;
use App\Models\Earnings;
use App\Models\Employee;
use App\Models\WeekPeriod;
use App\Models\Overtime;
use Faker\Provider\ar_EG\Text;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section as ComponentsSection;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EarningsResource extends Resource
{
    protected static ?string $model = Earnings::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "Employee Payroll";

    public static function calculateTotal($holiday, $leave, $overtimeRate)
    {
        return $holiday + $leave + $overtimeRate;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Earnings Information')
                    ->schema([
                        Select::make('EmployeeID')
                            ->label('Employee')
                            ->options(Employee::all()->pluck('full_name', 'id'))
                            ->required()
                            ->preload()
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, $set) {

                                $set('PeriodID', null);
                            }),



                        Select::make('EarningType')
                            ->label('Earnings Type')
                            ->options([
                                'Other Allowance' => 'Other Allowance',
                            ])
                            ->required()
                            ->default('Other Allowance'),
                        TextInput::make('Amount')
                            ->label('Amount')
                            ->required(fn(string $context) => $context === 'create')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(50000)
                            ->validationMessages([
                                'required' => 'The amount is required.',
                                'numeric' => 'The amount must be a number.',
                                'min' => 'The amount must be at least 0.',
                                'max' => 'The amount must not exceed 50,000.',
                            ]),
                        Select::make('PeriodID')
                            ->label('Select Period')
                            ->options(function (callable $get) {
                                $employeeId = $get('EmployeeID');
                                if ($employeeId) {
                                    $employee = Employee::find($employeeId);
                                    if ($employee) {
                                        $category = $employee->employment_type === 'Regular' ? 'Kinsenas' : 'Weekly';
                                        return WeekPeriod::where('Category', $category)->get()
                                            ->mapWithKeys(function ($period) {
                                                return [
                                                    $period->id => $period->StartDate . ' - ' . $period->EndDate
                                                ];
                                            });
                                    }
                                }
                                return [];
                            })
                            ->reactive()
                            ->required(fn(string $context) => $context === 'create'),

                        Section::make('')
                            ->schema([
                                Toggle::make('is_disbursed')
                                    ->label('Disbursed')
                                    ->default(false),
                            ]),

                    ])
                    ->columns(2)
                    ->collapsible(true),
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee.full_name')
                    ->label('Employee')
                    ->searchable(['employees.first_name', 'employees.middle_name', 'employees.last_name']),

                ToggleColumn::make('is_disbursed')
                    ->label('Disbursed'),

                TextColumn::make('EarningType')
                    ->label('Earning Type')
                    ->searchable(),

                TextColumn::make('PeriodID')
                    ->label('Period')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->weekperiod ?
                            $record->weekperiod->StartDate . ' - ' . $record->weekperiod->EndDate :
                            'N/A';
                    }),

                TextColumn::make('Amount')
                    ->label('Amount'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->hidden(fn($record) => $record->trashed()),

                Tables\Actions\DeleteAction::make()->label('Deactivate')
                    ->modalSubmitActionLabel('Deactivate')
                    ->modalHeading('Deactivate Earnings')
                    ->hidden(fn($record) => $record->trashed())
                    ->successNotificationTitle('Earnings Deactivated'),

                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEarnings::route('/'),
            'create' => Pages\CreateEarnings::route('/create'),
            'edit' => Pages\EditEarnings::route('/{record}/edit'),
        ];
    }
}
