<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use Dompdf\Dompdf;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
	public function showDtr(Request $request)
	{
		try {

			$employeeId = $request->query('employee_id');
			$projectId = $request->query('project_id');
			$startDate = $request->query('startDate');
			$endDate = $request->query('endDate');
			$dompdf = new Dompdf();
			$payslipHtml = '';
			$employee = Employee::findOrFail($employeeId);
			if ($projectId) {
				$data = Attendance::where('employee_id', $employeeId)
					->where('ProjectID', $projectId)
					->whereBetween('Date', [$startDate, $endDate])
					->orderBy('Date', 'asc')
					->get();
			} else {
				$data = Attendance::where('employee_id', $employeeId)
					->where(function ($query) {
						$query->where('ProjectID', 0)
							->orWhereNull('ProjectID');
					})
					->whereBetween('Date', [$startDate, $endDate])
					->orderBy('Date', 'asc')

					->get();
			}
			if (count($data) > 0) {
				$TotalHours = 0;
				$tardinessMorningMinutes = 0;
				$underTimeMorningMinutes = 0;
				$tardinessAfternoonMinutes = 0;
				$underTimeAfternoonMinutes = 0;
				$workedMorningHours = 0;
				$workedAfternoonHours = 0;
				$totalOvertimeHours = 0;

				foreach ($data as $attendances) {
					$attendanceDate = Carbon::parse($attendances['Date']);
					$GetWorkSched = \App\Models\WorkSched::where('id', $employee->schedule_id)->get();
					$WorkSched = $GetWorkSched;
					$In1 = $WorkSched[0]->CheckinOne;
					$In1Array = explode(':', $In1);
					$Out1 = $WorkSched[0]->CheckoutOne;
					$Out1Array = explode(':', $Out1);
					$In2 = $WorkSched[0]->CheckinTwo;
					$In2Array = explode(':', $In2);
					$Out2 = $WorkSched[0]->CheckoutTwo;
					$Out2Array = explode(':', $Out2);
					$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
					$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
					$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
					$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);

					if ($attendances["Checkin_One"] && $attendances["Checkout_One"]) {
						$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
						$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));

						$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
						$effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
						$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
						$underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
						$tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
						$workedMorningHours = $workedMorningMinutes / 60;
					}
					if ($attendances["Checkin_Two"] && $attendances["Checkout_Two"]) {
						$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
						$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

						$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
						$effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
						$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
						$underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
						$tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
						$workedAfternoonHours = $workedAfternoonMinutes / 60;
					}

					$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
					$netWorkedHours = $totalWorkedHours;

					$TotalHours += $netWorkedHours;
					$attendances['TotalHours'] = number_format($TotalHours, 2);
					$attendances['MorningTardy'] = $tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0;
					$attendances['MorningUndertime'] = $underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0;
					$attendances['AfternoonTardy'] = $tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0;
					$attendances['AfternoonUndertime'] = $underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0;


					$approvedOvertimeRecords = \App\Models\Overtime::where('EmployeeID', $employee->id)
						->get();


					$dayOfWeek = strtolower(Carbon::parse($attendances->Date)->format('l'));


					if (!empty($WorkSched[0]) && $WorkSched[0]->$dayOfWeek) {
						$workStart = Carbon::parse($WorkSched[0]->CheckinOne);
						$workEnd = Carbon::parse($WorkSched[0]->CheckoutTwo);
						$attendanceCheckin = Carbon::parse($attendances->Checkin_One);
						$attendanceCheckout = Carbon::parse($attendances->Checkout_Two);
						$overtimeRecord = $approvedOvertimeRecords->firstWhere('Date', $attendances->Date);

						if ($overtimeRecord) {

							$overtimeHoursForDay = 0;
							$totalOvertimeHours = 2.5;
							if ($attendanceCheckin->lt($workStart)) {
								$overtimeMinutesBefore = $attendanceCheckin->diffInMinutes($workStart);
								$overtimeHoursForDay += $overtimeMinutesBefore / 60;
							}
							if ($attendanceCheckout->gt($workEnd)) {
								$overtimeMinutesAfter = $workEnd->diffInMinutes($attendanceCheckout);
								$overtimeHoursForDay += $overtimeMinutesAfter / 60;
							}
							if ($overtimeHoursForDay > 0) {
								$totalOvertimeHours = 2.5;
							}
						}
					}
					if ($approvedOvertimeRecords->contains('Date', $attendances->Date) && !$WorkSched[0]->$dayOfWeek) {
						$overtimeRecord = $approvedOvertimeRecords->firstWhere('Date', $attendances->Date);
						if ($overtimeRecord) {
							$totalOvertimeHours = 2.5;
							$attendanceCheckin = Carbon::parse($attendances->Checkin_One);
							$attendanceCheckout = Carbon::parse($attendances->Checkout_Two);
							$overtimeHoursForDay = $attendanceCheckin->diffInMinutes($attendanceCheckout) / 60;
							if ($overtimeHoursForDay > 8) {
								$overtimeHoursForDay -= 1;
							}
							if ($overtimeHoursForDay > 0) {
								$totalOvertimeHours = 2.5;
							}
						}
					}
					$attendances['TotalOvertimeHours'] = $totalOvertimeHours;
				}
				$payslipHtml .= view('dtr.show', ['employee' => $employee, 'data' => $data->toArray()])->render();
			}
			$dompdf->loadHtml($payslipHtml);
			$dompdf->setPaper('Legal', 'landscape');
			$dompdf->render();
			return $dompdf->stream('Dtr.pdf', ['Attachment' => false]);
		} catch (\Exception $e) {
			return response()->json(['error' => 'An error occurred while generating the DTR. Please try again later.'], 500);
		}
	}

	public function showSummary(Request $request)
	{
		$dompdf = new Dompdf();
		$payslipHtml = '';
		$payroll = \App\Models\Payroll::where('id', $request->payroll_id)->first();
		$weekPeriod = \App\Models\WeekPeriod::where('id', $payroll->weekPeriodID)->first();
		if ($weekPeriod) {
			if ($weekPeriod->Category == 'Kinsenas') {
				if (in_array($weekPeriod->Type, ['1st Kinsena', '2nd Kinsena'])) {
					$startDate = $weekPeriod->StartDate;
					$endDate = $weekPeriod->EndDate;
				} else {
					$startDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 1);
					$endDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 15);
				}
			} elseif ($weekPeriod->Category == 'Weekly') {
				if (in_array($weekPeriod->Type, ['Week 1', 'Week 2', 'Week 3', 'Week 4'])) {
					$startDate = $weekPeriod->StartDate;
					$endDate = $weekPeriod->EndDate;
				} else {
					$startDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 1);
					$endDate = Carbon::create($request->PayrollYear, Carbon::parse($request->PayrollMonth)->month, 7);
				}
			}
		}

		$start = Carbon::parse($startDate);
		$end = Carbon::parse($endDate);
		$dates = [];
		while ($start->lte($end)) {
			$dates[] = $start->toDateString();
			$start->addDay();
		}
		$payrollRecords = collect();
		foreach ($dates as $date) {

			$attendance = \App\Models\Attendance::where('ProjectID', $payroll->ProjectID)
				->where('Date', $date)
				->orderBy('Date', 'ASC')
				->get();
			foreach ($attendance as $attendances) {

				$employeesWPosition = \App\Models\Employee::where('project_id', $payroll->ProjectID)
					->where('employees.id', $attendances['Employee_ID'])
					->whereNotNull('employees.schedule_id')
					->join('positions', 'employees.position_id', '=', 'positions.id')
					->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate');
				$validator = Validator::make($request->all(), [
					'project_id' => 'nullable|string|integer',
				]);
				if ($validator->fails()) {
					return response()->json($validator->errors(), 422);
				}
				$employeesWPosition = $employeesWPosition->get();
				foreach ($employeesWPosition as $employee) {
					$newRecord = $request->all();
					$newRecord['EmployeeID'] = $employee->id;
					$newRecord['first_name'] = $employee->first_name;
					$newRecord['middle_name'] = $employee->middle_name ?? Null;
					$newRecord['last_name'] = $employee->last_name;
					$newRecord['position'] = $employee->PositionName;
					$newRecord['monthlySalary'] = $employee->MonthlySalary;
					$newRecord['hourlyRate'] = $employee->HourlyRate;
					$newRecord['SalaryType'] = 'OPEN';
					$newRecord['RegularStatus'] = $employee->employment_type == 'Regular' ? 'YES' : 'NO';
					$newRecord['Period'] = $weekPeriod->StartDate . ' - ' . $weekPeriod->EndDate;
					if ($employee->project_id) {
						$project = \App\Models\Project::find($employee->project_id);
						if ($project) {
							$newRecord['ProjectName'] = $project->ProjectName;
						} else {
							$newRecord['ProjectName'] = 'Main Office';
						}
					} else {
						$newRecord['ProjectName'] = 'Main Office';
					}
					$TotalHours = 0;
					$tardinessMorningMinutes = 0;
					$underTimeMorningMinutes = 0;
					$tardinessAfternoonMinutes = 0;
					$underTimeAfternoonMinutes = 0;
					$TotalHoursSunday = 0;
					$TotalHrsSpecialHol = 0;
					$TotalHrsRegularHol = 0;
					$RegHolidayWorkedHours = 0;
					$SpecialHolidayWorkedHours = 0;
					$TotalTardiness = 0;
					$TotalUndertime = 0;
					$totalHoursWeek = [
						'Sunday' => 0,
						'Monday' => 0,
						'Tuesday' => 0,
						'Wednesday' => 0,
						'Thursday' => 0,
						'Friday' => 0,
						'Saturday' => 0,
					];
					$attendanceDate = Carbon::parse($attendances['Date']);
					$newRecord['DateNow'] = substr($attendanceDate, 0, 10);
					$newRecord['MorningCheckIn'] = $attendances["Checkin_One"];
					$newRecord['MorningCheckOut'] = $attendances["Checkout_One"];
					$newRecord['AfternoonCheckIn'] = $attendances["Checkin_Two"];
					$newRecord['AfternoonCheckOut'] = $attendances["Checkout_Two"];

					$GetHoliday = \App\Models\Holiday::where('HolidayDate', substr($attendanceDate, 0, 10))->get();
					$Holiday = $GetHoliday;
					$GetWorkSched = \App\Models\WorkSched::where('id', $employee['schedule_id'])->get();
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
						$dayOfWeek = $attendanceDate->format('l');
						if ($attendanceDate->isSunday()) {
							$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
							$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
							$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
							$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);

							if ($attendances["Checkin_One"] && $attendances["Checkout_One"]) {

								$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
								$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));
								$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
								$effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
								$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
								$underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
								$tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
								$workedMorningHours = $workedMorningMinutes / 60;
							}
							if ($attendances["Checkin_Two"] && $attendances["Checkout_Two"]) {

								$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
								$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));
								$lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;
								$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
								$effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
								$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
								$underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
								$tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
								$workedAfternoonHours = $workedAfternoonMinutes / 60;
							}
							$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
							$SundayWorkedHours = $totalWorkedHours;
							$TotalHoursSunday += $SundayWorkedHours;
							$TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
								+ ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

							$TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
								+ ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

							$newRecord['TotalTardiness'] = $TotalTardiness;
							$newRecord['TotalUndertime'] = $TotalUndertime;
							$newRecord['TotalHoursSunday'] = $TotalHoursSunday;
						} else {
							if (count(value: $Holiday) > 0 && $Holiday[0]->ProjectID == $employee->project_id) {
								$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
								$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
								$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
								$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);
								if ($attendances["Checkin_One"] && $attendances["Checkout_One"]) {
									$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
									$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));
									$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
									$effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
									$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
									$underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
									$tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
									$workedMorningHours = $workedMorningMinutes / 60;
								}
								if ($attendances["Checkin_Two"] && $attendances["Checkout_Two"]) {
									$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
									$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));
									$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
									$effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
									$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
									$underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
									$tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
									$workedAfternoonHours = $workedAfternoonMinutes / 60;
								}
								$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
								if ($Holiday[0]->HolidayType == 'Regular') {
									$RegHolidayWorkedHours = $totalWorkedHours;
									$TotalHrsRegularHol += $RegHolidayWorkedHours;
									$newRecord['TotalHrsRegularHol'] = $TotalHrsRegularHol;
								} else if ($Holiday[0]->HolidayType == 'Special') {
									$SpecialHolidayWorkedHours = $totalWorkedHours;
									$TotalHrsSpecialHol += $SpecialHolidayWorkedHours;
									$newRecord['TotalHrsSpecialHol'] = $TotalHrsSpecialHol;
								}
								$TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
									+ ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

								$TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
									+ ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

								$newRecord['TotalTardiness'] = $TotalTardiness;
								$newRecord['TotalUndertime'] = $TotalUndertime;
							} else {
								$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]);
								$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);
								$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]);
								$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);
								if ($attendances["Checkin_One"] && $attendances["Checkout_One"]) {
									$checkinOne = Carbon::createFromFormat('H:i', substr($attendances["Checkin_One"], 0, 5));
									$checkoutOne = Carbon::createFromFormat('H:i', substr($attendances["Checkout_One"], 0, 5));
									$effectiveCheckinOne = $checkinOne->greaterThan($morningStart) ? $checkinOne : $morningStart;
									$effectiveCheckOutOne = $checkoutOne->lessThan($morningEnd) ? $checkoutOne : $morningEnd;
									$workedMorningMinutes = $effectiveCheckinOne->diffInMinutes($checkoutOne);
									$underTimeMorningMinutes = $effectiveCheckOutOne->diffInMinutes($morningEnd);
									$tardinessMorningMinutes = $morningStart->diffInMinutes($checkinOne);
									$workedMorningHours = $workedMorningMinutes / 60;
								}
								if ($attendances["Checkin_Two"] && $attendances["Checkout_Two"]) {
									$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
									$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));
									$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
									$effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
									$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
									$underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
									$tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
									$workedAfternoonHours = $workedAfternoonMinutes / 60;
								}
								$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
								$netWorkedHours = $totalWorkedHours;
								$TotalHours += $netWorkedHours;
								$totalHoursWeek[$dayOfWeek] += $netWorkedHours;
								$TotalTardiness += ($tardinessMorningMinutes > 0 ? $tardinessMorningMinutes : 0)
									+ ($tardinessAfternoonMinutes > 0 ? $tardinessAfternoonMinutes : 0);

								$TotalUndertime += ($underTimeMorningMinutes > 0 ? $underTimeMorningMinutes : 0)
									+ ($underTimeAfternoonMinutes > 0 ? $underTimeAfternoonMinutes : 0);

								$newRecord['TotalTardiness'] = $TotalTardiness;
								$newRecord['TotalUndertime'] = $TotalUndertime;
								$newRecord['TotalHours'] = $TotalHours;
							}
						}
					}
					$totalHoursWeeks = [
						'Sunday' => $TotalHoursSunday,
						'Monday' => $totalHoursWeek['Monday'],
						'Tuesday' => $totalHoursWeek['Tuesday'],
						'Wednesday' => $totalHoursWeek['Wednesday'],
						'Thursday' => $totalHoursWeek['Thursday'],
						'Friday' => $totalHoursWeek['Friday'],
						'Saturday' => $totalHoursWeek['Saturday'],
					];
					foreach ($totalHoursWeeks as $day => $workedHours) {
						$newRecord[$day] = $workedHours;
					}
					$payrollRecords->push($newRecord);
				}
			}
		}
		$payslipHtml = view('dtr.summary', ['payrollRecords' => $payrollRecords])->render();
		$dompdf->loadHtml($payslipHtml);
		$dompdf->setPaper('Legal', 'landscape');
		$dompdf->render();
		return $dompdf->stream('Attendance_Summary.pdf', ['Attachment' => false]);
	}
}
