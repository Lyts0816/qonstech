<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Summary Report</title>
    <style>
        @page {
            size: Legal;
            margin: 10px;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 14in;
            margin: 10px auto;
            border: 1px solid #000;
            padding: 10px;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }

        .header img {
            width: 300px;
            height: auto;
            margin: 0;
        }

        .header div {
            text-align: left;
            flex-grow: 1;
        }

        .header h1 {
            font-size: 24px;
            margin: 0;
        }

        .header p {
            font-size: 14px;
        }

        .project-name {
            font-size: 18px;
            color: red;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
            /* Smaller font size for better fit */
        }

        th,
        td {
            border: 2px solid #000;
            padding: 3px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }

        .total-row th,
        .total-row td {
            font-weight: bold;
        }

        .signatures {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .signatures div {
            text-align: left;
            width: 30%;
        }

        #exportPDF {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- <script src="{{ asset('js/html2canvas.min.js') }}"></script>
    <script src="{{ asset('js/jspdf.umd.min.js') }}"></script> -->
</head>

<body>
    <div class="container">
        <div class="header">
            <div>
                @php
                    $employeeda = $payrollData->first();
                    $formattedPeriod = '';
                    if (isset($employeeda['Period'])) {
                        $dates = explode(' - ', $employeeda['Period']);
                        if (count($dates) == 2) {
                            $startDate = \Carbon\Carbon::parse($dates[0])->format('m-d-Y');
                            $endDate = \Carbon\Carbon::parse($dates[1])->format('m-d-Y');
                            $formattedPeriod = "{$startDate} - {$endDate}";
                        }
                    }
                @endphp
                @if ($employeeda)
                    <h1>Payroll Summary Report</h1>
                    {{-- <p>Period covered: {{ $employeeda['Period'] ?? '' }}</p> --}}
                    <p>Period covered: {{ $formattedPeriod }}</p>
                    <p class="project-name">{{ $employeeda['ProjectName'] ?? '' }}</p>
                @endif
            </div>
            <img src="{{ asset('images/qonstech.png') }}" alt="Company Logo" class="logo">
        </div>

        <table>
            <thead>
                <tr>
                    <th rowspan="2">ID</th>
                    <th rowspan="2">Name</th>
                    <th rowspan="2">Position</th>
                    <th rowspan="2">Monthly Salary</th>
                    <th rowspan="2">Hourly Rate</th>
                    <th rowspan="2">Regular Hours</th <th rowspan="2">Total Hours</th>
                    <th rowspan="2">O.T Hours</th>
                    <th colspan="2">Holidays</th>
                    <th rowspan="2">Earnings Amount</th>
                    <th rowspan="2">Gross Amount</th>
                    <th colspan=10">Deductions</th>
                    <th rowspan="2">Total Deductions & Adjustment</th>
                    <th rowspan="2">NET PAY</th>
                    <th rowspan="2">SIGNATURE</th>
                </tr>
                <tr>

                    <th>Regular Holiday Hours</th>
                    <th>Special Holiday Hours</th>
                    <th>Tardiness</th>
                    <th>Undertime</th>
                    <th>TAXES</th>
                    <th>SSS</th>
                    <th>PHIC</th>
                    <th>HDMF</th>
                    <th>Cash Advances</th>
                    <th>SSS LOAN</th>
                    <th>SALARY LOAN</th>
                    <th>HDMF LOAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payrollData as $employee)
                    <tr>
                        <td>{{ $employee['EmployeeID'] ?? '' }}</td>
                        <td>{{ $employee['first_name'] . ' ' . ($employee['middle_name'] ?? '') . ' ' . ($employee['last_name'] ?? '') }}
                        </td>
                        <td>{{ $employee['position'] ?? '' }}</td>
                        {{-- <td>{{ $employee['ProjectName'] ?? '' }}</td> --}}
                        <td>P{{ number_format($employee['monthlySalary'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['hourlyRate'] ?? 0, 2) }}</td>


                        <td>{{ number_format($employee['TotalHours'] ?? 0, 2) }}</td>
                        <td>{{ number_format($employee['TotalHours'] ?? 0, 2) }}</td>
                        <td>{{ number_format($employee['TotalOvertimeHours'] ?? 0, 2) }}</td>
                        <td>{{ $employee['TotalHrsRegularHol'] ?? 0 }}</td>
                        <td>{{ $employee['TotalHrsSpecialHol'] ?? 0 }}</td>
                        <td>P{{ number_format($employee['EarningPay'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['GrossPay'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['TotalTardinessDed'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['TotalUndertimeDed'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['WTAXDeduction'] ?? 0, 2) }}</td>

                        <td>P{{ number_format($employee['SSSDeduction'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['PhilHealthDeduction'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['PagIbigDeduction'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['DeductionFee'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['SSSLoan'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['SalaryLoan'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['PagibigLoan'] ?? 0, 2) }}</td>

                        <!-- <td>P{{ number_format('0') }}</td>
                                <td>P{{ number_format('0') }}</td>
                                <td>P{{ number_format('0') }}</td> -->

                        <td>P{{ number_format($employee['TotalDeductions'] ?? 0, 2) }}</td>
                        <td>P{{ number_format($employee['NetPay'] ?? 0, 2) }}</td>
                        <td>{{ $employee['SIGNATURE'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signatures">
            <div>
                <p>Prepared by:</p>
                <p>_______________________</p>
                <p>Alma Mae S. Gepellano</p>
                <p>HR OFFICER</p>
            </div>
            <div>
                <p>Certified Correct by:</p>
                <p>_______________________</p>
                <p>Rosimo Jonas</p>
                <p>VP ADMIN</p>
            </div>
            <div>
                <p>Approved by:</p>
                <p>_______________________</p>
                <p>Mary Jane Villanueva</p>
                <p>VP FINANCE</p>
            </div>

        </div>
        <b>Date Generated: {{ now()->format('m-d-Y H:i:s') }}</b>
    </div>

    <!-- Export to PDF button -->
    <button id="exportPDF">Export to PDF</button>

    <script>
        document.getElementById('exportPDF').addEventListener('click', function() {
            const {
                jsPDF
            } = window.jspdf;

            // Initialize jsPDF with landscape orientation and a custom size (8x13 inches)
            const doc = new jsPDF('landscape', 'pt', [576, 936]);

            const element = document.querySelector('.container');

            if (element) {
                doc.html(element, {
                    callback: function(doc) {
                        doc.save('payroll-report.pdf');
                    },
                    x: 10,
                    y: 10,
                    autoPaging: 'text',
                    width: 900,
                    windowWidth: 1300,
                    scale: 0.8
                });
            } else {
                console.error('Element .container not found!');
            }
        });
    </script>
</body>

</html>
