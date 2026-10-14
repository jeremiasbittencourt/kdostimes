<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pelada;
use App\Models\User;
use App\Services\Ranking\RankingQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RankingController extends Controller
{
    private const ALLOWED_PERIODS = ['month', 'year', 'all', 'last10'];

    public function __construct(
        private readonly RankingQuery $query,
    ) {
    }

    public function index(Request $request, Pelada $pelada): JsonResponse
    {
        $this->authorize('view', $pelada);

        $period = (string) $request->query('period', 'all');
        if (! in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = 'all';
        }

        $ranking = $this->query->forPelada($pelada, $period);

        $userIds = array_map(static fn (array $row): int => $row['user_id'], $ranking);
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        $enriched = array_map(function (array $row) use ($users): array {
            $user = $users->get($row['user_id']);

            return [
                'user_id' => $row['user_id'],
                'name' => $user?->name,
                'avatar_url' => $user?->avatar_url,
                'total_points' => $row['total_points'],
                'wins_count' => $row['wins_count'],
                'per_category' => $row['per_category'],
            ];
        }, $ranking);

        return response()->json([
            'period' => $period,
            'ranking' => $enriched,
        ]);
    }

    public function show(Request $request, Pelada $pelada, int $userId): JsonResponse
    {
        $this->authorize('view', $pelada);

        $user = User::findOrFail($userId);
        $period = (string) $request->query('period', 'all');
        if (! in_array($period, self::ALLOWED_PERIODS, true)) {
            $period = 'all';
        }

        $ranking = $this->query->forPelada($pelada, $period);
        $entry = null;
        foreach ($ranking as $row) {
            if ($row['user_id'] === $user->id) {
                $entry = $row;
                break;
            }
        }

        return response()->json([
            'period' => $period,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'avatar_url' => $user->avatar_url,
            ],
            'entry' => $entry,
        ]);
    }
}
