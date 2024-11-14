<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use App\Filament\Resources\ReportResource\RelationManagers;
use App\Models\Report;
use App\Models\Employee;
use Filament\Forms;
use App\Models\Payroll;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\WeekPeriod;
use App\Models\Project;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Grid;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use DateTime;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Actions\ButtonAction;
use Filament\Forms\Components\Fieldset;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // EmployeeStatus Select Field
                Select::make('ReportType')
                    ->label('Report Type')
                    ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                    ->options([
                        'SSS Contribution' => 'SSS Contribution',
                        'Philhealth Contribution' => 'Philhealth Contribution',
                        'Pagibig Contribution' => 'Pagibig Contribution',
                        'Payslip' => 'Payslip'
                    ])
                    ->default(request()->query('employee'))
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        // Restrict EmployeeStatus options based on ReportType
                        if ($state === 'SSS Contribution' || $state === 'Pagibig Contribution') {
                            $set('EmployeeStatus', 'Regular');
                        } else {
                            $set('EmployeeStatus', null); // Clear EmployeeStatus if ReportType changes to non-restricted type
                        }
                        $set('SelectPayroll', null);
                    }),

                Select::make('SelectPayroll')
                    ->label('Select Date')
                    ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                    ->options(function (callable $get) {
                        // Get the current ReportType value
                        $reportType = $get('ReportType');

                        // Start the query to fetch payrolls
                        $payrollsQuery = Payroll::orderBy('PayrollYear')
                            ->orderBy('PayrollMonth')
                            ->orderBy('PayrollDate2');

                        // Apply filter only for "SSS Contribution" to show Regular employees only
                        if ($reportType === 'SSS Contribution' || $reportType === 'Pagibig Contribution') {
                            $payrollsQuery->where('EmployeeStatus', 'Regular');
                        }

                        return $payrollsQuery->get()->mapWithKeys(function ($payroll) {
                            $displayText = "{$payroll->PayrollMonth}, {$payroll->PayrollYear} | {$payroll->EmployeeStatus} - {$payroll->assignment}";
                            return [$payroll->id => $displayText];
                        });
                    })
                    ->placeholder('Select Date Option')
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        $set('EmployeeStatus', null);
                        $set('PayrollFrequency', null);
                        $set('PayrollMonth', null);
                        $set('PayrollYear', null);
                        $set('PayrollDate2', null);
                        $set('assignment', null);
                        $set('ProjectID', null);
                        $set('weekPeriodID', null);

                        if ($state) {
                            $payroll = Payroll::find($state);
                            if ($payroll) {
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

                Fieldset::make('Payroll Details')// Create a two-column grid layout for the first two fields
                    ->schema([

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
                                if ($state === 'Regular') {
                                    $set('PayrollFrequency', 'Kinsenas');
                                } else {
                                    $set('PayrollFrequency', 'Weekly');
                                }
                            })
                            ->hidden(),


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
                            ->reactive()
                            ->hidden(),

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
                                        // Log the exception or handle it as needed
                                        Log::error('Error fetching week periods: ' . $e->getMessage());
                                        return [];
                                    }
                                }

                                // If any of the fields are not set, return an empty array
                                return [];
                            })
                            ->native(false)
                            ->reactive() // Make this field reactive to other fields
                            ->placeholder('Select the payroll period')
                            ->visible(function (callable $get) {
                                // Display if SelectPayroll has a value
                                return !empty($get('SelectPayroll') && $get('ReportType') === 'Payslip');
                            }),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ReportType')
                    ->label('Report Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('EmployeeStatus')
                    ->label('Employee Type')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('assignment')
                    ->label('Assignment')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('project.ProjectName')
                    ->label('Project')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('PayrollMonth')
                    ->Label('Payroll Month'),

                Tables\Columns\TextColumn::make('PayrollYear')
                    ->Label('Payroll Year'),

                // Tables\Columns\TextColumn::make('PayrollFrequency')
                //     ->Label('Payroll Frequency'),

                // Tables\Columns\TextColumn::make('PayrollDate2')
                //     ->label('Payroll Dates')
                //     ->searchable()
                //     ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('viewSummary')
                    ->hidden(fn($record) => $record->trashed())
                    ->label('View Payslip')
                    ->color('success')
                    ->form([
                        Select::make('SelectEmployee')
                            ->label('Select Employee')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function (callable $get, $record) {
                                $projectID = $record->ProjectID;
                                $EmployeeStatus = $record->EmployeeStatus;

                                if ($projectID) {
                                    // Fetch employees associated with the selected project and concatenate first_name and last_name
                                    $employees = \App\Models\Employee::where('project_id', $projectID)
                                        ->where('employment_type', $EmployeeStatus)
                                        ->get()
                                        ->mapWithKeys(function ($employee) {
                                        return [$employee->id => "{$employee->first_name} {$employee->last_name}"];
                                    });

                                    // Add "All Employees in this Project" option
                                    return ['All' => 'All Employees'] + $employees->toArray();
                                } else {
                                    $employees = \App\Models\Employee::where('employment_type', $EmployeeStatus)
																				->whereNull('project_id')
                                        ->get()
                                        ->mapWithKeys(function ($employee) {
                                            return [$employee->id => "{$employee->first_name} {$employee->last_name}"];
                                        });

                                    // Add "All Employees in this Project" option
                                    return ['All' => 'All Employees'] + $employees->toArray();
                                }
                                return []; // Return empty if no projectID
                            })
                            ->placeholder('Select Employee')
                            ->reactive()
                    ])
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->ReportType === 'Payslip')
                    ->action(function (array $data, $record) {
                        $employeeId = $data['SelectEmployee'] === 'All' ? 'All' : $data['SelectEmployee'];

                        return redirect()->to(route('generate.payslips', [
                            'employee_id' => $employeeId,
                            'record' => $record->toArray(),
                        ]));
                    }),


                Tables\Actions\Action::make('generateReport')
                    ->hidden(fn($record) => $record->trashed())
                    ->label('Generate Report')
                    ->icon('heroicon-o-calculator')
                    ->color('info')
                    ->url(fn($record) => route('generate.reports', $record->toArray()))
                    ->openUrlInNewTab()
                    ->visible(fn($record) => $record->ReportType != 'Payslip'),

                
                    Tables\Actions\DeleteAction::make()->label('Archive')
                    ->modalSubmitActionLabel('Archived')
                    ->modalHeading('Archived Report')
                    ->hidden(fn($record) => $record->trashed())
                    ->successNotificationTitle('Report Archived'),

                    Tables\Actions\RestoreAction::make(),
                // Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
}
