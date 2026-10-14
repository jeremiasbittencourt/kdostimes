<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DrawController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\PeladaController;
use App\Http\Controllers\Api\PresenceController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\VotingController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);
    Route::post('auth/forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('me', [MeController::class, 'show']);
    Route::put('me', [MeController::class, 'update']);
    Route::put('me/notification-preferences', [MeController::class, 'updateNotificationPreferences']);
    Route::post('me/device-tokens', [MeController::class, 'storeDeviceToken']);
    Route::delete('me/device-tokens/{id}', [MeController::class, 'destroyDeviceToken'])
        ->whereNumber('id');

    Route::post('peladas', [PeladaController::class, 'store']);
    Route::get('peladas', [PeladaController::class, 'index']);
    Route::post('peladas/join', [PeladaController::class, 'join']);
    Route::get('peladas/{pelada}', [PeladaController::class, 'show'])->whereNumber('pelada');
    Route::post('peladas/{pelada}/invites', [PeladaController::class, 'createInvite'])
        ->whereNumber('pelada');
    Route::put('peladas/{pelada}/members/{userId}', [PeladaController::class, 'updateMember'])
        ->whereNumber('pelada')->whereNumber('userId');
    Route::delete('peladas/{pelada}/members/{userId}', [PeladaController::class, 'removeMember'])
        ->whereNumber('pelada')->whereNumber('userId');
    Route::put('peladas/{pelada}/voting-settings', [PeladaController::class, 'updateVotingSettings'])
        ->whereNumber('pelada');

    Route::get('peladas/{pelada}/sessions/current', [PresenceController::class, 'currentSession'])
        ->whereNumber('pelada');
    Route::post('sessions/{session}/presence', [PresenceController::class, 'confirm'])
        ->whereNumber('session');

    Route::post('sessions/{session}/draw', [DrawController::class, 'store'])
        ->whereNumber('session');
    Route::get('sessions/{session}/draw', [DrawController::class, 'showPublished'])
        ->whereNumber('session');
    Route::post('draws/{draw}/publish', [DrawController::class, 'publish'])
        ->whereNumber('draw');

    Route::get('sessions/{session}/voting', [VotingController::class, 'show'])
        ->whereNumber('session');
    Route::post('voting-sessions/{votingSession}/votes', [VotingController::class, 'castVote'])
        ->whereNumber('votingSession');
    Route::get('voting-sessions/{votingSession}/results', [VotingController::class, 'results'])
        ->whereNumber('votingSession');

    Route::get('peladas/{pelada}/ranking', [RankingController::class, 'index'])
        ->whereNumber('pelada');
    Route::get('peladas/{pelada}/ranking/{userId}', [RankingController::class, 'show'])
        ->whereNumber('pelada')->whereNumber('userId');
});
