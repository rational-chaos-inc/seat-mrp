@extends('web::layouts.grids.12')

@section('title', 'Corporation Activity Dashboard')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Corporation Activity Dashboard</h4>
                @if(isset($corporation))
                    <span class="badge badge-info">{{ $corporation->corporation_name ?? 'Unknown Corporation' }}</span>
                @endif
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
                                    <a href="{{ route('member-rewards.director.index', ['window' => $window]) }}"
                                       class="btn btn-sm {{ $timeWindow === $window ? 'btn-primary' : 'btn-outline-primary' }}">
                                        {{ $label }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('member-rewards.league-tables') }}" class="btn btn-primary float-right">
                                View League Tables →
                            </a>
                        </div>
                    </div>

                    <!-- Corporation Summary Cards -->
                    @if(isset($summary))
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Total Value</h6>
                                        <h3 class="card-text">{{ number_format($summary['total_value'], 2) }} ISK</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Total Activities</h6>
                                        <h3 class="card-text">{{ $summary['total_activities'] }}</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Active Members</h6>
                                        <h3 class="card-text">{{ $summary['active_members'] }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Member Aggregations Table -->
                    <div class="row">
                        <div class="col-md-12">
                            <h5>Member Activity Summary</h5>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Member</th>
                                            <th>Total Value</th>
                                            <th>Activities</th>
                                            <th>Average</th>
                                            <th>Characters</th>
                                            <th>Per Character</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($memberAggregations as $userId => $agg)
                                            @php
                                                $memberModel = config('auth.providers.users.model');
                                                $member = $memberModel::find($userId);
                                            @endphp
                                            @if($member)
                                                <tr>
                                                    <td><strong>{{ $member->name ?? $member->email }}</strong></td>
                                                    <td>{{ number_format($agg->total, 2) }}</td>
                                                    <td>{{ $agg->count }}</td>
                                                    <td>{{ number_format($agg->average, 2) }}</td>
                                                    <td>{{ $agg->characterCount }}</td>
                                                    <td>{{ number_format($agg->averagePerCharacter, 2) }}</td>
                                                    <td>
                                                        <a href="{{ route('member-rewards.member.detail', ['userId' => $userId, 'window' => $timeWindow]) }}"
                                                           class="btn btn-sm btn-primary">
                                                            View Details
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-muted">
                                                    No member activities found
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
