<?php

declare(strict_types=1);

return [
    'invite' => [
        'ttl_hours' => (int) env('CADE_OS_TIMES_INVITE_TTL_HOURS', 72),
    ],

    'voting' => [
        'window_hours' => (int) env('CADE_OS_TIMES_VOTING_WINDOW_HOURS', 24),
        'reminder_hours_before_close' => 2,
    ],

    'draw' => [
        'star_tolerance' => (int) env('CADE_OS_TIMES_DRAW_STAR_TOLERANCE', 2),
        'position_max_ratio' => (float) env('CADE_OS_TIMES_POSITION_MAX_RATIO', 0.6),
    ],

    'presence' => [
        'reminder_hours_before_deadline' => 2,
    ],

    'pelada' => [
        'match_duration_hours' => (int) env('CADE_OS_TIMES_MATCH_DURATION_HOURS', 2),
    ],

    'push' => [
        'enabled' => (bool) env('PUSH_ENABLED', false),
    ],
];
