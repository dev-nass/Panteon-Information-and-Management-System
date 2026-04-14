<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>@yield('title') - Panteon De Dasmariñas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .text-center {
            text-align: center;
        }

        .mb-5 {
            margin-bottom: 20px;
        }

        .mb-2 {
            margin-bottom: 8px;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mt-1 {
            margin-top: 4px;
        }

        .mt-5 {
            margin-top: 20px;
        }

        .mt-8 {
            margin-top: 32px;
        }

        .my-4 {
            margin-top: 16px;
            margin-bottom: 16px;
        }

        .mx-4 {
            margin-left: 16px;
            margin-right: 16px;
        }

        .m-0 {
            margin: 0;
        }

        .p-2 {
            padding: 8px;
        }

        .p-5 {
            padding: 20px;
        }

        .header-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .header-top {
            width: 100%;
            margin-bottom: 8px;
        }

        .header-top table {
            width: 70%;
            border: none;
            margin: 0 auto;
        }

        .header-top td {
            border: none;
            vertical-align: middle;
            text-align: center;
            padding: 0 2px;
        }

        .header-top img {
            height: 60px;
        }

        .header-title h1 {
            margin: 0;
            color: #16a34a;
            font-size: 24px;
            font-weight: bold;
            white-space: nowrap;
        }

        .header-title p {
            margin: 4px 0 0 0;
            font-size: 14px;
            color: #666;
            white-space: nowrap;
        }

        .header-divider {
            border-top: 2px solid #16a34a;
            margin: 16px 0;
        }

        .report-info {
            text-align: center;
            margin-bottom: 8px;
            font-size: 11px;
        }

        .report-info .font-bold {
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #16a34a;
            color: white;
        }

        .footer {
            margin-top: 32px;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .signature-section {
            margin-top: 40px;
            text-align: right;
            padding-right: 50px;
        }

        .signature-line {
            border-top: 1px solid #000;
            width: 200px;
            margin: 0 0 5px auto;
        }

        .signature-name {
            font-weight: bold;
            color: #000;
        }

        .signature-title {
            font-size: 10px;
            color: #666;
        }

        .disclaimer {
            margin-top: 20px;
            font-size: 10px;
            font-style: italic;
            color: #666;
        }

        .summary-box {
            background: #f0fdf4;
            border: 2px solid #16a34a;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .summary-item {
            display: inline-block;
            width: 45%;
            margin: 10px;
            text-align: center;
            vertical-align: top;
        }

        .summary-item h2 {
            color: #16a34a;
            font-size: 36px;
            margin: 0;
            font-weight: bold;
        }

        .summary-item p {
            color: #666;
            margin: 5px 0;
        }

        h3 {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="header-container">
        <div class="header-top">
            <table>
                <tr>
                    <td style="width: 20%;">
                        <img src="{{ public_path('images/dasmarinas-logo.png') }}" alt="Logo">
                    </td>
                    <td style="width: 60%;">
                        <div class="header-title">
                            <h1>Panteon De Dasmariñas</h1>
                            <p>City of Dasmariñas</p>
                        </div>
                    </td>
                    <td style="width: 20%;">
                        <img src="{{ public_path('images/dasmarinas-logo.png') }}" alt="Logo">
                    </td>
                </tr>
            </table>
        </div>
        <div class="header-divider"></div>
        <div class="report-info">
            <p class="font-bold">@yield('report-title')</p>
            <p>Period: {{ \Carbon\Carbon::parse($startDate)->format('F d, Y') }} to
                {{ \Carbon\Carbon::parse($endDate)->format('F d, Y') }}
            </p>
            <p>Generated on: {{ date('F d, Y') }}</p>
        </div>
    </div>

    @yield('content')

    <div class="footer">
        <p>Total Records: @yield('total-records')</p>
        <p class="disclaimer">This is a system-generated report from the Panteon Information and Management System.</p>
        <p>Panteon Information and Management System</p>
    </div>

    <div class="signature-section">
        <div class="signature-line"></div>
        <p class="signature-name">{{ auth()->user()->first_name . " " . auth()->user()->last_name}}</p>
        <p class="signature-title">Authorized Clerk</p>
    </div>
</body>

</html>