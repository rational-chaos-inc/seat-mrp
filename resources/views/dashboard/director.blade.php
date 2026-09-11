@extends('web::layouts.grids.4-4-4')

@section('title', 'Corporation Activities')

@section('left')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Corporation Overview</h3>
    </div>
    <div class="card-body">
        <p>Time Period: <strong>{{ $timeWindow }}</strong></p>
        <p>Records Shown: <strong>{{ count($activities) }}</strong></p>
    </div>
</div>
@endsection

@section('middle')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">All Activities</h3>
    </div>
    <div class="card-body">
        @if($activities->isEmpty())
            <div class="alert alert-info">No activities recorded yet</div>
        @else
            <table class="table table-sm table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Character</th>
                        <th>Corp</th>
                        <th>Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        <tr>
                            <td>{{ $activity->activity_timestamp->format('Y-m-d H:i') }}</td>
                            <td>{{ $activity->character_id }}</td>
                            <td>{{ $activity->corporation_id ?? 'N/A' }}</td>
                            <td><span class="badge bg-primary">{{ $activity->activity_type }}</span></td>
                            <td>
                                @if($activity->metadata)
                                    @php $meta = $activity->metadata; @endphp
                                    @if(isset($meta['quantity']))
                                        {{ $meta['quantity'] }} units
                                    @elseif(isset($meta['amount']))
                                        ISK {{ number_format($meta['amount']) }}
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection

@section('right')
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Time Window</h3>
    </div>
    <div class="card-body">
        <div class="btn-group d-flex flex-column" role="group">
            <a href="?window=day" class="btn btn-sm @if($timeWindow === 'day') btn-primary @else btn-outline-primary @endif">Day</a>
            <a href="?window=week" class="btn btn-sm @if($timeWindow === 'week') btn-primary @else btn-outline-primary @endif">Week</a>
            <a href="?window=month" class="btn btn-sm @if($timeWindow === 'month') btn-primary @else btn-outline-primary @endif">Month</a>
            <a href="?window=quarter" class="btn btn-sm @if($timeWindow === 'quarter') btn-primary @else btn-outline-primary @endif">Quarter</a>
            <a href="?window=year" class="btn btn-sm @if($timeWindow === 'year') btn-primary @else btn-outline-primary @endif">Year</a>
        </div>
    </div>
</div>
@endsection
