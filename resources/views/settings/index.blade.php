@extends('web::layouts.grids.12')

@section('title', 'Member Rewards Settings')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Member Rewards Settings</h3>
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @foreach($corporations as $corp)
                    @php $setting = $settings[$corp->corporation_id] ?? null; @endphp

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h5 class="card-title mb-0">{{ $corp->name ?? 'Unknown Corporation' }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('member-rewards.settings.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="corporation_id" value="{{ $corp->corporation_id }}">

                                <!-- Visibility Controls -->
                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <h6 class="text-muted">Visible Metrics</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="show_login_status"
                                                {{ $setting && $setting->show_login_status ? 'checked' : '' }}
                                                id="login_{{ $corp->corporation_id }}">
                                            <label class="form-check-label" for="login_{{ $corp->corporation_id }}">
                                                Login Status
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="show_mining"
                                                {{ $setting && $setting->show_mining ? 'checked' : '' }}
                                                id="mining_{{ $corp->corporation_id }}">
                                            <label class="form-check-label" for="mining_{{ $corp->corporation_id }}">
                                                Mining Activity
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="show_tax_bounty"
                                                {{ $setting && $setting->show_tax_bounty ? 'checked' : '' }}
                                                id="tax_{{ $corp->corporation_id }}">
                                            <label class="form-check-label" for="tax_{{ $corp->corporation_id }}">
                                                Tax/Bounty Contributions
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="show_pvp"
                                                {{ $setting && $setting->show_pvp ? 'checked' : '' }}
                                                id="pvp_{{ $corp->corporation_id }}">
                                            <label class="form-check-label" for="pvp_{{ $corp->corporation_id }}">
                                                PvP Activity
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <h6 class="text-muted">Who Can See</h6>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="visibility_level"
                                                value="directors"
                                                {{ $setting && $setting->visibility_level === 'directors' ? 'checked' : '' }}
                                                id="vis_directors_{{ $corp->corporation_id }}">
                                            <label class="form-check-label" for="vis_directors_{{ $corp->corporation_id }}">
                                                Directors Only
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="visibility_level"
                                                value="members"
                                                {{ $setting && $setting->visibility_level === 'members' ? 'checked' : '' }}
                                                id="vis_members_{{ $corp->corporation_id }}">
                                            <label class="form-check-label" for="vis_members_{{ $corp->corporation_id }}">
                                                Members Only
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="visibility_level"
                                                value="both"
                                                {{ $setting && $setting->visibility_level === 'both' ? 'checked' : '' }}
                                                id="vis_both_{{ $corp->corporation_id }}">
                                            <label class="form-check-label" for="vis_both_{{ $corp->corporation_id }}">
                                                Everyone
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Weighting -->
                                <div class="row mb-4">
                                    <div class="col-md-12">
                                        <h6 class="text-muted">Activity Weighting (0-10)</h6>
                                        <p class="small text-secondary">Used to calculate member scores. Higher = more important.</p>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="mining_weight_{{ $corp->corporation_id }}" class="form-label">Mining Weight</label>
                                        <input type="number" step="0.1" min="0" max="10" class="form-control"
                                            name="mining_weight"
                                            value="{{ $setting ? $setting->mining_weight : 1.0 }}"
                                            id="mining_weight_{{ $corp->corporation_id }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="tax_weight_{{ $corp->corporation_id }}" class="form-label">Tax/Bounty Weight</label>
                                        <input type="number" step="0.1" min="0" max="10" class="form-control"
                                            name="tax_bounty_weight"
                                            value="{{ $setting ? $setting->tax_bounty_weight : 1.0 }}"
                                            id="tax_weight_{{ $corp->corporation_id }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="pvp_weight_{{ $corp->corporation_id }}" class="form-label">PvP Weight</label>
                                        <input type="number" step="0.1" min="0" max="10" class="form-control"
                                            name="pvp_weight"
                                            value="{{ $setting ? $setting->pvp_weight : 1.0 }}"
                                            id="pvp_weight_{{ $corp->corporation_id }}">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <button type="submit" class="btn btn-primary">Save Settings</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
