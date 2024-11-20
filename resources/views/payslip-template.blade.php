<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
    <style>
        @page {
            size: Legal;
            margin: 10px;
        }

        body {
            font-family: 'Times New Roman', sans-serif;
            margin: 0;
            padding: 10px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            font-size: 8px;
        }

        .container {
            width: 48%;
            height: 48%;
            border: 1px solid #000;
            padding: 10px;
            box-sizing: border-box;
            margin: 5px;
            display: inline-block;
        }

        h1 {
            text-align: center;
            font-size: 12px;
            margin-bottom: 5px;
        }

        h3 {
            font-size: 10px;
            margin-bottom: 5px;
        }

        .employee-details,
        .earnings,
        .deductions {
            width: 100%;
            margin-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 4px;
            text-align: left;
        }

        .totals {
            width: 100%;
            display: flex;
            justify-content: space-between;
            margin-top: 5px;
        }

        .totals div {
            width: 48%;
            padding: 5px;
            border: 1px solid #000;
            font-size: 8px;
        }

        .net-pay {
            width: 100%;
            text-align: right;
            margin-top: 10px;
            font-size: 15px;
        }

        .logo-container {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }

        .logo {
            width: 100px;
            height: auto;
        }
    </style>
</head>

<body>
    <?php
    $imageData = base64_encode(file_get_contents(public_path('images/qonstech.png')));
    $src = 'data:image/png;base64,' . $imageData;
    ?>

    <div>
        <div class="container">
            <div class="logo-container">
                <img src="{{ $src }}" alt="Company Logo" class="logo">
            </div>
            <h1>PAYSLIP</h1>

            <!-- Employee Details -->
            <div class="employee-details">
                @foreach ($payrollRecords as $employee)
                @endforeach
                <table>
                    <tr>
                        <th>Employee Name</th>
                        <td>{{ $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') }}
                        </td>
                        <th>Employment Status</th>
                        <td>{{ $employee['EmployeeStatus'] }}</td>
                    </tr>
                    <tr>
                        <th>Position</th>
                        <td>{{ $employee['position'] ?? '' }}</td>
                        <th>Payroll Period</th>
                        <td>{{ $employee['Period'] }}</td>
                    </tr>
                    <tr>
                        <th>Monthly Salary</th>
                        <td>PHP&nbsp;{{ number_format($employee['monthlySalary'] ?? 0, 2) }}</td>
                        <th>Hourly Rate</th>
                        <td>PHP&nbsp;{{ number_format($employee['hourlyRate'] ?? 0, 2) }}</td>
                    </tr>
                </table>
            </div>

            <!-- Earnings Section -->
            <div class="earnings">
                <h3>Earnings</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Pay</td>
                            <td>PHP&nbsp;{{ number_format($employee['BasicPay'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Sunday Pay</td>
                            <td>PHP&nbsp;{{ number_format($employee['SundayPay'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Special Holiday Pay</td>
                            <td>PHP&nbsp;{{ number_format($employee['SpecialHolidayPay'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Regular Holiday Pay</td>
                            <td>PHP&nbsp;{{ number_format($employee['RegularHolidayPay'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Overtime Pay</td>
                            <td>PHP&nbsp;{{ number_format($employee['TotalOvertimePay'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Other Earnings</td>
                            <td>PHP&nbsp;{{ number_format($employee['EarningPay'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Total Gross Pay</td>
                            <td><b>PHP&nbsp;{{ number_format($employee['GrossPay'] ?? 0, 2) }}</b></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Deductions Section -->
            <div class="deductions">
                <h3>Deductions</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Tardiness</td>
                            <td>PHP&nbsp;{{ number_format($employee['TotalTardinessDed'] ?? 0, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Undertime</td>
                            <td>PHP&nbsp;{{ number_format($employee['TotalUndertimeDed'] ?? 0, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>Tax</td>
                            <td>PHP&nbsp;{{ number_format($employee['WTAXDeduction'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>SSS</td>
                            <td>PHP&nbsp;{{ number_format($employee['SSSDeduction'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Philhealth</td>
                            <td>PHP&nbsp;{{ number_format($employee['PhilHealthDeduction'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Pag-ibig</td>
                            <td>PHP&nbsp;{{ number_format($employee['PagIbigDeduction'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>SSS Loan</td>
                            <td>PHP&nbsp;{{ number_format($employee['SSSLoan'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Pagibig Loan</td>
                            <td>PHP&nbsp;{{ number_format($employee['PagibigLoan'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Salary Loan</td>
                            <td>PHP&nbsp;{{ number_format($employee['SalaryLoan'] ?? 0, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Cash Advances</td>
                            <td>PHP&nbsp;{{ number_format($employee['DeductionFee'] ?? 0, 2) }}</td>
                        </tr>

                        <tr>
                            <td>Total Deductions</td>
                            <td><b>PHP&nbsp;{{ number_format($employee['TotalDeductions'] ?? 0, 2) }}</b></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Net Pay Section -->
            <div class="net-pay">
                <p>Net Pay:<strong> PHP&nbsp;{{ number_format($employee['NetPay'] ?? 0, 2) }}</strong></p>
            </div>
        </div>
    </div>
</body>

</html>
