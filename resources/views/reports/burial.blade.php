@extends('layout.reports')

@section('title', 'Burial Records Report')

@section('report-title', 'Burial Records Report')

@section('total-records', count($data))

@section('content')
<table>
    <thead>
        <tr>
            <th>Seq. No</th>
            <th>Deceased Name</th>
            <th>Date of Burial</th>
            <th>Phase</th>
            <th>Cluster</th>
            <th>Lot</th>
            <th>Address</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $index => $burial)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td>{{ $burial->deceasedRecord->first_name }} {{ $burial->deceasedRecord->last_name }}</td>
            <td>{{ \Carbon\Carbon::parse($burial->deceasedRecord->date_of_depository)->format('F d, Y') }}</td>
            <td>{{ $burial->lot && $burial->lot->cluster && $burial->lot->cluster->phase ? $burial->lot->cluster->phase->phase_name : 'N/A' }}</td>
            <td>{{ $burial->lot && $burial->lot->cluster ? $burial->lot->cluster->cluster_name : 'N/A' }}</td>
            <td>{{ $burial->lot ? $burial->lot->column . $burial->lot->row : 'N/A' }}</td>
            <td>{{ $burial->deceasedRecord->address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
