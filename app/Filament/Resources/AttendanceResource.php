<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PhpParser\Node\Stmt\Label;
use Illuminate\Support\Facades\Session;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;
    protected static ?string $navigationIcon = 'heroicon-s-view-columns';
    protected static ?string $title = 'Attendance';
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Add your form fields here if needed
        ]);
    }



    public static function table(Table $table): Table
    {


        return $table
            ->columns([
                TextColumn::make('Date')
                    ->label('Date')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return \Carbon\Carbon::parse($record->Date)->format('F j, Y');
                    }),
                TextColumn::make('Checkin_One')->label('Morning Check-in'),
                TextColumn::make('Checkout_One')->label('Morning Checkout'),
                TextColumn::make('Checkin_Two')->label('Afternoon Check-in'),
                TextColumn::make('Checkout_Two')->label('Afternoon Checkout'),
                TextColumn::make('Overtime_In')->label('Overtime In'),
                TextColumn::make('Overtime_Out')->label('Overtime Out'),
                TextColumn::make('Total_Hours')->label('Total Hours')->sortable(),
            ])
            ->recordUrl(function ($record) {
                return null;
            })
            ->filters(
                [
                    Filter::make('employee_filter')
                        ->form([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('selectedEmployeeId')
                                        ->label('Select Employee')
                                        ->options(Employee::all()->pluck('full_name', 'id'))
                                        ->extraAttributes(['class' => 'h-12 text-lg', 'style' => 'width: 100%;'])
                                        ->reactive()
                                        ->afterStateUpdated(function ($state) {
                                            if (!empty($state)) {
                                                Session::put('selected_employee_id', $state);
                                            } else {
                                                Session::forget('selected_employee_id');
                                            }
                                        })
                                        ->required()
                                        ->placeholder('Select an Employee'),
                                ])
                                ->columns(1),
                        ])
                        ->query(function (Builder $query, array $data) {
                            if (!empty($data['selectedEmployeeId'])) {
                                Session::put('selected_employee_id', $data['selectedEmployeeId']);
                                $query->where('employee_id', $data['selectedEmployeeId']);
                            } else {
                                Session::forget('selected_employee_id');
                            }
                            return $query;
                        }),


                    Filter::make('project_filter')
                        ->form([
                            Forms\Components\Grid::make()
                                ->schema([
                                    Forms\Components\Select::make('selectedProjectId')
                                        ->label('Select Project')
                                        ->options(Project::all()->pluck('ProjectName', 'id'))
                                        ->extraAttributes(['class' => 'h-12 text-lg', 'style' => 'width: 100%;'])
                                        ->required(),
                                ])
                                ->columns(1),
                        ])
                        ->query(
                            function (Builder $query, array $data) {
                                if (!empty($data['selectedProjectId'])) {
                                    Session::put('selected_project_id', $data['selectedProjectId']);
                                    $query->where('ProjectID', $data['selectedProjectId']); // Make sure to use project_id for filtering
                                } else {
                                    $query->where(function ($query) {
                                        $query->where('ProjectID', 0)
                                            ->orWhereNull('ProjectID');
                                    }); // Make sure to use project_id for filtering
                                    Session::put('selected_project_id', null);
                                }
                                return $query;
                            }
                        ),

                    Filter::make('start_date')
                        ->form([
                            Forms\Components\TextInput::make('start_date')
                                ->label('Start Date')
                                ->type('date')
                                ->default(now()->startOfMonth()->toDateString())
                        ])
                        ->query(
                            function (Builder $query, $data) {
                                if (!empty($data['start_date'])) {
                                    Session::put('startDate', $data['start_date']);
                                    $query->whereBetween('Date', [$data['start_date'], Session::get('endDate')]);
                                    // $query->where('Date', '>=', $data['start_date']);
                                }
                                return $query;
                            }
                        ),

                    Filter::make('end_date')
                        ->form([
                            Forms\Components\TextInput::make('end_date')
                                ->label('End Date')
                                ->type('date')
                                ->default(now()->endOfMonth()->toDateString())
                        ])
                        ->query(
                            function (Builder $query, $data) {
                                if (!empty($data['end_date'])) {
                                    Session::put('endDate', $data['end_date']);
                                    $query->whereBetween('Date', [Session::get('startDate'), $data['end_date']]);
                                    // $query->where('Date', '>=', $data['end_date']);
                                }
                                return $query;
                            }
                        ),

                ],

                layout: FiltersLayout::AboveContent
            )

            ->headerActions([
                Action::make('uploadBiometrics')
                    ->label('Upload Biometrics')
                    ->action(function ($record, $data) {
                  
                        $file = $data['file'];

                        $validator = Validator::make($data, [
                            'file' => 'required|file|max:10240', 
                        ]);
                        if ($validator->fails()) {
                            return back()->withErrors($validator)->withInput();
                        }

                        $extension = $file->getClientOriginalExtension();
                        if ($extension !== 'txt') {
                            return back()->withErrors(['file' => 'The file must be a text file.'])->withInput();
                        }
                        $path = $file->storeAs('uploads', $file->getClientOriginalName());

                        $content = Storage::get($path);

                        $lines = explode("\n", $content);

                        foreach ($lines as $index => $line) {
                            if ($index == 0)
                                continue; 
            
                                $columns = explode(',', $line);

                            if (count($columns) >= 4) {
                                $employeeId = $columns[0]; 
                                $date = $columns[1];        
                                $time = $columns[2];       
                                $status = $columns[3];      
            
                                $employee = Employee::where('Employee_ID', $employeeId)->first();

                                if ($employee) {
                                    $timestamp = Carbon::parse("$date $time");

                                    $attendanceData = [
                                        'Employee_ID' => $employee['id'],  
                                    ];
                                    if ($status == 'Check-in') {
                                        if ($timestamp->between(Carbon::createFromTime(8, 0), Carbon::createFromTime(10, 0))) {
                                            $attendanceData['Checkin_One'] = $timestamp;
                                        } elseif ($timestamp->between(Carbon::createFromTime(12, 31), Carbon::createFromTime(13, 0))) {
                                            $attendanceData['Checkin_Two'] = $timestamp;
                                        }
                                    } elseif ($status == 'Check-out') {
                                        if ($timestamp->between(Carbon::createFromTime(12, 0), Carbon::createFromTime(12, 30))) {
                                            $attendanceData['Checkout_One'] = $timestamp;
                                        } elseif ($timestamp->gt(Carbon::createFromTime(13, 0))) {
                                            $attendanceData['Checkout_Two'] = $timestamp;
                                        }
                                    }
                                    if (isset($attendanceData['Checkin_One']) || isset($attendanceData['Checkout_One']) || isset($attendanceData['Checkin_Two']) || isset($attendanceData['Checkout_Two'])) {
                                        Attendance::create($attendanceData); 
                                    }
                                }
                            }
                        }

                        session()->flash('success', 'File uploaded and attendance data inserted successfully.');
                        return redirect()->back();  
                    })
                    ->form(function (Forms\Form $form) {
                        return $form->schema([
                            Forms\Components\FileUpload::make('file')
                                ->label('ZKTeco Biometrics File')
                                ->required(),  
                        ]);
                    })
                    ->color('info'),

                Action::make('viewDtr')
                    ->label('View DTR')
                    ->color('primary')
                    ->url(fn() => route('dtr.show', [
                        'employee_id' => Session::get('selected_employee_id'),
                        'startDate' => Session::get('startDate'),
                        'endDate' => Session::get('endDate'),
                        'project_id' => Session::get('selected_project_id'),
                    ]))
                    ->openUrlInNewTab()
                    ->disabled(function () {
                        $selectedEmployee = Session::get('selected_employee_id');
                        return empty($selectedEmployee);
                    })->tooltip(function () {

                        $selectedEmployee = Session::get('selected_employee_id');

                        if (empty($selectedEmployee)) {
                            return 'Please select an Employee to proceed';
                        }

                        return '';
                    }),
                Action::make('viewSummary')
                    ->openUrlInNewTab()
                    ->label('View Attendance Summary')
                    ->color('success')
                    ->form([
                        Select::make('SelectPayroll')
                            ->label('Select Payroll') // Label for the field
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function () {
                                return Payroll::with('project')
                                    ->orderBy('PayrollYear')
                                    ->orderBy('PayrollMonth')
                                    ->orderBy('PayrollDate2')
                                    ->get()
                                    ->mapWithKeys(function ($payroll) {
                                        $projectName = $payroll->project->ProjectName ?? 'No Project'; // Use 'No Project' if the name is null
                                        $displayText = "{$payroll->PayrollMonth},{$payroll->PayrollYear} - {$payroll->PayrollDate2} | {$payroll->EmployeeStatus} - {$payroll->assignment} | {$projectName}";
                                        return [$payroll->id => $displayText];
                                    });
                            })
                            ->placeholder('Select Payroll Option')
                            ->reactive()
                    ])

                    ->deselectRecordsAfterCompletion()
                    ->action(function (array $data) {
                        return redirect()->to(route('dtr.summary', [
                            'payroll_id' => $data['SelectPayroll'],
                        ]));
                    })
                    ->openUrlInNewTab()

            ])
            ->bulkActions([]);


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
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            // 'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
