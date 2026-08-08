@extends('layout.reports')

@section('title', 'Annual Summary Report')

@section('report-title', 'Annual Summary Report')

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
    <div class="summary-item">
        <h2>{{ $data['burials'] }}</h2>
        <p>Burials (Disposal Type)</p>
    </div>
    <div class="summary-item">
        <h2>{{ $data['cremations'] }}</h2>
        <p>Cremations (Disposal Type)</p>
    </div>
</div>

<h3>Monthly Breakdown for {{ $data['year'] }}</h3>
<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['by_month'] as $month)
        <tr>
            <td>{{ $month['month_name'] }}</td>
            <td>{{ $month['count'] }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" style="text-align: center;">No records found for this year</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection