<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PayslipResource\Pages;
use App\Filament\Resources\PayslipResource\RelationManagers;
use App\Models\Payslip;
use App\Models\WeekPeriod;
use App\Models\Payroll;
use Filament\Forms;
use Filament\Forms\Form;
use App\Models\Project;
use Filament\Resources\Resource;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Models\Employee;
use Carbon\Carbon;
use DateTime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\ButtonAction;
use Filament\Forms\Components\Fieldset;

class PayslipResource extends Resource
{
    protected static ?string $model = Payslip::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    // public static function getNavigationLabel(): string
    // {
    //     return 'Payslips and DTR';
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Adding the new "Select Payroll" field at the top
                Select::make('SelectPayroll')
                    ->label('Select Payroll') // Label for the field
                    ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                    ->options(function () {
                        // Fetch payrolls and format the display string
                        return Payroll::orderBy('PayrollYear')
                            ->orderBy('PayrollMonth')
                            ->orderBy('PayrollDate2')
                            ->get()
                            ->mapWithKeys(function ($payroll) {
                            $displayText = "{$payroll->PayrollMonth},{$payroll->PayrollYear} - {$payroll->PayrollDate2} | {$payroll->EmployeeStatus} - {$payroll->assignment} ";
                            return [$payroll->id => $displayText];
                        });
                    })
                    ->placeholder('Select Payroll Option')
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // When a payroll is selected, fetch details and update other fields
                        $set('EmployeeStatus', null);
                        $set('PayrollFrequency', null);
                        $set('PayrollMonth', null);
                        $set('PayrollYear', null);
                        $set('PayrollDate2', null);
                        $set('assignment', null);
                        $set('ProjectID', null);
                        $set('weekPeriodID', null);
                        if ($state) {
                            $payroll = Payroll::find($state); // Fetch payroll by ID
            
                            if ($payroll) {
                                // Update other fields with payroll data
                                $set('EmployeeStatus', $payroll->EmployeeStatus);
                                $set('PayrollFrequency', $payroll->PayrollFrequency);
                                $set('PayrollMonth', $payroll->PayrollMonth);
                                $set('PayrollYear', $payroll->PayrollYear);
                                $set('PayrollDate2', $payroll->PayrollDate2);
                                $set('assignment', $payroll->assignment);
                                $set('ProjectID', $payroll->ProjectID);
                                $set('weekPeriodID', $payroll->weekPeriodID);
                            }
                        }
                    }),

                Fieldset::make('Payroll Details') // Create a two-column grid layout for the first two fields
                    ->schema([
                        // EmployeeStatus Select Field
                        // EmployeeStatus Select Field
                        Select::make('EmployeeStatus')
                            ->label('Employee Status')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options([
                                'Regular' => 'Regular',
                                'Contractual' => 'Contractual',
                            ])
                            ->default(request()->query('employee'))
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Automatically set PayrollFrequency based on EmployeeStatus
                                if ($state === 'Regular') {
                                    $set('PayrollFrequency', 'Kinsenas');
                                } elseif ($state === 'Contractual') {
                                    $set('PayrollFrequency', 'Weekly');
                                }
                            }),

                        // Assignment Select Field
                        Select::make('assignment')
                            ->label('Assignment')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options([
                                'Main Office' => 'Main Office',
                                'Project Based' => 'Project Based',
                            ])
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Clear project selection if assignment is not Project Based
                                if ($state !== 'Project Based') {
                                    $set('ProjectID', null); // Reset the project selection
                                }
                            }),

                        // Project Select Field - only shown if assignment is Project Based
                        Select::make('ProjectID')
                            ->label('Project')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function () {
                                // Fetch projects from the database (assuming you have a Project model)
                                return \App\Models\Project::pluck('ProjectName', 'id'); // Change 'name' to the actual field for project name
                            })
                            ->hidden(fn($get) => $get('assignment') !== 'Project Based'), // Hide if not project based

                        // Select::make('EmployeeID')
                        //     ->label('Select Employee')
                        //     ->required()
                        //     ->options(
                        //         fn($get) =>
                        //         Employee::where('employment_type', $get('EmployeeStatus'))
                        //             ->get() // Get all records first
                        //             ->mapWithKeys(function ($employee) {
                        //                 return [$employee->id => $employee->first_name . ' ' . $employee->last_name];
                        //             }) // Combine names
                        //     )
                        //     ->reactive(),


                        // PayrollFrequency Select Field
                        Select::make('PayrollFrequency')
                            ->label('Payroll Frequency')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options([
                                'Kinsenas' => 'Kinsenas',
                                'Weekly' => 'Weekly',
                            ])
                            ->native(false)
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                // Automatically set PayrollFrequency based on EmployeeStatus if it is not set already
                                if ($state === 'Regular') {
                                    $set('PayrollFrequency', 'Kinsenas');
                                } else {
                                    $set('PayrollFrequency', 'Weekly');
                                }
                            }),


                        // PayrollDate Select Field
                        Select::make('PayrollDate2')
                            ->label('Payroll Date')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function (callable $get) {
                                $frequency = $get('PayrollFrequency');

                                if ($frequency == 'Kinsenas') {
                                    return [
                                        '1st Kinsena' => '1st-15th',
                                        '2nd Kinsena' => '16th-End of the Month',
                                    ];
                                } elseif ($frequency == 'Weekly') {
                                    return [
                                        'Week 1' => 'Week 1',
                                        'Week 2' => 'Week 2',
                                        'Week 3' => 'Week 3',
                                        'Week 4' => 'Week 4',
                                    ];
                                }

                                return [];
                            })
                            ->native(false)
                            ->reactive(),

                        // PayrollMonth Select Field
                        Select::make('PayrollMonth')
                            ->label('Payroll Month')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options([
                                'January' => 'January',
                                'February' => 'February',
                                'March' => 'March',
                                'April' => 'April',
                                'May' => 'May',
                                'June' => 'June',
                                'July' => 'July',
                                'August' => 'August',
                                'September' => 'September',
                                'October' => 'October',
                                'November' => 'November',
                                'December' => 'December',
                            ])
                            ->native(false)
                            ->reactive(),

                        // PayrollYear Select Field
                        Select::make('PayrollYear')
                            ->label('Payroll Year')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function () {
                                $currentYear = date('Y');
                                $years = [];
                                for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                                    $years[$i] = $i;
                                }
                                return $years;
                            })
                            ->native(false)
                            ->reactive(),

                        // weekPeriodID Select Field
                        Select::make('weekPeriodID')
                            ->label('Period')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function (callable $get) {
                                // Fetch selected values from other fields
                                $month = $get('PayrollMonth');
                                $frequency = $get('PayrollFrequency');
                                $payrollDate = $get('PayrollDate2');
                                $year = $get('PayrollYear');

                                // Ensure that all necessary fields are filled before proceeding
                                if ($month && $frequency && $payrollDate && $year) {
                                    try {
                                        // Convert month name to month number (e.g., 'January' to '01')
                                        $monthId = Carbon::createFromFormat('F', $month)->format('m');

                                        // Fetch WeekPeriod entries based on the selected criteria
                                        return WeekPeriod::where('Month', $monthId)
                                            ->where('Category', $frequency)
                                            ->where('Type', $payrollDate)
                                            ->where('Year', $year)
                                            ->get()
                                            ->mapWithKeys(function ($period) {
                                            return [
                                                $period->id => $period->StartDate . ' - ' . $period->EndDate,
                                            ];
                                        });
                                    } catch (\Exception $e) {
                                        // In case there is an issue with parsing the date or any other issue
                                        return [];
                                    }
                                }

                                // If any of the fields are not set, return an empty array
                                return [];
                            })
                            ->native(false)
                            ->reactive() // Make this field reactive to other fields
                            ->placeholder('Select the payroll period'),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Tables\Columns\TextColumn::make('employee.full_name')
                //     ->label('Employee Name')
                //     ->searchable()
                //     ->sortable(),



                Tables\Columns\TextColumn::make('EmployeeStatus')
                    ->label('Employee Type')
                    ->searchable()
                    ->sortable(),

                // Tables\Columns\TextColumn::make('assignment')
                //     ->label('Assignment')
                //     ->searchable()
                //     ->sortable(),

                Tables\Columns\TextColumn::make('project.ProjectName')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),



                Tables\Columns\TextColumn::make('PayrollMonth')
                    ->Label('Payroll Month'),

                Tables\Columns\TextColumn::make('PayrollYear')
                    ->Label('Payroll Year'),

                Tables\Columns\TextColumn::make('PayrollFrequency')
                    ->Label('Payroll Frequency'),

                Tables\Columns\TextColumn::make('PayrollDate2')
                    ->label('Payroll Dates')
                    ->searchable()
                    ->sortable(),
            ])
            ->recordUrl(function ($record) {
                return null;
            })
            ->actions([
                // Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('viewPayslip')
                ->hidden(fn($record) => $record->trashed())
                ->label('View Payslip')
                ->icon('heroicon-o-calculator')
                ->color('info')
                ->url(fn($record) => route('generate.payslips', ['record' => $record->toArray()])) // Pass the ProjectID
                ->openUrlInNewTab(),

                Tables\Actions\DeleteAction::make()->label('Archive')
                ->modalSubmitActionLabel('Archived')
                ->modalHeading('Archived Payslip')
                ->hidden(fn($record) => $record->trashed())
                ->successNotificationTitle('Payslip Archived'),

                Tables\Actions\RestoreAction::make(),

            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    /**
     * Example payroll calculation method.
     *
     * @param Payslip|array $data
     * @return float
     */
    protected static function calculateNetPay($data)
    {
        // Implement your payroll calculation logic here.
        // This is a dummy implementation.
        // Replace with your actual calculation logic.
        if ($data instanceof Payroll) {
            // If $data is a Payroll model instance
            return $data->GrossPay - $data->TotalDeductions;
        } elseif (is_array($data)) {
            // If $data is an array from the form
            // Example calculation based on form data
            // Adjust as necessary
            $grossPay = isset($data['GrossPay']) ? floatval($data['GrossPay']) : 0;
            $totalDeductions = isset($data['TotalDeductions']) ? floatval($data['TotalDeductions']) : 0;
            return $grossPay - $totalDeductions;
        }

        return 0;
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
            'index' => Pages\ListPayslips::route('/'),
            'create' => Pages\CreatePayslip::route('/create'),
            'edit' => Pages\EditPayslip::route('/{record}/edit'),
        ];
    }
}
