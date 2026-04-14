<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Burial Records Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { text-align: center; color: #16a34a; }
        .header { text-align: center; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #16a34a; color: white; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Burial Records Report</h1>
        <p>Period: {{ $startDate }} to {{ $endDate }}</p>
        <p>Generated on: {{ date('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Deceased Name</th>
                <th>Date of Burial</th>
                <th>Lot</th>
                <th>Address</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $burial)
            <tr>
                <td>{{ $burial->id }}</td>
                <td>{{ $burial->deceasedRecord->first_name }} {{ $burial->deceasedRecord->last_name }}</td>
                <td>{{ $burial->deceasedRecord->date_of_depository }}</td>
                <td>{{ $burial->lot && $burial->lot->properties ? $burial->lot->properties['column'] . $burial->lot->properties['row'] : 'N/A' }}</td>
                <td>{{ $burial->deceasedRecord->address }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Total Records: {{ count($data) }}</p>
        <p>Panteon Information and Management System</p>
    </div>
</body>
</html>
