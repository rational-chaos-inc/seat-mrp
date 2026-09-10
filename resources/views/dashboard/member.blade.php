@extends('web::layouts.grids.12')

@section('title', 'My Activities')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">My Activities</h4>
            </div>
            <div class="card-body">
                @if(isset($error))
                    <div class="alert alert-warning">{{ $error }}</div>
                @else
                    <!-- Time Window Selector -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label>Time Window:</label>
                            <div class="btn-group" role="group">
                                @foreach(['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'quarter' => 'Quarter', 'year' => 'Year'] as $window => $label)
                                    <a href="{{ route('member-rewards.dashboard', ['window' => $window]) }}"
                                       class="btn btn-sm {{ $timeWindow === $window ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label>Activity Type:</label>
                            <div class="btn-group" role="group">
                                <a href="{{ route('member-rewards.dashboard', ['window' => $timeWindow]) }}"
                                   class="btn btn-sm {{ !$selectedType ? 'btn-primary' : 'btn-outline-primary' }}">
                                    All
                                </a>
                                @foreach(config('member-rewards.activity_types', []) as $type)
                                    <a href="{{ route('member-rewards.dashboard', ['window' => $timeWindow, 'type' => $type]) }}"
                                       class="btn btn-sm {{ $selectedType === $type ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ ucfirst(str_replace('_', ' ', $type)) }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Overall Summary Cards -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted">Total Value</h6>
                                    <h3 class="card-text">{{ number_format($overall->total, 2) }} ISK</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted">Activities</h6>
                                    <h3 class="card-text">{{ $overall->count }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted">Average per Activity</h6>
                                    <h3 class="card-text">{{ number_format($overall->average, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title text-muted">Linked Characters</h6>
                                    <h3 class="card-text">{{ $overall->characterCount }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Breakdown by Type -->
                    @if(!empty($overall->byType))
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h5>Breakdown by Type</h5>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Activity Type</th>
                                            <th>Count</th>
                                            <th>Total Value</th>
                                            <th>Average</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($overall->byType as $type => $stats)
                                            <tr>
                                                <td><strong>{{ ucfirst(str_replace('_', ' ', $type)) }}</strong></td>
                                                <td>{{ $stats['count'] }}</td>
                                                <td>{{ number_format($stats['total'], 2) }}</td>
                                                <td>{{ number_format($stats['average'], 2) }}</td>
                                                <td>
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar" role="progressbar"
                                                             style="width: {{ $stats['percentage'] }}%"
                                                             aria-valuenow="{{ $stats['percentage'] }}"
                                                             aria-valuemin="0" aria-valuemax="100">
                                                            {{ $stats['percentage'] }}%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Breakdown by Character -->
                    @if(!empty($overall->byCharacter))
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <h5>Breakdown by Character</h5>
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Character</th>
                                            <th>Count</th>
                                            <th>Total Value</th>
                                            <th>Average</th>
                                            <th>Percentage</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($characters as $character)
                                            @if(isset($overall->byCharacter[$character->character_id]))
                                                <tr>
                                                    <td><strong>{{ $character->character_name }}</strong></td>
                                                    <td>{{ $overall->byCharacter[$character->character_id]['count'] }}</td>
                                                    <td>{{ number_format($overall->byCharacter[$character->character_id]['total'], 2) }}</td>
                                                    <td>{{ number_format($overall->byCharacter[$character->character_id]['average'], 2) }}</td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar" role="progressbar"
                                                                 style="width: {{ $overall->byCharacter[$character->character_id]['percentage'] }}%"
                                                                 aria-valuenow="{{ $overall->byCharacter[$character->character_id]['percentage'] }}"
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                {{ $overall->byCharacter[$character->character_id]['percentage'] }}%
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('member-rewards.character.detail', ['characterId' => $character->character_id, 'window' => $timeWindow]) }}"
                                                           class="btn btn-sm btn-info">
                                                            View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
