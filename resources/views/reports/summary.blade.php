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

<h3>Daily Breakdown for {{ $data['month'] }}</h3>
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        @forelse($data['by_day'] as $day)
        <tr>
            <td>{{ \Carbon\Carbon::parse($day->day)->format('F d, Y') }}</td>
            <td>{{ $day->count }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="2" style="text-align: center;">No records found for this month</td>
        </tr>
        @endforelse
    </tbody>
</table>
@endsection
