<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Certificate of Service</title>
    <style>
        body { font-family: serif; font-size: 13px; margin: 40px; }
        h1 { text-align: center; font-size: 20px; text-transform: uppercase; margin-bottom: 4px; }
        .subtitle { text-align: center; font-size: 12px; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        td { padding: 8px 10px; vertical-align: top; }
        td:first-child { width: 40%; font-weight: bold; }
        .divider { border-top: 1px solid #000; margin: 20px 0; }
        .footer { margin-top: 60px; text-align: right; }
    </style>
</head>
<body>
    <h1>Certificate of Service</h1>
    <p class="subtitle">Panteon Information and Management System</p>

    <div class="divider"></div>

    <table>
        <tr>
            <td>Deceased Name:</td>
            <td>{{ $data['deceased_name'] }}</td>
        </tr>
        <tr>
            <td>Deceased Address:</td>
            <td>{{ $data['deceased_address'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td>Date of Death:</td>
            <td>{{ $data['date_of_death'] ? \Carbon\Carbon::parse($data['date_of_death'])->format('F d, Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <td>Date of Depository:</td>
            <td>{{ $data['date_of_depository'] ? \Carbon\Carbon::parse($data['date_of_depository'])->format('F d, Y') : 'N/A' }}</td>
        </tr>
        <tr>
            <td>Applicant Name:</td>
            <td>{{ $data['applicant_name'] }}</td>
        </tr>
        <tr>
            <td>Applicant Address:</td>
            <td>{{ $data['applicant_address'] }}</td>
        </tr>
        <tr>
            <td>Relationship to Deceased:</td>
            <td>{{ $data['relationship'] ?? 'N/A' }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="footer">
        <p>Date Issued: {{ \Carbon\Carbon::now()->format('F d, Y') }}</p>
    </div>
</body>
</html>
