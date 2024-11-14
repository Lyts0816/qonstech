<!-- resources/views/dtr/show.blade.php -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DTR for {{ $employee->full_name }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: white;
        }

        .container {
            width: 100%;
            text-align: center;
            padding: 10px;
            box-sizing: border-box;
            margin: 5px;
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 11px;

        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: right;
        }

        th {
            background-color: #f2f2f2;
        }

        .logo {
            width: 200px;
            height: auto;
        }
        h2 {
            margin-top: -20px;
        }
    </style>
</head>
<?php
 $imageData = base64_encode(file_get_contents(public_path('images/qonstech.png')));
 $src = 'data:image/png;base64,' . $imageData;
?>
<body>
    <div>
        <div class="container" id="dtr-container">
            <img src="{{ $src }}" alt="Company Logo" class="logo">

            <h4 class="text-2xl font-bold">DAILY TIME RECORD</h4>
            <h2>{{ $employee->full_name }}</h2>

            <table>
                <thead>
                    <tr>
                        <th style="text-align:left">Date</th>
                        <th>Morning Check-in</th>
                        <th>Morning Checkout</th>
                        <th>Afternoon Check-in</th>
                        <th>Afternoon Checkout</th>
                        <th>Overtime In</th>
                        <th>Overtime Out</th>
                        <th>Morning Late</th>
                        <th>Morning Undertime</th>
                        <th>Afternoon Late</th>
                        <th>Afternoon Undertime</th>
                        <th>Total Worked Hours</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data as $row)
                        <tr>
                            <td style="text-align:left">{{ $row['Date'] ? \Carbon\Carbon::parse($row['Date'])->format(' F j, Y') : 'N/A' }}</td>
                            <td>{{ $row['Checkin_One'] > 0 ? $row['Checkin_One'] : 0 }}</td>
                            <td>{{ $row['Checkout_One'] > 0 ? $row['Checkout_One'] : 0 }}</td>
                            <td>{{ $row['Checkin_Two'] > 0 ? $row['Checkin_Two'] : 0 }}</td>
                            <td>{{ $row['Checkout_Two'] > 0 ? $row['Checkout_Two'] : 0 }}</td>
                            <td>{{ $row['Overtime_In'] > 0 ? $row['Overtime_In'] : 0 }}</td>
                            <td>{{ $row['Overtime_Out'] > 0 ? $row['Overtime_Out'] : 0 }}</td>
                            <td>{{ $row['MorningTardy'] > 0 ? $row['MorningTardy'] : 0 }} mins</td>
                            <td>{{ $row['MorningUndertime'] > 0 ? $row['MorningUndertime'] : 0 }} mins</td>
                            <td>{{ $row['AfternoonTardy'] > 0 ? $row['AfternoonTardy'] : 0 }} mins</td>
                            <td>{{ $row['AfternoonUndertime'] > 0 ? $row['AfternoonUndertime'] : 0 }} mins</td>
                            <td>{{ $row['TotalHours'] > 0 ? $row['TotalHours'] : 0 }} mins</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7" style="text-align: right; font-weight: bold;">Total:</td>
                        <td style="font-weight: bold;">
                            {{ collect($data)->sum('MorningTardy') }} mins
                        </td>
                        <td style="font-weight: bold;">
                            {{ collect($data)->sum('MorningUndertime') }} mins
                        </td>
                        <td style="font-weight: bold;">
                            {{ collect($data)->sum('AfternoonTardy') }} mins
                        </td>
                        <td style="font-weight: bold;">
                            {{ collect($data)->sum('AfternoonUndertime') }} mins
                        </td>
                        <td style="font-weight: bold;">
                            {{ collect($data)->sum('TotalHours') }} mins
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</body>

</html>