<?php

return [
    'member-rewards' => [
        'permission' => 'view_own_activities',
        'name' => 'Member Rewards',
        'icon' => 'fas fa-chart-bar',
        'route_segment' => 'member-rewards',
        'entries' => [
            [
                'name' => 'My Activities',
                'icon' => 'fas fa-chart-line',
                'route' => 'member-rewards.dashboard',
                'permission' => 'view_own_activities',
            ],
        ],
    ],
    'member-rewards-director' => [
        'permission' => 'view_all_activities',
        'name' => 'Member Rewards (Director)',
        'icon' => 'fas fa-crown',
        'route_segment' => 'member-rewards-director',
        'entries' => [
            [
                'name' => 'Corporation Dashboard',
                'icon' => 'fas fa-chart-area',
                'route' => 'member-rewards.director.index',
                'permission' => 'view_all_activities',
            ],
            [
                'name' => 'League Tables',
                'icon' => 'fas fa-trophy',
                'route' => 'member-rewards.league-tables',
                'permission' => 'view_all_activities',
            ],
        ],
    ],
];
