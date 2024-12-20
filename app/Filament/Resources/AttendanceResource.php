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
        return $form->schema([]);
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
                                    $query->where('ProjectID', $data['selectedProjectId']);
                                } else {
                                    $query->where(function ($query) {
                                        $query->where('ProjectID', 0)
                                            ->orWhereNull('ProjectID');
                                    });
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
                        $validator = Validator::make($data, [
                            'file' => 'required|string',
                        ]);
                        // dd($validator);
                        if ($validator->fails()) {
                            return back()->withErrors($validator)->withInput();
                        }
                        $filePath = $data['file'];

                        if (!Storage::exists($filePath)) {
                            return back()->withErrors(['file' => 'The selected file does not exist.'])->withInput();
                        }

                        $content = Storage::get($filePath);

                        try {
                            $content = Storage::get($filePath);
                            $lines = explode("\n", $content);

                            foreach ($lines as $index => $line) {
                                if (empty(trim($line))) {
                                    continue;
                                }
                                try {
                                    $columns = preg_split('/\t+/', $line);
                                    logger()->info('Processing line columns:', $columns);

                                    if (count($columns) >= 4) {
                                        $employeeId = $columns[0];

                                        $dateTime = explode(' ', $columns[1]);
                                        $date = $dateTime[0];
                                        $time = $dateTime[1];

                                        $employee = Employee::where('id', $employeeId)->first();
                                        if ($employee) {
                                            $attendanceData = [
                                                'Employee_ID' => $employeeId,
                                                'Date' => $date,
                                            ];

                                            $timestamp = Carbon::parse("$date $time", 'Asia/Manila')->format('H:i:s');
                                            $timestampCarbon = Carbon::parse($timestamp, 'Asia/Manila');

                                            $checkinEnd = Carbon::createFromTime(10, 30, 0, 'Asia/Manila');
                                            $checkinTwoStart = Carbon::createFromTime(12, 31, 0, 'Asia/Manila');
                                            $checkinTwoEnd = Carbon::createFromTime(15, 29, 0, 'Asia/Manila');
                                            $checkoutOneStart = Carbon::createFromTime(10, 31, 0, 'Asia/Manila');
                                            $checkoutOneEnd = Carbon::createFromTime(12, 30, 0, 'Asia/Manila');
                                            $checkoutTwoStart = Carbon::createFromTime(15, 30, 0, 'Asia/Manila');

                                            if ($timestampCarbon->lt($checkinEnd)) {
                                                $attendanceData['Checkin_One'] = $timestampCarbon->format('H:i:s');
                                            } elseif ($timestampCarbon->between($checkinTwoStart, $checkinTwoEnd)) {
                                                $attendanceData['Checkin_Two'] = $timestampCarbon->format('H:i:s');
                                            } elseif ($timestampCarbon->between($checkoutOneStart, $checkoutOneEnd)) {
                                                $attendanceData['Checkout_One'] = $timestampCarbon->format('H:i:s');
                                            } elseif ($timestampCarbon->gt($checkoutTwoStart)) {
                                                $attendanceData['Checkout_Two'] = $timestampCarbon->format('H:i:s');
                                            }

                                            if (
                                                isset($attendanceData['Checkin_One']) ||
                                                isset($attendanceData['Checkout_One']) ||
                                                isset($attendanceData['Checkin_Two']) ||
                                                isset($attendanceData['Checkout_Two'])
                                            ) {
                                                $existingAttendance = Attendance::where('Employee_ID', $employeeId)
                                                    ->where('Date', $date)
                                                    ->first();
                                                if ($existingAttendance) {
                                                    $existingAttendance->update(array_filter($attendanceData));
                                                } else {
                                                    Attendance::create($attendanceData);
                                                }
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    logger()->error('Error processing line: ' . $line, ['error' => $e->getMessage()]);
                                }
                            }

                            session()->flash('success', 'File uploaded and attendance data inserted successfully.');
                        } catch (\Exception $e) {
                            logger()->error('File processing error:', ['error' => $e->getMessage()]);
                            return back()->withErrors(['file' => 'An error occurred while processing the file. Please check the logs.'])->withInput();
                        }

                        return redirect()->back();
                    })
                    ->form(function (Forms\Form $form) {
                        return $form->schema([
                            Forms\Components\FileUpload::make('file')
                                ->label('ZKTeco Biometrics File')
                                ->disk('local')
                                ->directory('uploads')
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
                            ->label('Select Payroll')
                            ->required(fn(string $context) => $context === 'create' || $context === 'edit')
                            ->options(function () {
                                return Payroll::with('project')
                                    ->orderBy('PayrollYear')
                                    ->orderBy('PayrollMonth')
                                    ->orderBy('PayrollDate2')
                                    ->get()
                                    ->mapWithKeys(function ($payroll) {
                                        $projectName = $payroll->project->ProjectName ?? 'No Project';
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

        ];
    }
}
