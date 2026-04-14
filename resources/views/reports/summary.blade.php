@extends('layout.reports')

@section('title', 'Monthly Summary Report')

@section('report-title', 'Monthly Summary Report')

@section('total-records', $data['total_burials'] + $data['total_deceased'])

@section('content')
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
@endsection
