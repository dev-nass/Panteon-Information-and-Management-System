@extends('layout.reports')

@section('title', 'Deceased Records Report')

@section('report-title', 'Deceased Records Report')

@section('total-records', count($data))

@section('content')
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
            <td>{{ \Carbon\Carbon::parse($deceased->date_of_depository)->format('F d, Y') }}</td>
            <td>{{ $deceased->address }}</td>
            <td>{{ $deceased->applicant ? $deceased->applicant->first_name . ' ' . $deceased->applicant->last_name : 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
