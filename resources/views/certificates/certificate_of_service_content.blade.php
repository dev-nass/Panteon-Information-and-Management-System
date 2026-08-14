<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Certification of Service</title>
    <style>
        @page {
            margin: 220px 85px;
            size: A4;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            margin: 0;
            padding: 0;
            color: #222;
        }

        .cert-title {
            font-size: 28px;
            font-weight: bold;
            letter-spacing: 6px;
            color: #222;
            text-align: center;
            margin: 0 0 45px 0;
        }

        .body-text {
            font-size: 13px;
            line-height: 1.9;
            text-align: justify;
            text-indent: 40px;
            margin-bottom: 16px;
        }

        .body-text-center {
            font-size: 13px;
            line-height: 1.9;
            text-align: center;
            margin: 40px 0 0 0;
        }
    </style>
</head>

<body>

    <div class="cert-title">C E R T I F I C A T I O N</div>

    <div class="body-text">
        This is to certify that <strong>{{ strtoupper($data['deceased_name']) }}</strong>
        of {{ $data['deceased_address'] ?? 'N/A' }}.
        Passed away on
        {{ $data['date_of_death'] ? \Carbon\Carbon::parse($data['date_of_death'])->format('F d, Y') : 'N/A' }},
        at {{ $data['place_of_death'] ?? 'N/A' }}.
        His/her <u>burial</u> took place on
        <strong>{{ $data['date_of_depository'] ? \Carbon\Carbon::parse($data['date_of_depository'])->format('F d, Y') : 'N/A' }}</strong>
        at <strong>{{ $data['burial_place'] ?? 'Panteon De Dasmariñas' }}</strong>.
    </div>

    <div class="body-text">
        This certification is issued to <strong>{{ strtoupper($data['applicant_name']) }}</strong>
        legal-age Filipino citizen residing in {{ $data['applicant_address'] }}.
        He/she is the <strong>{{ $data['relationship'] ?? 'N/A' }}</strong> of the deceased.
    </div>

    <div class="body-text">
        This Certification is being issued for his/her application for
        <strong>Interment/Inurnment / and other purposes.</strong>
    </div>

    <div class="body-text-center">
        Issued this <strong>{{ \Carbon\Carbon::now()->format('jS') }} day of
            {{ \Carbon\Carbon::now()->format('F Y') }}</strong>
        at the City of Dasmariñas, Cavite
    </div>

</body>

</html>
