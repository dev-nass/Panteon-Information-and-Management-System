@extends('layout.reports')

@section('title', 'Phase Availability Report')

@section('report-title', 'Phase Availability Report')

@section('content')
<div class="summary-box">
    <p style="text-align: center; color: #666; margin: 0;">
        This report shows the current availability status of all phases in the cemetery.
    </p>
</div>

<table>
    <thead>
        <tr>
            <th>Phase Name</th>
            <th style="text-align: center;">Total Clusters</th>
            <th style="text-align: center;">Total Lots</th>
            <th style="text-align: center;">Occupied</th>
            <th style="text-align: center;">Available</th>
            <th style="text-align: center;">Occupancy Rate</th>
        </tr>
    </thead>
    <tbody>
        @php
            $totalClusters = 0;
            $totalLots = 0;
            $totalOccupied = 0;
            $totalAvailable = 0;
        @endphp
        
        @forelse($data as $phase)
        @php
            $totalClusters += $phase['total_clusters'];
            $totalLots += $phase['total_lots'];
            $totalOccupied += $phase['total_occupants'];
            $totalAvailable += $phase['available_lots'];
        @endphp
        <tr>
            <td>{{ $phase['phase_name'] }}</td>
            <td style="text-align: center;">{{ $phase['total_clusters'] }}</td>
            <td style="text-align: center;">{{ $phase['total_lots'] }}</td>
            <td style="text-align: center;">{{ $phase['total_occupants'] }}</td>
            <td style="text-align: center;">{{ $phase['available_lots'] }}</td>
            <td style="text-align: center;">{{ number_format($phase['occupancy_rate'], 2) }}%</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align: center;">No phase data available</td>
        </tr>
        @endforelse
        
        @if(count($data) > 0)
        <tr style="background-color: #f0fdf4; font-weight: bold;">
            <td>TOTAL</td>
            <td style="text-align: center;">{{ $totalClusters }}</td>
            <td style="text-align: center;">{{ $totalLots }}</td>
            <td style="text-align: center;">{{ $totalOccupied }}</td>
            <td style="text-align: center;">{{ $totalAvailable }}</td>
            <td style="text-align: center;">
                {{ $totalLots > 0 ? number_format(($totalOccupied / $totalLots) * 100, 2) : 0 }}%
            </td>
        </tr>
        @endif
    </tbody>
</table>

<div style="margin-top: 30px; padding: 15px; background: #f9fafb; border-left: 4px solid #16a34a;">
    <h3 style="margin-top: 0; color: #16a34a;">Summary Statistics</h3>
    <p style="margin: 5px 0;"><strong>Total Phases:</strong> {{ count($data) }}</p>
    <p style="margin: 5px 0;"><strong>Total Clusters:</strong> {{ $totalClusters }}</p>
    <p style="margin: 5px 0;"><strong>Total Lot Capacity:</strong> {{ $totalLots }}</p>
    <p style="margin: 5px 0;"><strong>Currently Occupied:</strong> {{ $totalOccupied }} ({{ $totalLots > 0 ? number_format(($totalOccupied / $totalLots) * 100, 2) : 0 }}%)</p>
    <p style="margin: 5px 0;"><strong>Available Lots:</strong> {{ $totalAvailable }} ({{ $totalLots > 0 ? number_format(($totalAvailable / $totalLots) * 100, 2) : 0 }}%)</p>
</div>
@endsection
