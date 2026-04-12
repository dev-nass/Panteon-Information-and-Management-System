<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Summary Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; color: #16a34a; }
        .header { text-align: center; margin-bottom: 20px; }
        .summary-box { background: #f0fdf4; border: 2px solid #16a34a; padding: 20px; margin: 20px 0; border-radius: 8px; }
        .summary-item { display: inline-block; width: 45%; margin: 10px; text-align: center; }
        .summary-item h2 { color: #16a34a; font-size: 36px; margin: 0; }
        .summary-item p { color: #666; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #16a34a; color: white; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Monthly Summary Report</h1>
        <p>Period: {{ $startDate }} to {{ $endDate }}</p>
        <p>Generated on: {{ date('F d, Y') }}</p>
    </div>

    <div class="summary-box">
        <div class="summary-item">
            <h2>{{ $data['total_burials'] }}</h2>
            <p>Total Burials</p>
        </div>
        <div class="summary-item">
            <h2>{{ $data['total_deceased'] }}</h2>
            <p>Total Deceased Records</p>
        </div>
    </div>

    <h3>Monthly Breakdown</h3>
    <table>
        <thead>
            <tr>
                <th>Month</th>
                <th>Count</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data['by_month'] as $month)
            <tr>
                <td>{{ $month->month }}</td>
                <td>{{ $month->count }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Panteon Information and Management System</p>
    </div>
</body>
</html>
