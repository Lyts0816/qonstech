<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeductionResource\Pages;
use App\Filament\Resources\DeductionResource\RelationManagers;
use App\Models\Deduction;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Employee;
use App\Models\WeekPeriod;
use App\Models\pagibig;
use App\Models\philhealth;
use App\Models\sss;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DeductionResource extends Resource
{
    protected static ?string $model = Deduction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = "Employee Payroll";

    protected static ?int $navigationSort = 2;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Employee Select Field
                Select::make('employeeID')
                ->label('Employee')
                ->options(Employee::all()->pluck('full_name', 'id'))
                ->required()
                ->preload()
                ->searchable()
                ->reactive() // Make this field reactive
                ->afterStateUpdated(function ($state, $set) {
                    // Clear PeriodID when EmployeeID changes
                    $set('PeriodID', null);
                }),
            
                // Deduction Type - Leave only Salary Adjustment
                Select::make('DeductionType')
                    ->label('Deduction Type')
                    ->options([
                        'Cash Advances' => 'Cash Advances',
                        'SalaryAdjustment' => 'Salary Adjustment',
                    ])
                    ->default('SalaryAdjustment'),
                                         
                // Amount Field
                TextInput::make('Amount')
                    ->label('Amount')
                    ->required()
                    ->numeric(),

                    Select::make('PeriodID')
                    ->label('Select Period')
                    ->options(function (callable $get) {
                        // Ensure PeriodID is reactive to EmployeeID
                        $employeeId = $get('employeeID');
                        if ($employeeId) {
                            $employee = Employee::find($employeeId);
                            if ($employee) {
                                // Dynamically filter WeekPeriod based on employee status
                                $category = $employee->employment_type === 'Regular' ? 'Kinsenas' : 'Weekly';
                                return WeekPeriod::where('Category', operator: $category)->get()
                                    ->mapWithKeys(function ($period) {
                                        return [
                                            $period->id => $period->StartDate . ' - ' . $period->EndDate
                                        ];
                                    });
                            }
                        }
                        return [];
                    })
                    ->reactive() // Add reactivity here
                    ->required(fn (string $context) => $context === 'create'),
            ]);
    }
    


    public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('employee.full_name')
                ->label('Employee')
                ->searchable(['employees.first_name', 'employees.middle_name', 'employees.last_name']),

            TextColumn::make('DeductionType')
                ->label('Deduction Type'),

            TextColumn::make('PeriodID') // Reference to the period
                ->label('Period')
                ->formatStateUsing(function ($state, $record) {
                    // Assuming $record->weekperiod exists and contains StartDate and EndDate
                    return $record->weekperiod ? 
                        $record->weekperiod->StartDate . ' - ' . $record->weekperiod->EndDate : 
                        'N/A'; // Handle case where no period is found
                }),

            TextColumn::make('Amount')
                ->label('Amount'),
        ])
        ->filters([
            // Add filters here if needed
        ])
        ->actions([
            Tables\Actions\EditAction::make()
                ->hidden(fn($record) => $record->trashed()),

                Tables\Actions\DeleteAction::make()->label('Deactivate')
                ->modalSubmitActionLabel('Deactivate')
                ->modalHeading('Deactivate Deduction')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('Deduction Deactivated'),

                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
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
            'index' => Pages\ListDeductions::route('/'),
            'create' => Pages\CreateDeduction::route('/create'),
            'edit' => Pages\EditDeduction::route('/{record}/edit'),
        ];
    }
}
