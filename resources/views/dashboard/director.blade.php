@extends('web::layouts.default')

@section('title', 'Corporation Activities')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Corporation Activities - {{ $timeWindow }} ({{ count($activities) }} records)</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <a href="?window=day" class="btn btn-sm @if($timeWindow === 'day') btn-primary @else btn-outline-primary @endif">Day</a>
                        <a href="?window=week" class="btn btn-sm @if($timeWindow === 'week') btn-primary @else btn-outline-primary @endif">Week</a>
                        <a href="?window=month" class="btn btn-sm @if($timeWindow === 'month') btn-primary @else btn-outline-primary @endif">Month</a>
                        <a href="?window=quarter" class="btn btn-sm @if($timeWindow === 'quarter') btn-primary @else btn-outline-primary @endif">Quarter</a>
                        <a href="?window=year" class="btn btn-sm @if($timeWindow === 'year') btn-primary @else btn-outline-primary @endif">Year</a>
                    </div>
                </div>

                @if(count($activities) == 0)
                    <div class="alert alert-info">No activities recorded yet</div>
                @else
                    <div style="overflow-x: auto;">
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
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
