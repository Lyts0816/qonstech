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
			// Get the employee ID from the query parameters
			$employeeId = $request->query('employee_id');
			$projectId = $request->query('project_id');
			$startDate = $request->query('startDate');
			$endDate = $request->query('endDate');

			$dompdf = new Dompdf();
			// Render each employee's payslip
			$payslipHtml = '';

			// Find the employee by ID
			$employee = Employee::findOrFail($employeeId);
			// $project = Project::findOrFail($projectId);

			// Retrieve DTR data for the employee
			// Example: replace with your actual DTR fetching logic
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
					// ->whereNull('ProjectID')
					// ->orWhere('ProjectID', 0)
					->get();
			}


			// dd($data);

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

					//Get the workschedule based on Schedule assign to employee
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

					$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
					$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
					$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
					$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

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

					// Fetch approved overtime records for the employee within the selected week period
					$approvedOvertimeRecords = \App\Models\Overtime::where('EmployeeID', $employee->id)
					->get();

					// Get the day of the week (e.g., 'monday', 'tuesday')
					$dayOfWeek = strtolower(Carbon::parse($attendances->Date)->format('l'));

					// Ensure that the employee has a schedule for the specific day
					if (!empty($WorkSched[0]) && $WorkSched[0]->$dayOfWeek) {
							// Parse the regular work hours (CheckinOne, CheckoutOne)
							$workStart = Carbon::parse($WorkSched[0]->CheckinOne);
							$workEnd = Carbon::parse($WorkSched[0]->CheckoutTwo);

							// Attendance check-in and check-out times
							$attendanceCheckin = Carbon::parse($attendances->Checkin_One);
							$attendanceCheckout = Carbon::parse($attendances->Checkout_Two);

							// Check if the employee has approved overtime for this date
							$overtimeRecord = $approvedOvertimeRecords->firstWhere('Date', $attendances->Date);

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
					if ($approvedOvertimeRecords->contains('Date', $attendances->Date) && !$WorkSched[0]->$dayOfWeek) {
							$overtimeRecord = $approvedOvertimeRecords->firstWhere('Date', $attendances->Date);
							if ($overtimeRecord) {

									$totalOvertimeHours = 2.5;
									// Calculate total hours based on check-in and check-out times without work schedule comparison
									$attendanceCheckin = Carbon::parse($attendances->Checkin_One);
									$attendanceCheckout = Carbon::parse($attendances->Checkout_Two);

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

			// Store the total overtime hours in the new record
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
		// dd($request->payroll_id);
		$dompdf = new Dompdf();
		// Render each employee's payslip
		$payslipHtml = '';
		// dd($projectId);

		$payroll = \App\Models\Payroll::where('id', $request->payroll_id)->first();

		// Check if payroll frequency is Kinsenas or Weekly
		$weekPeriod = \App\Models\WeekPeriod::where('id', $payroll->weekPeriodID)->first();

		// dd($weekPeriod->StartDate);

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
				// $attendance = \App\Models\Attendance::where('ProjectID', $payroll->ProjectID)
				// 	->whereBetween('Date', [$startDate, $endDate])
				// 	->get();

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
				// $attendance = \App\Models\Attendance::where('ProjectID', $payroll->ProjectID)
				// 	->whereBetween('Date', [$startDate, $endDate])
				// 	->orderBy('Date', 'ASC')
				// 	->get();
			}
		}

		$start = Carbon::parse($startDate);
		$end = Carbon::parse($endDate);

		$dates = [];

		// Loop through the dates
		while ($start->lte($end)) {
			$dates[] = $start->toDateString(); // Add the date in 'Y-m-d' format to the array
			$start->addDay(); // Increment the date by one day
		}

		$payrollRecords = collect();
		// $finalAttendance = $attendance;
		foreach ($dates as $date) {

			$attendance = \App\Models\Attendance::where('ProjectID', $payroll->ProjectID)
				->where('Date', $date)
				->orderBy('Date', 'ASC')
				->get();
			// dd($attendance->toArray());
			foreach ($attendance as $attendances) {

				$employeesWPosition = \App\Models\Employee::where('project_id', $payroll->ProjectID)
					->where('employees.id', $attendances['Employee_ID'])
					->whereNotNull('employees.schedule_id')
					->join('positions', 'employees.position_id', '=', 'positions.id')
					->select('employees.*', 'positions.PositionName', 'positions.MonthlySalary', 'positions.HourlyRate'); // Only select needed fields
				$validator = Validator::make($request->all(), [
					'project_id' => 'nullable|string|integer',
				]);

				if ($validator->fails()) {
					return response()->json($validator->errors(), 422);
				}
				$employeesWPosition = $employeesWPosition->get();

				// dd($employeesWPosition);
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
					// $newRecord = $request->all();

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
					// dd($attendances);
					$attendanceDate = Carbon::parse($attendances['Date']);
					$newRecord['DateNow'] = substr($attendanceDate, 0, 10);
					$newRecord['MorningCheckIn'] = $attendances["Checkin_One"];
					$newRecord['MorningCheckOut'] = $attendances["Checkout_One"];
					$newRecord['AfternoonCheckIn'] = $attendances["Checkin_Two"];
					$newRecord['AfternoonCheckOut'] = $attendances["Checkout_Two"];

					$GetHoliday = \App\Models\Holiday::where('HolidayDate', substr($attendanceDate, 0, 10))->get();
					$Holiday = $GetHoliday;

					//Get the workschedule based on Schedule assign to employee
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

						// Check if the attendance date is a Sunday
						$dayOfWeek = $attendanceDate->format('l');
						if ($attendanceDate->isSunday()) {
							// Set official work start and end times
							$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
							$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
							$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
							$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM

							if ($attendances["Checkin_One"] && $attendances["Checkout_One"]) {
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
							}
							if ($attendances["Checkin_Two"] && $attendances["Checkout_Two"]) {
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
							}
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
							$newRecord['TotalHoursSunday'] = $TotalHoursSunday;
						} else { // regular day monday to saturday
							// If date is Holiday
							//dd(count(value: $Holiday));
							if (count(value: $Holiday) > 0 && $Holiday[0]->ProjectID == $employee->project_id) {
								$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
								$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
								$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
								$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM
								if ($attendances["Checkin_One"] && $attendances["Checkout_One"]) {
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
								}
								if ($attendances["Checkin_Two"] && $attendances["Checkout_Two"]) {
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
								}
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
								// else {
								// 	$netWorkedHours = $totalWorkedHours - $totalLateHours;
								// }

								// $TotalHours += $netWorkedHours;
							} else { // regular Day
								$morningStart = Carbon::createFromTime($In1Array[0], $In1Array[1], $In1Array[2]); // 8:00 AM
								$morningEnd = Carbon::createFromTime($Out1Array[0], $Out1Array[1], $Out1Array[2]);  // 12:00 PM
								$afternoonStart = Carbon::createFromTime($In2Array[0], $In2Array[1], $In2Array[2]); // 1:00 PM
								$afternoonEnd = Carbon::createFromTime($Out2Array[0], $Out2Array[1], $Out2Array[2]);  // 5:00 PM
								if ($attendances["Checkin_One"] && $attendances["Checkout_One"]) {
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
								}
								if ($attendances["Checkin_Two"] && $attendances["Checkout_Two"]) {
									$checkinTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkin_Two"], 0, 5));
									$checkoutTwo = Carbon::createFromFormat('H:i', substr($attendances["Checkout_Two"], 0, 5));

									// $lateAfternoonHours = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo->diffInMinutes($afternoonEnd) / 60 : 0;

									$effectivecheckinTwo = $checkinTwo->greaterThan($afternoonStart) ? $checkinTwo : $afternoonStart;
									$effectiveCheckOutTwo = $checkoutTwo->lessThan($afternoonEnd) ? $checkoutTwo : $afternoonEnd;
									$workedAfternoonMinutes = $effectivecheckinTwo->diffInMinutes($checkoutTwo);
									$underTimeAfternoonMinutes = $effectiveCheckOutTwo->diffInMinutes($afternoonEnd);
									$tardinessAfternoonMinutes = $afternoonStart->diffInMinutes($checkinTwo);
									$workedAfternoonHours = $workedAfternoonMinutes / 60;
								}
								$totalWorkedHours = $workedMorningHours + $workedAfternoonHours;
								// $totalLateHours = $lateMorningHours + $lateAfternoonHours;
								$netWorkedHours = $totalWorkedHours
								;
								// $netWorkedHours = $totalWorkedHours - $totalLateHours;
								// $SundayWorkedHours = $totalSundayWorkedHours - $totalSundayLateHours;

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
					// dd($totalHoursWeeks);

					// // Sum total hours for the week
					// $newRecord['TotalHours'] = array_sum($totalHoursWeek);



					// Prepare the new record with total hours for each day
					foreach ($totalHoursWeeks as $day => $workedHours) {
						$newRecord[$day] = $workedHours; // Store total hours for each day
					}


					$payrollRecords->push($newRecord);
				}

			}
		}
		// dd($payrollRecords);
		$payslipHtml = view('dtr.summary', ['payrollRecords' => $payrollRecords])->render();

		// dd($payrollRecords);
		$dompdf->loadHtml($payslipHtml);
		$dompdf->setPaper('Legal', 'landscape');
		$dompdf->render();

		return $dompdf->stream('Attendance_Summary.pdf', ['Attachment' => false]);
		// dd($data);
		// return view('dtr.show', compact('employee', 'data'));
	}


}