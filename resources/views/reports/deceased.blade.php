<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Deceased Records Report</title>
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
        <h1>Deceased Records Report</h1>
        <p>Period: {{ $startDate }} to {{ $endDate }}</p>
        <p>Generated on: {{ date('F d, Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Seq. No</th>
                <th>First Name</th>
                <th>Middle Name</th>
                <th>Last Name</th>
                <th>Date of Burial</th>
                <th>Address</th>
                <th>Applicant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $index => $deceased)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $deceased->first_name }}</td>
                <td>{{ $deceased->middle_name }}</td>
                <td>{{ $deceased->last_name }}</td>
                <td>{{ $deceased->date_of_depository }}</td>
                <td>{{ $deceased->address }}</td>
                <td>{{ $deceased->applicant ? $deceased->applicant->first_name . ' ' . $deceased->applicant->last_name : 'N/A' }}</td>
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
