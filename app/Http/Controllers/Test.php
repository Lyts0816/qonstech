<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\{Employee, Attendance, Holiday, WorkSched, Overtime, Earnings, Deduction, Sss, Pagibig, PhilHealth, WeekPeriod, Payslip};

class Test extends Controller
{
    public function show(Request $request, $EmployeeID)
    {
        // Find the specific employee based on EmployeeID in the record


        $selectedEmployee = Employee::with('position')->findOrFail($EmployeeID);

        $records = Payslip::where('EmployeeID', $EmployeeID)->get();

        if ($records->isEmpty()) {
            return redirect()->back()->withErrors(['No payslips found for this employee.']);
        }

        if (!$selectedEmployee) {
            return redirect()->back()->withErrors(['Employee not found']);
        }

        $newRecord = new \stdClass();
        $newRecord->EmployeeID = $selectedEmployee->id;
        $newRecord->first_name = $selectedEmployee->first_name;
        $newRecord->middle_name = $selectedEmployee->middle_name ?? null;
        $newRecord->last_name = $selectedEmployee->last_name;
        $newRecord->position = $selectedEmployee->position->PositionName; // Accessing the position relation
        $newRecord->monthlySalary = $selectedEmployee->position->MonthlySalary;
        $newRecord->hourlyRate = $selectedEmployee->position->HourlyRate;
        $newRecord->SalaryType = 'OPEN';
        $newRecord->RegularStatus = $selectedEmployee->employment_type == 'Regular' ? 'YES' : 'NO';

        // Check payroll frequency
        $weekPeriod = WeekPeriod::find($request->weekPeriodID);
        $attendance = $this->getAttendance($weekPeriod, $selectedEmployee, $request, $records);

        $this->calculateAttendanceDetails($attendance, $newRecord, $selectedEmployee);

        // Get Overtime Records
        $this->calculateOvertime($newRecord);

        // Calculate Earnings and Deductions
        $this->calculateEarningsAndDeductions($newRecord, $request, $selectedEmployee, $records);
        // Return the view with the selected employee's payroll record
        return view('payslip.records', [
            'payrollRecords' => collect([$newRecord]), // Return a collection with the single employee record
            'earnings' => $newRecord->EarningPay,
            'TotalOvertimePay' => $newRecord->TotalOvertimeEarnings = $newRecord->TotalOvertimeHours * ($newRecord->hourlyRate * 1.25),
            'sss' => $newRecord->SSSDeduction,
            'philHealth' => $newRecord->PhilHealthDeduction,
            'pagIbig' => $newRecord->PagIbigDeduction,
            'deductions' => $newRecord->DeductionFee,
            'totalDeductions' => $newRecord->SSSDeduction + $newRecord->PagIbigDeduction + $newRecord->PhilHealthDeduction + $newRecord->DeductionFee,
            'totalGrossPay' => $newRecord->EarningPay + ( $newRecord->TotalHours * $newRecord->hourlyRate) + ($newRecord->TotalOvertimeEarnings = $newRecord->TotalOvertimeHours * ($newRecord->hourlyRate * 1.25)),
            'BasicPay' =>  $newRecord->TotalHours * $newRecord->hourlyRate,
            'netPay' => $newRecord->EarningPay + ( $newRecord->TotalHours * $newRecord->hourlyRate) + ($newRecord->TotalOvertimeEarnings = $newRecord->TotalOvertimeHours * ($newRecord->hourlyRate * 1.25)) - ($newRecord->SSSDeduction + $newRecord->PagIbigDeduction + $newRecord->PhilHealthDeduction + $newRecord->DeductionFee),
        ]);
    }

    // Process and calculate attendance hours
    protected function calculateAttendanceDetails($attendance, &$newRecord, $selectedEmployee)
    {
        // Initialize total hours
        $TotalHours = 0;
        $TotalHoursSunday = 0;
        $TotalHrsRegularHol = 0;
        $TotalHrsSpecialHol = 0;

        // Loop through each attendance record
        foreach ($attendance as $att) {
            // Parse attendance date
            $attendanceDate = Carbon::parse($att['date']);

            // Get holidays for the given date
            $holidays = Holiday::where('HolidayDate', $attendanceDate->toDateString())->get();

            // Get the work schedule assigned to the employee
            $workSched = WorkSched::find($selectedEmployee->schedule_id);

            // Skip if there's no work schedule for the employee
            if (!$workSched) {
                continue;
            }

            // Check if the attendance date matches the work schedule
            if ($this->isScheduledDay($workSched, $attendanceDate)) {
                // Split the schedule for morning and afternoon shifts
                $In1Array = explode(':', $workSched->CheckinOne);
                $Out1Array = explode(':', $workSched->CheckoutOne);
                $In2Array = explode(':', $workSched->CheckinTwo);
                $Out2Array = explode(':', $workSched->CheckoutTwo);

                // If Sunday, calculate Sunday hours
                if ($attendanceDate->isSunday()) {
                    $this->calculateSundayHours($att, $In1Array, $Out1Array, $In2Array, $Out2Array, $TotalHoursSunday, $newRecord);
                } else {
                    // Initialize hours worked
                    $regularHolidayHours = 0;
                    $specialHolidayHours = 0;
                    $hoursWorked = 0;

                    // Extract check-in and check-out times from the attendance data
                    $checkInTime = Carbon::parse($att['CheckIn']);
                    $checkOutTime = Carbon::parse($att['CheckOut']);

                    // Calculate holiday hours
                    foreach ($holidays as $holiday) {
                        if ($holiday->HolidayType == 'Regular') {
                            $regularHolidayHours += $this->calculateHolidayHours($checkInTime, $checkOutTime, $In1Array, $Out1Array, $In2Array, $Out2Array, 2);
                            $TotalHrsRegularHol += $regularHolidayHours;
                        } elseif ($holiday->HolidayType == 'Special') {
                            $specialHolidayHours += $this->calculateHolidayHours($checkInTime, $checkOutTime, $In1Array, $Out1Array, $In2Array, $Out2Array, 1.5);
                            $TotalHrsSpecialHol += $specialHolidayHours;
                        }
                    }

                    // If not a holiday, calculate regular hours
                    if ($regularHolidayHours == 0 && $specialHolidayHours == 0) {
                        $hoursWorked += $this->calculateRegularHours($att,$checkInTime, $checkOutTime, $In1Array, $Out1Array, $In2Array, $Out2Array);
                    }

                    // Update newRecord with calculated hours
                    $newRecord->RegularHours = $hoursWorked;
                    $TotalHours += $hoursWorked; // Update total hours
                }
            }
        }

        // Set total hours in the newRecord
        $newRecord->TotalHours = $TotalHours;
        $newRecord->TotalHoursSunday = $TotalHoursSunday;
        $newRecord->TotalHrsRegularHol = $TotalHrsRegularHol;
        $newRecord->TotalHrsSpecialHol = $TotalHrsSpecialHol;
    }

    private function isScheduledDay($workSched, Carbon $attendanceDate)
    {
        // Check if the day of the week matches the work schedule
        return ($workSched->monday == $attendanceDate->isMonday() ||
            $workSched->tuesday == $attendanceDate->isTuesday() ||
            $workSched->wednesday == $attendanceDate->isWednesday() ||
            $workSched->thursday == $attendanceDate->isThursday() ||
            $workSched->friday == $attendanceDate->isFriday() ||
            $workSched->saturday == $attendanceDate->isSaturday() ||
            $workSched->sunday == $attendanceDate->isSunday());
    }

    private function calculateHolidayHours($checkInTime, $checkOutTime, $In1Array, $Out1Array, $In2Array, $Out2Array, $multiplier)
    {
        $hours = 0;

        // Logic to calculate hours based on check-in and check-out times
        $scheduledIn1 = Carbon::createFromTime($In1Array[0], $In1Array[1]);
        $scheduledOut1 = Carbon::createFromTime($Out1Array[0], $Out1Array[1]);

        if ($checkInTime->between($scheduledIn1, $scheduledOut1)) {
            if ($checkOutTime->lt($scheduledOut1)) {
                $hours += $checkOutTime->diffInHours($checkInTime);
            } else {
                $hours += $scheduledOut1->diffInHours($checkInTime);
            }
        }

        if (!empty($In2Array)) {
            $scheduledIn2 = Carbon::createFromTime($In2Array[0], $In2Array[1]);
            $scheduledOut2 = Carbon::createFromTime($Out2Array[0], $Out2Array[1]);

            if ($checkInTime->greaterThan($scheduledOut1) && $checkInTime->between($scheduledIn2, $scheduledOut2)) {
                if ($checkOutTime->lt($scheduledOut2)) {
                    $hours += $checkOutTime->diffInHours($checkInTime);
                } else {
                    $hours += $scheduledOut2->diffInHours($checkInTime);
                }
            }
        }

        return $hours * $multiplier; // Return adjusted holiday hours
    }

    private function calculateRegularHours($att,$checkInTime, $checkOutTime, $In1Array, $Out1Array, $In2Array, $Out2Array)
    {
        $TotalHours = 0; 

        $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
        $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
        $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
        $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

        $checkinOne = Carbon::createFromFormat('H:i', substr($att["Checkin_One"], 0, 5));
        $checkoutOne = Carbon::createFromFormat('H:i', substr($att["Checkout_One"], 0, 5));

        // $lateMorningHours = $checkinOne->greaterThan($morningStart) ? $checkinOne->diffInMinutes($morningStart) / 60 : 0;

        $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
        $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
        $workedMorningHours = $workedMorningMinutes / 60;
        // $workedMorningHours = $checkinOne->diffInMinutes($morningEnd) / 60;

        $checkinTwo = Carbon::createFromFormat('H:i', substr($att["Checkin_Two"], 0, 5));
        $checkoutTwo = Carbon::createFromFormat('H:i', substr($att["Checkout_Two"], 0, 5));

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
        return $TotalHours;

    }

    // Calculate overtime hours
    private function calculateOvertime($newRecord)
    {
        $overtimeRecords = Overtime::where('EmployeeID', $newRecord->EmployeeID)
            ->where('Status', 'approved')
            ->get();

        $TotalOvertimeHours = 0;

        foreach ($overtimeRecords as $otRecord) {
            $workedOTHours = Carbon::parse($otRecord->Checkin)->diffInHours(Carbon::parse($otRecord->Checkout));
            $TotalOvertimeHours += $workedOTHours;
        }
        $newRecord->TotalOvertimeHours = $TotalOvertimeHours;
    }


    private function getEmployeeById($EmployeeID)
    {
        return \App\Models\Employee::find($EmployeeID);
    }

    private function getAttendance($weekPeriod, $employee, $request, $records)
    {
        $startDate = null;
        $endDate = null;

        // Get the first record from the $records collection
        $firstRecord = $records->first();

        // Check if the first record exists
        if (!$firstRecord) {
            // Handle case where there are no records
            return null; // or return an empty collection if that's the desired behavior
        }

        // Retrieve the weekPeriod based on the weekPeriodID from the first record
        $weekPeriod = WeekPeriod::where('id', $firstRecord->weekPeriodID)->first();

        if ($weekPeriod) {
            if ($weekPeriod->Category == 'Kinsenas') {
                $startDate = $weekPeriod->StartDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 1);
                $endDate = $weekPeriod->EndDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 15);
            } elseif ($weekPeriod->Category == 'Weekly') {
                $startDate = $weekPeriod->StartDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 1);
                $endDate = $weekPeriod->EndDate ?? Carbon::create($firstRecord->PayrollYear, Carbon::parse($firstRecord->PayrollMonth)->month, 7);
            }
        }

        return Attendance::where('Employee_ID', $employee->id)
            ->whereBetween('Date', [$startDate, $endDate])
            ->get();
    }



    protected function calculateSundayHours($att, $In1Array, $Out1Array, $In2Array, $Out2Array, &$TotalHoursSunday, &$newRecord)
    {
        $morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
        $morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
        $afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
        $afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);

        $checkinOne = Carbon::createFromFormat('H:i', substr($att["Checkin_One"], 0, 5));
        $effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
        $workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($morningEnd);
        $workedMorningHours = $workedMorningMinutes / 60;

        $checkinTwo = Carbon::createFromFormat('H:i', substr($att["Checkin_Two"], 0, 5));
        $effectiveCheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
        $workedAfternoonMinutes = $effectiveCheckinTwo->diffInMinutes($afternoonEnd);
        $workedAfternoonHours = $workedAfternoonMinutes / 60;

        $totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
        $TotalHoursSunday += $totalWorkedHours;
        $newRecord->TotalHoursSunday = $TotalHoursSunday;

    }





    private function calculateEarningsAndDeductions($newRecord, $record, $employee, $records)
    {
        // Initialize values
        // $newRecord->EarningPay = 0;
        $firstRecord = $records->first();
        if (!$firstRecord) {
            // Handle case where there are no records
            return;
        }


        // Calculate Earnings
        $earnings = Earnings::where('PeriodID', $firstRecord->weekPeriodID)
            ->where('EmployeeID', $employee->id)
            ->first();

        // Set EarningPay if earnings found
        $newRecord->EarningPay = $earnings ? $earnings->Amount : 0;

        // Calculate Deductions
        $deductions = Deduction::where('PeriodID', $firstRecord->weekPeriodID)
            ->where('EmployeeID', $employee->id)
            ->first();

        // Set DeductionFee if deductions found
        $newRecord->DeductionFee = $deductions ? $deductions->Amount : 0;

        // Fetch SSS, PagIbig, PhilHealth settings
        $GetSSS = \App\Models\SSS::all();
        $GetPagibig = \App\Models\PagIbig::all();
        $GetPhilHealth = \App\Models\PhilHealth::all();
        $weekPeriod = \App\Models\WeekPeriod::where('id', $firstRecord->weekPeriodID)->first(); // Adjusted to get specific weekPeriod

        $newRecord->SSSDeduction = 0;
        $newRecord->PhilHealthDeduction = 0;
        $newRecord->PagIbigDeduction = 0;
        $newRecord->DeductionFee = 0;

        // Calculate deductions based on week period category
        if ($weekPeriod) {

            // Determine the deduction factor based on the week period type
            $deductionFactor = ($weekPeriod->Category == 'Kinsenas') ?
                (($weekPeriod->Type == '1st Kinsena' || $weekPeriod->Type == '2nd Kinsena') ? 2 : 1) :
                4; // Default for Weekly

            // Function to calculate SSS, PagIbig, and PhilHealth deductions

            // SSS Deduction calculation
            foreach ($GetSSS as $sss) {
                if ($sss->MinSalary <= $employee->position->MonthlySalary && $sss->MaxSalary >= $employee->position->MonthlySalary) {
                    $newRecord->SSSDeduction = $sss->EmployeeShare / $deductionFactor; // Adjusted SSS Deduction based on factor
                    break; // Exit loop after finding the correct SSS
                }
            }

            // PagIbig Deduction calculation
            foreach ($GetPagibig as $pagibig) {
                if ($pagibig->MinimumSalary <= $employee->position->MonthlySalary && $pagibig->MaximumSalary >= $employee->position->MonthlySalary) {
                    $newRecord->PagIbigDeduction = (($pagibig->EmployeeRate / 100) * $employee->position->MonthlySalary) / $deductionFactor; // Adjusted PagIbig Deduction
                    break; // Exit loop after finding the correct PagIbig
                }
            }

            // PhilHealth Deduction calculation
            foreach ($GetPhilHealth as $philhealth) {
                if ($philhealth->MinSalary <= $employee->position->MonthlySalary && $philhealth->MaxSalary >= $employee->position->MonthlySalary) {
                    if ($philhealth->PremiumRate == '0.00') {
                        $newRecord->PhilHealthDeduction = $philhealth->ContributionAmount / $deductionFactor; // Fixed contribution amount
                    } else {
                        $newRecord->PhilHealthDeduction = (($philhealth->PremiumRate / 100) * $employee->position->MonthlySalary) / $deductionFactor; // Adjusted PhilHealth Deduction
                    }
                    break; // Exit loop after finding the correct PhilHealth
                }
            }

            // Calculate total deductions and store in DeductionFee
            $newRecord->DeductionFee = $newRecord->SSSDeduction + $newRecord->PagIbigDeduction + $newRecord->PhilHealthDeduction;
        }
    }

    // New private function to handle deduction calculations
    private function calculateDeductions($newRecord, $employee, $deductionFactor, $GetSSS, $GetPagibig, $GetPhilHealth)
    {
        // Initialize deductions to zero

    }


}