<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Enums\SessionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Presence\ConfirmPresenceRequest;
use App\Http\Resources\PeladaSessionResource;
use App\Http\Resources\PresenceConfirmationResource;
use App\Models\Pelada;
use App\Models\PeladaSession;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PresenceController extends Controller
{
    public function currentSession(Request $request, Pelada $pelada): JsonResponse
    {
        $this->authorize('view', $pelada);

        $session = $pelada->sessions()
            ->where('status', '!=', SessionStatus::FINISHED->value)
            ->orderBy('scheduled_at')
            ->with(['presences.user'])
            ->first();

        if (! $session) {
            return response()->json(['session' => null]);
        }

        return response()->json([
            'session' => new PeladaSessionResource($session),
        ]);
    }

    public function confirm(ConfirmPresenceRequest $request, PeladaSession $session): PresenceConfirmationResource
    {
        $pelada = $session->pelada()->firstOrFail();
        $this->authorize('view', $pelada);

        if ($session->status !== SessionStatus::OPEN) {
            throw new AuthorizationException(
                'Esta sessão não aceita mais confirmações.'
            );
        }

        if ($session->deadline_at->isPast()) {
            throw new AuthorizationException(
                'O prazo de confirmação já encerrou.'
            );
        }

        $presence = $session->presences()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'status' => $request->string('status'),
                'confirmed_at' => now(),
            ],
        );

        return new PresenceConfirmationResource($presence->load('user'));
    }
}
