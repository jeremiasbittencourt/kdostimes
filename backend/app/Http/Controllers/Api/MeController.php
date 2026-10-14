<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StoreDeviceTokenRequest;
use App\Http\Requests\Profile\UpdateMeRequest;
use App\Http\Requests\Profile\UpdateNotificationPreferencesRequest;
use App\Http\Resources\DeviceTokenResource;
use App\Http\Resources\NotificationPreferenceResource;
use App\Http\Resources\UserResource;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        $user = $request->user()->load(['positions', 'notificationPreferences']);

        return new UserResource($user);
    }

    public function update(UpdateMeRequest $request): UserResource
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            $fields = $request->only(['name', 'skill_rating', 'avatar_url']);
            if ($fields !== []) {
                $user->update($fields);
            }

            if ($request->has('positions')) {
                $user->positions()->delete();
                foreach ($request->input('positions', []) as $entry) {
                    $user->positions()->create([
                        'position' => $entry['position'],
                        'priority' => $entry['priority'],
                    ]);
                }
            }
        });

        return new UserResource($user->fresh(['positions', 'notificationPreferences']));
    }

    public function updateNotificationPreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($request, $user): void {
            foreach ($request->input('preferences', []) as $pref) {
                $user->notificationPreferences()->updateOrCreate(
                    ['event_type' => $pref['event_type']],
                    ['enabled' => (bool) $pref['enabled']],
                );
            }
        });

        return response()->json([
            'preferences' => NotificationPreferenceResource::collection(
                $user->notificationPreferences()->get()
            ),
        ]);
    }

    public function storeDeviceToken(StoreDeviceTokenRequest $request): JsonResponse
    {
        $user = $request->user();

        $device = DeviceToken::updateOrCreate(
            ['token' => $request->string('token')],
            [
                'user_id' => $user->id,
                'platform' => $request->string('platform'),
                'last_used_at' => now(),
            ],
        );

        return response()->json([
            'device_token' => new DeviceTokenResource($device),
        ], 201);
    }

    public function destroyDeviceToken(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        $deleted = $user->deviceTokens()->where('id', $id)->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Device token não encontrado.'], 404);
        }

        return response()->json(['message' => 'Device token removido.']);
    }
}
