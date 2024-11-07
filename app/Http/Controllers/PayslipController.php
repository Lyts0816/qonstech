<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Dompdf\Dompdf;
use App\Models\LoanDtl;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Dompdf\Options;

class PayslipController extends Controller
{

    public function generatePayslips(Request $request)
    {
        // dd($request->record);
        // Initialize Dompdf instance


        $dompdf = new Dompdf();
        // Render each employee's payslip
        $payslipHtml = '';



        $employeesWPosition = \App\Models\Employee::where('employment_type', $request->record['EmployeeStatus'])
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate'); // Only select needed fields
        // $validator = Validator::make($request->all(), [
        //     'EmployeeStatus' => 'required|string',
        //     'assignment' => 'required|string',
        //     'ProjectID' => 'nullable|string|integer', // Adjust according to your requirement
        // ]);

        // if ($validator->fails()) {
        //     return response()->json($validator->errors(), 422);
        // }

        // Query for employees with their position
        $employeesWPosition = \App\Models\Employee::where('employment_type', $request->record['EmployeeStatus'])
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate');


        // Check if the assignment is project-based
        if ( $request->record['assignment'] === 'Project Based') {
            // If project-based, filter by project
            $employeesWPosition = $employeesWPosition->whereNotNull('project_id') // Ensure the employee has a project
                ->where('project_id', $request->record['ProjectID']); // Filter by specific project
        } else {
            // If not project-based, filter employees without a project
            $employeesWPosition = $employeesWPosition->whereNull('project_id'); // Ensure the employee does not have a project
        }
        // dd($employeesWPosition->get());
        // Execute the query and get results
        $employeesWPosition = $employeesWPosition->get();



        $payrollRecords = collect();

        foreach ($employeesWPosition as $employee) {
            // dd( $employee);
            $newRecord = $request->all();
            $newRecord['EmployeeID'] = $employee->id;
            $newRecord['first_name'] = $employee->first_name;
            $newRecord['middle_name'] = $employee->middle_name ?? Null;
            $newRecord['last_name'] = $employee->last_name;
            $newRecord['position'] = $employee->PositionName;
            $newRecord['monthlySalary'] = $employee->MonthlySalary;
            $newRecord['hourlyRate'] = $employee->HourlyRate;
            $newRecord['EmployeeStatus'] = $employee->employment_type;
            $newRecord['SalaryType'] = 'OPEN';
            $newRecord['TotalTardinessDed'] = 0;
            $newRecord['TotalUndertimeDed'] = 0;
            $newRecord['RegularStatus'] = $employee->employment_type == 'Regular' ? 'YES' : 'NO';
            // Check if the employee has a project_id
            if ($employee->project_id) {
                // Retrieve the project name associated with the employee's project_id
                $project = \App\Models\Project::find($employee->project_id);

                // Store the project name in the newRecord if the project is found
                if ($project) {
                    $newRecord['ProjectName'] = $project->ProjectName; // Assuming 'name' is the field for the project name
                } else {
                    $newRecord['ProjectName'] = 'Main Office'; // Handle case where project is not found
                }
            } else {
                $newRecord['ProjectName'] = 'Main Office'; // Handle case where there is no project assigned
            }


            // Check if payroll frequency is Kinsenas or Weekly
            $weekPeriod = \App\Models\WeekPeriod::where('id', $request->record['weekPeriodID'])->first();
            $newRecord['Period'] = $weekPeriod->StartDate . ' - ' . $weekPeriod->EndDate;

            // dd($newRecord['ProjectName'], $newRecord['Period']);
            if ($weekPeriod) {
                // For Kinsenas (1st Kinsena or 2nd Kinsena)
                if ($weekPeriod->Category == 'Kinsenas') {
                    if (in_array($weekPeriod->Type, ['1st Kinsena', '2nd Kinsena'])) {
                        $startDate = $weekPeriod->StartDate;
                        $endDate = $weekPeriod->EndDate;
                    } else {
                        // Default to the first half of the month if no specific Type is found
                        $startDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 1);
                        $endDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 15);
                    }

                    // Get attendance between startDate and endDate
                    $attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
                        ->whereBetween('Date', [$startDate, $endDate])
                        ->get();

                } elseif ($weekPeriod->Category == 'Weekly') {
                    // For Weekly (Week 1, Week 2, Week 3, or Week 4)
                    if (in_array($weekPeriod->Type, ['Week 1', 'Week 2', 'Week 3', 'Week 4'])) {
                        $startDate = $weekPeriod->StartDate;
                        $endDate = $weekPeriod->EndDate;
                    } else {
                        // Default to the first week if no specific period is found
                        $startDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 1);
                        $endDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 7);
                    }

                    // Get attendance between startDate and endDate
                    $attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
                        ->whereBetween('Date', [$startDate, $endDate])
                        ->orderBy('Date', 'ASC')
                        ->get();
                }
            }

            $finalAttendance = $attendance;
            $TotalHours = 0;
            $TotalHoursSunday = 0;
            $TotalHrsSpecialHol = 0;
            $TotalHrsRegularHol = 0;
            $TotalEarningPay = 0;
            $TotalDeductions = 0;
            $TotalGovDeductions = 0;
            $TotalOfficeDeductions = 0;
            $SSSDeduction = 0;
            $WTAXDeduction = 0;
            $PagIbigDeduction = 0;
            $PhilHealthDeduction = 0;
            $EarningPay = 0;
            $RegHolidayWorkedHours = 0; // initialize as zero
            $SpecialHolidayWorkedHours = 0;
            $TotalOvertimeHours = 0;
            $TotalOvertimePay = 0;
            $TotalTardiness = 0;
            $TotalUndertime = 0;
            $DeductionFee = 0;

            foreach ($finalAttendance as $attendances) {
                // dd($attendances);
                $attendanceDate = Carbon::parse($attendances['Date']);
                $GetHoliday = \App\Models\Holiday::where('HolidayDate', substr($attendanceDate, 0, 10))->get();
                $Holiday = $GetHoliday;

                //Get the workschedule based on Schedule assign to employee
                $GetWorkSched = \App\Models\WorkSched::where('ScheduleName', $employee['schedule']->ScheduleName)->get();
                $WorkSched = $GetWorkSched;

                if (
                    ($WorkSched[0]->monday == $attendanceDate->isMonday() && $attendanceDate->isMonday() == 1)
                    || ($WorkSched[0]->tuesday == $attendanceDate->isTuesday() && $attendanceDate->isTuesday() == 1)
                    || ($WorkSched[0]->wednesday == $attendanceDate->isWednesday() && $attendanceDate->isWednesday() == 1)
                    || ($WorkSched[0]->thursday == $attendanceDate->isThursday() && $attendanceDate->isThursday() == 1)
                    || ($WorkSched[0]->friday == $attendanceDate->isFriday() && $attendanceDate->isFriday() == 1)
                    || ($WorkSched[0]->saturday == $attendanceDate->isSaturday() && $attendanceDate->isSaturday() == 1)
                    || ($WorkSched[0]->sunday == $attendanceDate->isSunday() && $attendanceDate->isSunday() == 1)
                ) {
                    $In1 = $WorkSched[0]->CheckinOne;
                    $In1Array = explode(':', $In1);

                    $Out1 = $WorkSched[0]->CheckoutOne;
                    $Out1Array = explode(':', $Out1);

                    $In2 = $WorkSched[0]->CheckinTwo;
                    $In2Array = explode(':', $In2);

                    $Out2 = $WorkSched[0]->CheckoutTwo;
                    $Out2Array = explode(':', $Out2);

                    // Check if the attendance date is a Sunday

                    if ($attendanceDate->isSunday()) {
                        // Set official work start and end times
                        $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
                        $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
                        $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
                        $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

                        // Calculate morning shift times (ignoring seconds)
                        $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
                        $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

                        // Calculate late time for the morning (in hours)
                        // $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;

                        // Calculate worked hours for morning shift (in hours)
                        $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
                        $effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
                        $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
                        $underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
                        $tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
                        $workedMorningHours = $workedMorningMinutes / 60;
                        // $workedMorningHours = $checkinOne->diffInMinutes($checkoutOne) / 60;

                        // Calculate afternoon shift times (ignoring seconds)
                        $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
                        $checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

                        // Calculate late time for the afternoon (in hours)
                        $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

                        // Calculate worked hours for afternoon shift (in hours)
                        $effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
                        $effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
                        $workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
                        $underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
                        $tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
                        $workedAfternoonHours = $workedAfternoonMinutes / 60;
                        // $workedAfternoonHours = $checkinTwo->diffInMinutes($checkoutTwo) / 60;

                        // Total worked hours minus late hours
                        $totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
                        // $totalLateHours = $lateMorningHours + $lateAfternoonHours;
                        $SundayWorkedHours = $totalWorkedHours;
                        // $SundayWorkedHours = $totalWorkedHours - $totalLateHours;
                        // $SundayWorkedHours = $totalSundayWorkedHours - $totalSundayLateHours;

                        // $TotalHours += $netWorkedHours;
                        $TotalHoursSunday += $SundayWorkedHours; // Add to Sunday worked hours
                        $TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
                            + ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

                        $TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
                            + ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

                        $newRecord['TotalTardiness'] = $TotalTardiness;
                        $newRecord['TotalUndertime'] = $TotalUndertime;


                        $deduction = $employee->HourlyRate * ($TotalTardiness / 60);

                        $newRecord['TotalTardinessDed'] = $deduction;
                       
                        // * $employee->HourlyRate;
                        $UndertimetoHours = $TotalUndertime / 60;

                            $newRecord['TotalUndertimeDed'] = $employee->HourlyRate * $UndertimetoHours;
                    } else { // regular day monday to saturday
                        // If date is Holiday
                        // dd(count(value: $Holiday));

                        // check if Holiday exist on $attendanceDate AND if that holiday is used for the project/area
                        if (count(value: $Holiday) > 0 && $Holiday[0]->ProjectID == $employee->project_id) {
                            $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
                            $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
                            $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
                            $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

                            $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
                            $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

                            // $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;

                            $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
                            $effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
                            $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
                            $underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
                            $tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
                            $workedMorningHours = $workedMorningMinutes / 60;
                            // $workedMorningHours = $checkinOne->diffInMinutes($checkoutOne) / 60;

                            $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
                            $checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

                            // $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

                            $effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
                            $effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
                            $workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
                            $underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
                            $tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
                            $workedAfternoonHours = $workedAfternoonMinutes / 60;
                            // $workedAfternoonHours = $checkinTwo->diffInMinutes($checkoutTwo) / 60;

                            $totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
                            // $totalLateHours = $lateMorningHours + $lateAfternoonHours;

                            // Check type of Holiday
                            if ($Holiday[0]->HolidayType == 'Regular') {
                                $RegHolidayWorkedHours = $totalWorkedHours;
                                // $RegHolidayWorkedHours = $totalWorkedHours - $totalLateHours;
                                $TotalHrsRegularHol += $RegHolidayWorkedHours;
                                $newRecord['TotalHrsRegularHol'] = $TotalHrsRegularHol;

                            } else if ($Holiday[0]->HolidayType == 'Special') {
                                $SpecialHolidayWorkedHours = $totalWorkedHours;
                                // $SpecialHolidayWorkedHours = $totalWorkedHours - $totalLateHours;
                                $TotalHrsSpecialHol += $SpecialHolidayWorkedHours;
                                $newRecord['TotalHrsSpecialHol'] = $TotalHrsSpecialHol;

                            }

                            $TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
                                + ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

                            $TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
                                + ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

                                $newRecord['TotalTardiness'] = $TotalTardiness;
                        $newRecord['TotalUndertime'] = $TotalUndertime;


                        $deduction = $employee->HourlyRate * ($TotalTardiness / 60);

                        $newRecord['TotalTardinessDed'] = $deduction;
                        // * $employee->HourlyRate;
                        $UndertimetoHours = $TotalUndertime / 60;

                            $newRecord['TotalUndertimeDed'] = $employee->HourlyRate * $UndertimetoHours;
                            // else {
                            // 	$netWorkedHours = $totalWorkedHours - $totalLateHours;
                            // }

                            // $TotalHours += $netWorkedHours;
                        } else { // regular Day
                            $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
                            $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
                            $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
                            $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

                            $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
                            $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

                            // $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningStart) / 60 : 0;

                            $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
                            $effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
                            $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
                            $underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
                            $tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
                            $workedMorningHours = $workedMorningMinutes / 60;
                            // $workedMorningHours = $checkinOne->diffInMinutes($morningEnd) / 60;

                            $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
                            $checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

                            // $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

                            $effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
                            $effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
                            $workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
                            $underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
                            $tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
                            $workedAfternoonHours = $workedAfternoonMinutes / 60;

                            $totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
                            // $totalLateHours = $lateMorningHours + $lateAfternoonHours;
                            $netWorkedHours = $totalWorkedHours
                            ;
                            // $netWorkedHours = $totalWorkedHours - $totalLateHours;
                            // $SundayWorkedHours = $totalSundayWorkedHours - $totalSundayLateHours;

                            $TotalHours += $netWorkedHours;
                            $TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
                                + ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

                            $TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
                                + ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

                                $newRecord['TotalTardiness'] = $TotalTardiness;
                                $newRecord['TotalUndertime'] = $TotalUndertime;
        
        
                                $deduction = $employee->HourlyRate * ($TotalTardiness / 60);
        
                                $newRecord['TotalTardinessDed'] = $deduction;
                                // dd( $newRecord);
                                // * $employee->HourlyRate;
                                $UndertimetoHours = $TotalUndertime / 60;
        
                                    $newRecord['TotalUndertimeDed'] = $employee->HourlyRate * $UndertimetoHours;
                        }
                    }
                }

            }
            // dd($newRecord['EmployeeID);
            $attendance = \App\Models\Attendance::where('Employee_ID', $employee->id)
                ->whereBetween('Date', [$startDate, $endDate])
                ->get();

            // Fetch approved overtime records for the employee within the selected week period
            $approvedOvertimeRecords = \App\Models\Overtime::where('EmployeeID', $employee->id)
                ->get();

            // Get the work schedule for the employee using the related schedule
            $GetWorkSched = \App\Models\WorkSched::where('ScheduleName', $employee['schedule']->ScheduleName)->first();

            $totalOvertimeHours = 0;

            foreach ($attendance as $attendanceRecord) {
                // Get the day of the week (e.g., 'monday', 'tuesday')
                $dayOfWeek = strtolower(Carbon::parse($attendanceRecord->Date)->format('l'));

                // Ensure that the employee has a schedule for the specific day
                if (!empty($GetWorkSched) && $GetWorkSched->$dayOfWeek) {
                    // Parse the regular work hours (CheckinOne, CheckoutOne)
                    $workStart = Carbon::parse($GetWorkSched->CheckinOne);
                    $workEnd = Carbon::parse($GetWorkSched->CheckoutTwo);

                    // Attendance check-in and check-out times
                    $attendanceCheckin = Carbon::parse($attendanceRecord->Checkin_One);
                    $attendanceCheckout = Carbon::parse($attendanceRecord->Checkout_Two);

                    // Check if the employee has approved overtime for this date
                    $overtimeRecord = $approvedOvertimeRecords->firstWhere('Date', $attendanceRecord->Date);

                    if ($overtimeRecord) {
                        // Calculate overtime if attendance is outside regular work hours and there is an approved overtime schedule
                        $overtimeHoursForDay = 0;
                        $totalOvertimeHours = 2.5;
                        
                        // Check if check-in is before regular work hours
                        if ($attendanceCheckin->lt($workStart)) {
                            $overtimeMinutesBefore = $attendanceCheckin->diffInMinutes($workStart);
                            $overtimeHoursForDay += $overtimeMinutesBefore / 60;
                        }

                        // Check if check-out is after regular work hours
                        if ($attendanceCheckout->gt($workEnd)) {
                            $overtimeMinutesAfter = $workEnd->diffInMinutes($attendanceCheckout);
                            $overtimeHoursForDay += $overtimeMinutesAfter / 60;
                        }

                        // If the attendance is outside regular work hours and overtime is approved, add to the total overtime hours
                        if ($overtimeHoursForDay > 0) {
                            $totalOvertimeHours = 2.5; // Add 2.5 hours for the break
                        }
                    }
                }

                // Additional check for overtime that is outside of regular work schedule
                if ($approvedOvertimeRecords->contains('Date', $attendanceRecord->Date) && !$GetWorkSched->$dayOfWeek) {
                    $overtimeRecord = $approvedOvertimeRecords->firstWhere('Date', $attendanceRecord->Date);
                    if ($overtimeRecord) {

                        $totalOvertimeHours = 2.5;
                        // Calculate total hours based on check-in and check-out times without work schedule comparison
                        $attendanceCheckin = Carbon::parse($attendanceRecord->Checkin_One);
                        $attendanceCheckout = Carbon::parse($attendanceRecord->Checkout_Two);

                        // Calculate the total overtime hours for the day
                        $overtimeHoursForDay = $attendanceCheckin->diffInMinutes($attendanceCheckout) / 60;

                        // Adjust for break if overtime exceeds 8 hours
                        if ($overtimeHoursForDay > 8) {
                            $overtimeHoursForDay -= 1; // Subtract 1 hour for the break
                        }

                        // Add to total overtime hours
                        if ($overtimeHoursForDay > 0) {
                            $totalOvertimeHours = 2.5;
                        }
                    }
                }
            }

            // Store the total overtime hours in the new record
            $newRecord['TotalOvertimeHours'] = $totalOvertimeHours;


            // For Earnings
            $GetEarnings = \App\Models\Earnings::where('PeriodID', $request->record['weekPeriodID'])
                ->where('EmployeeID', $employee->id)
                ->get();
            $Earnings = $GetEarnings;

            if (count($Earnings) > 0) {
                $EarningPay = $Earnings[0]->Amount;
                $newRecord['EarningPay'] = $EarningPay;
                // $TotalEarningPay = $EarningPay;
            }


            // For Deductions
            $GetDeductions = \App\Models\Deduction::where('PeriodID', $request->record['weekPeriodID'])
                ->where('EmployeeID', $employee->id)
                ->get();
            $Deductions = $GetDeductions;

            if (count($Deductions) > 0) {
                $DeductionFee = $Deductions[0]->Amount;
                $newRecord['DeductionFee'] = $DeductionFee;
                // $TotalEarningPay = $EarningPay;
            }

            // Get the loan for the employee and period
            $loan = \App\Models\Loan::where('EmployeeID', $employee->id)
                ->where('PeriodID', $request->record['weekPeriodID'])
                ->first();
            if ($loan) {
                // Check if payroll is already generated for this period and employee
                $existingPayroll = \App\Models\Payroll::where('weekPeriodID', $request->record['weekPeriodID'])
                    ->exists();
                if ($existingPayroll) {
                    $newDeduction = new \App\Models\Deduction();
                    // Initialize variables for SSS and HDMF loan deductions
                    $SSSDeduction = 0;
                    $HDMFDeduction = 0;
                    // Deduct the number of payments based on LoanType
                    if ($loan->PaymentsRemaining > 0) {
                        switch ($loan->LoanType) {
                            case 'Monthly':
                                // For monthly loans, deduct 1 payment
                                $loan->PaymentsRemaining -= 1;
                                $loan->Balance -= $loan->MonthlyDeduction;
                                break;

                            case 'Kinsenas':
                                // For Kinsenas, deduct after 2 payroll periods in the same month
                                // Check how many payrolls have been generated for the month
                                $payrollCountKinsenas = \App\Models\Payroll::whereMonth('weekPeriodID', Carbon::now()->month)
                                    ->where('LoanType', 'Kinsenas')
                                    ->count();

                                if ($payrollCountKinsenas % 2 == 0) { // Deduct every 2 payrolls
                                    $loan->PaymentsRemaining -= 1;
                                    $loan->Balance -= $loan->KinsenaDeduction;

                                    // Store SSS and HDMF deductions
                                    $newRecord['$SSSDeduction'] = $loan->KinsenaDeduction;  // Assuming SSS loan for Kinsena
                                    $newRecord['$HDMFDeduction'] = $loan->KinsenaDeduction; // Assuming HDMF loan for Kinsena
                                }
                                break;

                            case 'Weekly':
                                // For weekly loans, deduct after 4 payroll periods in the same month
                                // Check how many payrolls have been generated for the month
                                $payrollCountWeekly = \App\Models\Payroll::whereMonth('weekPeriodID', Carbon::now()->month)
                                    ->where('LoanType', 'Weekly')
                                    ->count();

                                if ($payrollCountWeekly % 4 == 0) { // Deduct every 4 payrolls
                                    $loan->PaymentsRemaining -= 1;
                                    $loan->Balance -= $loan->WeeklyDeduction;

                                    // Store SSS and HDMF deductions
                                    $newRecord['$SSSDeduction'] = $loan->WeeklyDeduction;  // Assuming SSS loan for Weekly
                                    $newRecord['$HDMFDeduction'] = $loan->WeeklyDeduction; // Assuming HDMF loan for Weekly
                                }
                                break;
                        }

                        // Ensure the balance doesn't go below zero
                        if ($loan->Balance < 0) {
                            $loan->Balance = 0;
                        }

                        // Save the updated loan record
                        $loan->save();
                    }
                }
            }

            // Get loans for the employee filtered by LoanType
            $loans = \App\Models\Loan::where('EmployeeID', $employee->id)
                ->whereIn('LoanType', ['SSS Loan', 'Pagibig Loan', 'Salary Loan']) // Filter by loan types
                ->get();

            // Initialize new record array for deduction amounts
            $newRecord['SSSLoan'] = 0;
            $newRecord['PagibigLoan'] = 0;
            $newRecord['SalaryLoan'] = 0; 
            
            

            // Iterate over each loan to calculate deductions
            foreach ($loans as $loan) {
                // Get loan details for the specific week period
                $loanDetails = LoanDtl::where('LoanID', $loan->id) // Assuming LoanID is the foreign key in LoanDetail
                    ->where('PeriodID', $request->record['weekPeriodID'])
                    ->get();

                // Initialize deduction amount for this loan type
                $deductionAmount = 0;

                // Iterate over loan details to calculate the total deduction amount
                foreach ($loanDetails as $detail) {
                    // Sum up the deduction amounts
                    $deductionAmount += $detail->Amount; // Assuming Amount is the deduction amount in LoanDtl

                    // Check if the detail is already paid; if not, proceed to update
                    if (!$detail->IsPaid) { // Assuming 'isPaid' is a boolean column indicating payment status
                        // Mark loan detail as paid
                        $detail->IsPaid = true;
                        $detail->save(); // Save the updated loan detail

                        // Deduct the payment from the loan balance
                        $loan->Balance -= $detail->Amount; // Deduct the amount from loan balance
                    }
                }

                // Store the deduction amount in the new record array by loan type
                switch ($loan->LoanType) {
                    case 'SSS Loan':
                        $newRecord['SSSLoan'] += $deductionAmount; // Add to SSSLoan
                        break;
                    case 'Pagibig Loan':
                        $newRecord['PagibigLoan'] += $deductionAmount; // Add to PagibigLoan
                        break;
                    case 'Salary Loan':
                        $newRecord['SalaryLoan'] += $deductionAmount; // Add to SalaryLoan
                        break;
                }

                //Save the updated loan after processing all loan details
                if ($loan->Balance < 0) {
                    $loan->Balance = 0; // Ensure the balance does not go below zero
                }
                $loan->save(); // Save the updated loan record
            }
            // dd($newRecord['SSSLoan'], $newRecord['PagibigLoan'], $newRecord['SalaryLoan']);

            // }




            $GetSSS = \App\Models\sss::get();

            $GetPagibig = \App\Models\pagibig::get();

            $GetPhilHealth = \App\Models\philhealth::get();

            $GetWTAX = \App\Models\Tax::get();
            // $weekPeriod = \App\Models\WeekPeriod::where('id', $request->record['weekPeriodID'])->first();

            if ($weekPeriod) {
                // For Kinsenas (1st Kinsena or 2nd Kinsena)
                if ($weekPeriod->Category == 'Kinsenas') {
                    $deductionFactor = ($weekPeriod->Type == '1st Kinsena' || $weekPeriod->Type == '2nd Kinsena') ? 2 : 1;

                    // SSS Deduction for Kinsenas
                    foreach ($GetSSS as $sss) {
                        if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                            $SSSDeduction = $sss->EmployeeShare / $deductionFactor;
                            $newRecord['SSSDeduction'] = $SSSDeduction;
                            break;
                        }
                    }

                    // PagIbig Deduction for Kinsenas
                    foreach ($GetPagibig as $pagibig) {
                        if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                            // Set a static amount for employee and employer share
                            $PagIbigDeduction = 200 / $deductionFactor; // Divide by deduction factor for Kinsenas or Weekly
                            $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                            break;
                        }
                    }

                    // // PagIbig Deduction for Kinsenas
                    // foreach ($GetPagibig as $pagibig) {
                    //     if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                    //         $PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                    //         $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                    //         break;
                    //     }
                    // }

                    // PhilHealth Deduction for Kinsenas
                    foreach ($GetPhilHealth as $philhealth) {
                        if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
                            if ($philhealth->PremiumRate == '0.00') {
                                $PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
                            } else {
                                $PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                            }
                            $newRecord['PhilHealthDeduction'] = $PhilHealthDeduction;
                            break;
                        }
                    }

                    // WTAX Deduction for Kinsenas
                    foreach ($GetWTAX as $wTax) {
                        if ($wTax->MinSalary <= $employee->MonthlySalary && $wTax->MaxSalary >= $employee->MonthlySalary) {
                            $excess = $employee->MonthlySalary - $wTax->MinSalary;
                            $WTAXAnnual = $wTax->base_rate + ($excess * ($wTax->exceess_percent / 100));
                            $WTAXDeduction = $WTAXAnnual / $deductionFactor; // Dividing by 12 for monthly and deductionFactor for Kinsenas
                            $newRecord['WTAXDeduction'] = $WTAXDeduction;
                            break;
                        }
                    }
                } elseif ($weekPeriod->Category == 'Weekly') {
                    // For Weekly (Week 1, Week 2, Week 3, or Week 4)
                    $deductionFactor = 4; // Weekly deductions are typically divided into 4 parts

                    // // SSS Deduction for Weekly
                    // foreach ($GetSSS as $sss) {
                    //     if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                    //         $SSSDeduction = $sss->EmployeeShare / $deductionFactor;
                    //         $newRecord['SSSDeduction'] = $SSSDeduction;
                    //         break;
                    //     }
                    // }

                    // // PagIbig Deduction for Weekly
                    // foreach ($GetPagibig as $pagibig) {
                    //     if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                    //         $PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                    //         $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                    //         break;
                    //     }
                    // }

                    // PhilHealth Deduction for Weekly
                    foreach ($GetPhilHealth as $philhealth) {
                        if ($philhealth->MinSalary <= $employee->MonthlySalary && $philhealth->MaxSalary >= $employee->MonthlySalary) {
                            if ($philhealth->PremiumRate == '0.00') {
                                $PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor;
                            } else {
                                $PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                            }
                            $newRecord['PhilHealthDeduction'] = $PhilHealthDeduction;
                            break;
                        }
                    }

                    // WTAX Deduction for Weekly
                    foreach ($GetWTAX as $wTax) {
                        if ($wTax->MinSalary <= $employee->MonthlySalary && $wTax->MaxSalary >= $employee->MonthlySalary) {
                            $excess = $employee->MonthlySalary - $wTax->MinSalary;
                            $WTAXAnnual = $wTax->BaseRate + ($excess * ($wTax->ExcessPercent / 100));
                            $WTAXDeduction = $WTAXAnnual / 12 / $deductionFactor; // Dividing by 12 for monthly and deductionFactor for Weekly
                            $newRecord['WTAXDeduction'] = $WTAXDeduction;
                            break;
                        }
                    }
                }
            }
            $taxBrackets = [
                ['min' => 1, 'max' => 10417, 'compensation' => 10417, 'wth_tax' => 0, 'excess_rate' => 0],
                ['min' => 10417, 'max' => 16666, 'compensation' => 10417, 'wth_tax' => 0, 'excess_rate' => 0.15],
                ['min' => 16667, 'max' => 33332, 'compensation' => 16667, 'wth_tax' => 937.5, 'excess_rate' => 0.20],
                ['min' => 33333, 'max' => 83332, 'compensation' => 33333, 'wth_tax' => 4270.7, 'excess_rate' => 0.25],
                ['min' => 83333, 'max' => 333332, 'compensation' => 83333, 'wth_tax' => 16770.7, 'excess_rate' => 0.30],
                ['min' => 333333, 'max' => PHP_INT_MAX, 'compensation' => 333333, 'wth_tax' => 91770.7, 'excess_rate' => 0.35]
            ];

            $newRecord['WTAXDeduction'] = $newRecord['WTAXDeduction'] ?? 0;

            
            $BasicPay = $TotalHours * $employee->HourlyRate;
            $newRecord['BasicPay'] = $BasicPay;

            $TotalOvertimePay = $newRecord['TotalOvertimeHours'] * $employee->HourlyRate * 1.25;
            $newRecord['TotalOvertimePay'] = $TotalOvertimePay;

            // dd($TotalOvertimePay);
            $SundayPay = $TotalHoursSunday * $employee->HourlyRate * 1.30;
            $newRecord['SundayPay'] = $SundayPay;

            $SpecialHolidayPay = $TotalHrsSpecialHol ? $TotalHrsSpecialHol * $employee->HourlyRate * 1.30 : 0;
            $newRecord['SpecialHolidayPay'] = $SpecialHolidayPay;

            $RegularHolidayPay = $TotalHrsRegularHol ? $TotalHrsRegularHol * $employee->HourlyRate * 2: 0;
            $newRecord['RegularHolidayPay'] = $RegularHolidayPay;

            $GrossPay = $EarningPay + $BasicPay + $SundayPay + $SpecialHolidayPay + $RegularHolidayPay + $TotalOvertimePay;
            $newRecord['GrossPay'] = $GrossPay;



            $taxableIncome = $GrossPay - ($SSSDeduction + $PhilHealthDeduction + $PagIbigDeduction);
            $withholdingTax = 0;
            foreach ($taxBrackets as $bracket) {
                if ($taxableIncome >= $bracket['min'] && $taxableIncome <= $bracket['max']) {
                    $excess = $taxableIncome - $bracket['compensation'];
                    $withholdingTax = $bracket['wth_tax'] + ($excess * $bracket['excess_rate']);
                    break;
                }
            }
            $taxDue = round($withholdingTax, 2);

            // Update WTAXDeduction in payroll calculation
            $newRecord['WTAXDeduction'] = $taxDue;





            $TotalDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction + $DeductionFee + $newRecord['SSSLoan'] + $newRecord['PagibigLoan'] + $newRecord['SalaryLoan'] + $newRecord['WTAXDeduction'] + $TotalTardiness + $TotalUndertime;
            $newRecord['TotalDeductions'] = $TotalDeductions;

            $TotalGovDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction;
            $newRecord['TotalGovDeductions'] = $TotalGovDeductions;

            $TotalOfficeDeductions = $DeductionFee;
            $newRecord['TotalOfficeDeductions'] = $TotalOfficeDeductions;

            $NetPay = $GrossPay - $TotalDeductions;
            $newRecord['NetPay'] = $NetPay;


            $payrollRecords->push($newRecord);
            // dd( $payrollRecords->toArray());
            $payslipHtml .= view('payslip-template', ['payrollRecords' => $payrollRecords->toArray()])->render();
        }
        $dompdf->loadHtml($payslipHtml);
        $dompdf->setPaper('Legal', 'portrait');
        $dompdf->render();


        // Output the generated PDF to Browser
        return $dompdf->stream('payslips.pdf', ['Attachment' => false]);
    }
}
