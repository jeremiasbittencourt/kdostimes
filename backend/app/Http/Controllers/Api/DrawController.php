<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DrawResource;
use App\Models\Draw;
use App\Models\PeladaSession;
use App\Services\Draw\DrawPublisher;
use App\Services\Draw\TeamDrawService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class DrawController extends Controller
{
    public function __construct(
        private readonly TeamDrawService $drawService,
        private readonly DrawPublisher $publisher,
    ) {
    }

    public function store(Request $request, PeladaSession $session): DrawResource|JsonResponse
    {
        $pelada = $session->pelada()->firstOrFail();
        $this->authorize('drawTeams', $pelada);

        try {
            $draw = $this->drawService->drawFor($session, $request->user());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $draw->load(['teams.players.user']);

        return new DrawResource($draw);
    }

    public function publish(Request $request, Draw $draw): DrawResource
    {
        $pelada = $draw->session->pelada()->firstOrFail();
        $this->authorize('drawTeams', $pelada);

        $published = $this->publisher->publish($draw);

        return new DrawResource($published);
    }

    public function showPublished(Request $request, PeladaSession $session): DrawResource|JsonResponse
    {
        $pelada = $session->pelada()->firstOrFail();
        $this->authorize('view', $pelada);

        $draw = $session->draws()
            ->where('published', true)
            ->with(['teams.players.user'])
            ->first();

        if ($draw === null) {
            return response()->json(['message' => 'Cadê Os Times? Ainda não saiu o sorteio desta sessão.'], 404);
        }

        return new DrawResource($draw);
    }
}
