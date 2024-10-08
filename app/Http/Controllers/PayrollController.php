<?php

namespace App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function showReport(Request $request)
    {
        $employeesWPosition = \App\Models\Employee::where('employment_type', $request->EmployeeStatus)
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate'); // Only select needed fields
        $validator = Validator::make($request->all(), [
            'EmployeeStatus' => 'required|string',
            'assignment' => 'required|string',
            'ProjectID' => 'nullable|string|integer',
             // Adjust according to your requirement
        ]);
        

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Query for employees with their position
        $employeesWPosition = \App\Models\Employee::where('employment_type', $request->EmployeeStatus)
            ->join('positions', 'employees.position_id', '=', 'positions.id')
            ->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate');

        // Check if the assignment is project-based
        if ($request->assignment === 'Project Based') {
            // If project-based, filter by project
            $employeesWPosition = $employeesWPosition->whereNotNull('project_id') // Ensure the employee has a project
                ->where('project_id', $request->ProjectID); // Filter by specific project
        } else {
            // If not project-based, filter employees without a project
            $employeesWPosition = $employeesWPosition->whereNull('project_id'); // Ensure the employee does not have a project
        }

        // Execute the query and get results
        $employeesWPosition = $employeesWPosition->get();

        // dd($employeesWPosition);
        $payrollRecords = collect();
        foreach ($employeesWPosition as $employee) {
            $newRecord = $request->all();
            // dd($employee->first_name, $employee->middle_name, $employee->last_name);
            
            $newRecord['EmployeeID'] = $employee->id;
            $newRecord['first_name'] = $employee->first_name;
            $newRecord['middle_name'] = $employee->middle_name ?? Null;
            $newRecord['last_name'] = $employee->last_name;
            $newRecord['position'] = $employee->PositionName;
            $newRecord['monthlySalary'] = $employee->MonthlySalary;
            $newRecord['hourlyRate'] = $employee->HourlyRate;
            $newRecord['SalaryType'] = 'OPEN';
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
            $weekPeriod = \App\Models\WeekPeriod::where('id', $request->weekPeriodID)->first();
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
            $PagIbigDeduction = 0;
            $PhilHealthDeduction = 0;
            $EarningPay = 0;
            $RegHolidayWorkedHours = 0; // initialize as zero
            $SpecialHolidayWorkedHours = 0;
            $TotalOvertimeHours = 0;
            $TotalOvertimePay = 0;
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
                        $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
                        $workedMorningHours = $workedMorningMinutes / 60;
                        // $workedMorningHours = $checkinOne->diffInMinutes($checkoutOne) / 60;

                        // Calculate afternoon shift times (ignoring seconds)
                        $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
                        $checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

                        // Calculate late time for the afternoon (in hours)
                        $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

                        // Calculate worked hours for afternoon shift (in hours)
                        $effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
                        $workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($afternoonEnd);
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
                        $newRecord['TotalHoursSunday'] = $TotalHoursSunday;
                    } else { // regular day monday to saturday
                        // If date is Holiday
                        //dd(count(value: $Holiday));
                        if (count(value: $Holiday) > 0) {
                            $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
                            $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
                            $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
                            $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

                            $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
                            $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

                            // $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;

                            $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
                            $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
                            $workedMorningHours = $workedMorningMinutes / 60;
                            // $workedMorningHours = $checkinOne->diffInMinutes($checkoutOne) / 60;

                            $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
                            $checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

                            // $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

                            $effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
                            $workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($afternoonEnd);
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
                            $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
                            $workedMorningHours = $workedMorningMinutes / 60;
                            // $workedMorningHours = $checkinOne->diffInMinutes($morningEnd) / 60;

                            $checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
                            $checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

                            // $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

                            $effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
                            $workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($afternoonEnd);
                            $workedAfternoonHours = $workedAfternoonMinutes / 60;

                            $totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
                            // $totalLateHours = $lateMorningHours + $lateAfternoonHours;
                            $netWorkedHours = $totalWorkedHours
                            ;
                            // $netWorkedHours = $totalWorkedHours - $totalLateHours;
                            // $SundayWorkedHours = $totalSundayWorkedHours - $totalSundayLateHours;

                            $TotalHours += $netWorkedHours;
                            $newRecord['TotalHours'] = $TotalHours;
                        }
                    }
                }

                // FOR OVERTIME WORKED HOUR
                // $checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
                // $checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));
                // $OtDate = \App\Models\Overtime::where('Date', substr($attendanceDate, 0, 10))
                // 	->where('EmployeeID', $employee->id)
                // 	->get();

                // if (count($OtDate) > 0 && $attendanceDate == $OtDate[0]->Date) {

                // 	$In1s = $OtDate[0]->Checkin;
                // 	$InOT = explode(':', $In1s);

                // 	$Out1s = $OtDate[0]->Checkout;
                // 	$OutOT = explode(':', $Out1s);

                // 	$OTStart = Carbon::createFromTime($InOT[0], $InOT[1], $InOT[2]); // 8:00 AM
                // 	$OTEnd = Carbon::createFromTime($OutOT[0], $OutOT[1], $OutOT[2]);  // 12:00 PM

                // 	$checkinOT = Carbon::createFromFormat('H:i', substr($attendances["Overtime_In"], 0, 5));
                // 	$checkoutOT = Carbon::createFromFormat('H:i', substr($attendances["Overtime_Out"], 0, 5));

                // 	// $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningEnd) / 60 : 0;

                // 	$effectiveCheckinOT = $checkinOT->greaterThan($OTStart) ? $checkinOT : $OTStart;
                // 	$workedOTMinutes = $effectiveCheckinOT->diffInMinutes($OTEnd);
                // 	$workedOTHours = $workedOTMinutes / 60;

                // 	$TotalOvertimeHours += $workedOTHours;
                // 	$newRecord['TotalOvertimeHours = $TotalOvertimeHours;

                // }



            }
            // dd($newRecord['EmployeeID);
            $OtDate = \App\Models\Overtime::where('EmployeeID', $newRecord['EmployeeID'])
                ->where('Status', 'approved') // Only consider approved overtime
                ->get();

            if (count($OtDate) > 0) {

                foreach ($OtDate as $otRecord) {
                    // Extract the check-in and check-out times from the overtime record
                    $In1s = $otRecord->Checkin;
                    $InOT = explode(':', $In1s);

                    $Out1s = $otRecord->Checkout;
                    $OutOT = explode(':', $Out1s);

                    // Create Carbon instances for the check-in and check-out times
                    $OTStart = Carbon::createFromTime($InOT[0], $InOT[1], $InOT[2]);
                    $OTEnd = Carbon::createFromTime($OutOT[0], $OutOT[1], $OutOT[2]);

                    // Calculate the overtime worked in minutes, then convert to hours
                    $workedOTMinutes = $OTStart->diffInMinutes($OTEnd);
                    $workedOTHours = $workedOTMinutes / 60;

                    // Add to the total overtime hours
                    $TotalOvertimeHours += $workedOTHours;
                }

                // Store the total overtime hours in the new record
                $newRecord['TotalOvertimeHours'] = $TotalOvertimeHours;
            }


            // For Earnings
            $GetEarnings = \App\Models\Earnings::where('PeriodID', $request->weekPeriodID)
                ->where('EmployeeID', $employee->id)
                ->get();
            $Earnings = $GetEarnings;

            if (count($Earnings) > 0) {
                $EarningPay = $Earnings[0]->Amount;
                $newRecord['EarningPay'] = $EarningPay;
                // $TotalEarningPay = $EarningPay;
            }


            // For Deductions
            $GetDeductions = \App\Models\Deduction::where('PeriodID', $request->weekPeriodID)
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
                ->where('PeriodID', $request->weekPeriodID)
                ->first();
            if ($loan) {
                // Check if payroll is already generated for this period and employee
                $existingPayroll = \App\Models\Payroll::where('weekPeriodID', $request->weekPeriodID)
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




            $GetSSS = \App\Models\sss::get();

            $GetPagibig = \App\Models\pagibig::get();

            $GetPhilHealth = \App\Models\philhealth::get();

            // $weekPeriod = \App\Models\WeekPeriod::where('id', $request->weekPeriodID)->first();

            if ($weekPeriod) {
                // For Kinsenas (1st Kinsena or 2nd Kinsena)
                if ($weekPeriod->Category == 'Kinsenas') {
                    $deductionFactor = $weekPeriod->Type == '1st Kinsena' || $weekPeriod->Type == '2nd Kinsena' ? 2 : 1;

                    // SSS Deduction for Kinsenas (1st or 2nd half of the month)
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
                            $PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                            $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                            break;
                        }
                    }

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

                } elseif ($weekPeriod->Category == 'Weekly') {
                    // For Weekly (Week 1, Week 2, Week 3, or Week 4)
                    $deductionFactor = 4; // Weekly deductions are typically divided into 4 parts

                    // SSS Deduction for Weekly
                    foreach ($GetSSS as $sss) {
                        if ($sss->MinSalary <= $employee->MonthlySalary && $sss->MaxSalary >= $employee->MonthlySalary) {
                            $SSSDeduction = $sss->EmployeeShare / $deductionFactor;
                            $newRecord['SSSDeduction'] = $SSSDeduction;
                            break;
                        }
                    }

                    // PagIbig Deduction for Weekly
                    foreach ($GetPagibig as $pagibig) {
                        if ($pagibig->MinimumSalary <= $employee->MonthlySalary && $pagibig->MaximumSalary >= $employee->MonthlySalary) {
                            $PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->MonthlySalary) / $deductionFactor;
                            $newRecord['PagIbigDeduction'] = $PagIbigDeduction;
                            break;
                        }
                    }

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
                }
            }

            $BasicPay = $TotalHours * $employee->HourlyRate;
            $newRecord['BasicPay'] = $BasicPay;

            $TotalOvertimePay = $TotalOvertimeHours * $employee->HourlyRate * 1.25;
            $newRecord['TotalOvertimePay'] = $TotalOvertimePay;

            $SundayPay = $TotalHoursSunday * $employee->HourlyRate * 1.30;
            $newRecord['SundayPay'] = $SundayPay;

            $SpecialHolidayPay = $TotalHrsSpecialHol ? $TotalHrsSpecialHol * $employee->HourlyRate * 1.30 : 0;
            $newRecord['SpecialHolidayPay'] = $SpecialHolidayPay;

            $RegularHolidayPay = $TotalHrsRegularHol ? $TotalHrsRegularHol * $employee->HourlyRate : 0;
            $newRecord['RegularHolidayPay'] = $RegularHolidayPay;

            $GrossPay = $EarningPay + $BasicPay + $SundayPay + $SpecialHolidayPay + $RegularHolidayPay + $TotalOvertimePay;
            $newRecord['GrossPay'] = $GrossPay;
            $TotalDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction + $DeductionFee;
            $newRecord['TotalDeductions'] = $TotalDeductions;

            $TotalGovDeductions = $PagIbigDeduction + $SSSDeduction + $PhilHealthDeduction;
            $newRecord['TotalGovDeductions'] = $TotalGovDeductions;

            $TotalOfficeDeductions = $DeductionFee;
            $newRecord['TotalOfficeDeductions'] = $TotalOfficeDeductions;

            $NetPay = $GrossPay - $TotalDeductions;
            $newRecord['NetPay'] = $NetPay;
            // dd(
            // 	$TotalHours,
            // 	'TotalHours',
            // 	$employee->HourlyRate,
            // 	'empRate',
            // 	$BasicPay,
            // 	'bscPay',
            // 	$GrossPay,
            // 	'grsPay',
            // 	$TotalHoursSunday,
            // 	'hrsSun',
            // 	$TotalHrsRegularHol,
            // 	'regHrdHol',
            // 	$TotalHrsSpecialHol,
            // 	'spcHrdHol',
            // 	$SpecialHolidayWorkedHours,
            // 	'spclpay',
            // 	$RegularHolidayPay,
            // 	'regpay',
            // 	$EarningPay,
            // 	'Earningpay',
            // 	$request->$TotalDeductions,
            // 	'ttlDeduct',
            // 	$TotalOvertimeHours,
            // 	'totalOvertimeHrs',
            // 	$NetPay,
            // 	'netPay'
            // );

            // dd($GrossPay, $NetPay);
            // ================
            // 
            // Add the new record to the payrollRecords collection

            $payrollRecords->push($newRecord);
            // $payrollRecords->push($newRecord['toArray()']);
            // $request->NetPay = self::calculateNetPay($request);
            // $request->save();
            // return Excel::download(new PayrollExport($payrollRecords), 'payroll_' . $request->id . '.xlsx');

        }
        // dd($payrollRecords);
        // return Excel::download(new PayrollExport($payrollRecords), 'payroll_' . $request->EmployeeID . '.xlsx');
        // } 
        // else {
        // 	// dd($request->toArray());

        // 	$employees = \App\Models\Employee::where('employment_type', $request->EmployeeStatus)
        // 		->where('project_id', $request->ProjectID)
        // 		->join('positions', 'employees.position_id', '=', 'position_id')
        // 		->get();

        // 	// return Excel::download(new PayrollExport($request), 'payroll_' . $request->id . '.xlsx');
        // }


        // Fetch data from the database here, for now we use sample data
        $payrollData = [
            [
                'employee_id' => '001',
                'name' => 'John Doe',
                'designation' => 'LTO Project Regular',
                'area' => 'Area 1',
                'days_worked' => 22,
                'gross_pay' => 50000,
                'basic_pay' => 50000,
                'sss' => 2500,
                'philhealth' => 1000,
                'pagibig' => 500,
                'loans' => 0,
                'total_gov_deductions' => 4000,
                'total_other_deductions' => 0,
                'net_pay' => 46000,
            ],
            // More employee data...
        ];
        return view('payroll.payroll-report', ['payrollData' => $payrollRecords]);
    }
}