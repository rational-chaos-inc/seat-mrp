@extends('web::layouts.grids.full')

@section('title', 'My Activities')

@section('full')
<div class="card">
    <div class="card-header with-border">
        <h3 class="card-title">My Activities</h3>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <strong>Time Period:</strong> {{ $timeWindow }} | <strong>Records:</strong> {{ count($activities) }}
            </div>
            <div class="col-md-6 text-right">
                <a href="?window=day" class="btn btn-sm @if($timeWindow === 'day') btn-primary @else btn-default @endif">Day</a>
                <a href="?window=week" class="btn btn-sm @if($timeWindow === 'week') btn-primary @else btn-default @endif">Week</a>
                <a href="?window=month" class="btn btn-sm @if($timeWindow === 'month') btn-primary @else btn-default @endif">Month</a>
                <a href="?window=quarter" class="btn btn-sm @if($timeWindow === 'quarter') btn-primary @else btn-default @endif">Quarter</a>
                <a href="?window=year" class="btn btn-sm @if($timeWindow === 'year') btn-primary @else btn-default @endif">Year</a>
            </div>
        </div>

        @if(count($activities) == 0)
            <div class="alert alert-info">No activities recorded yet</div>
        @else
            <table class="table table-condensed table-striped">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Character</th>
                        <th>Type</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($activities as $activity)
                        <tr>
                            <td>{{ $activity->activity_timestamp->format('Y-m-d H:i') }}</td>
                            <td>{{ $activity->character_id }}</td>
                            <td><span class="label label-primary">{{ $activity->activity_type }}</span></td>
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
