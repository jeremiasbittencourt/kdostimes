<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Domain\Enums\NotificationEvent;
use App\Domain\Notification\PushMessage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pelada\CreatePeladaRequest;
use App\Http\Requests\Pelada\JoinPeladaRequest;
use App\Http\Requests\Pelada\UpdateMemberRequest;
use App\Http\Requests\Pelada\UpdateVotingSettingsRequest;
use App\Http\Resources\PeladaInviteResource;
use App\Http\Resources\PeladaMemberResource;
use App\Http\Resources\PeladaResource;
use App\Models\Pelada;
use App\Models\User;
use App\Models\VotingCategory;
use App\Services\Notification\NotificationCoordinator;
use App\Services\Pelada\PeladaCreator;
use App\Services\Pelada\PeladaJoiner;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class PeladaController extends Controller
{
    public function __construct(
        private readonly PeladaCreator $creator,
        private readonly PeladaJoiner $joiner,
        private readonly NotificationCoordinator $notifications,
    ) {
    }

    public function store(CreatePeladaRequest $request): PeladaResource
    {
        $pelada = $this->creator->create($request->user(), $request->validated());

        return new PeladaResource($pelada->load(['admin', 'members.user']));
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $peladas = $request->user()
            ->peladas()
            ->with('admin')
            ->orderBy('name')
            ->get();

        return PeladaResource::collection($peladas);
    }

    public function show(Request $request, Pelada $pelada): PeladaResource
    {
        $this->authorize('view', $pelada);

        return new PeladaResource($pelada->load(['admin', 'members.user']));
    }

    public function join(JoinPeladaRequest $request): PeladaResource
    {
        $user = $request->user();

        if ($request->filled('invite_token')) {
            $member = $this->joiner->joinByInviteToken($user, (string) $request->input('invite_token'));
        } else {
            $member = $this->joiner->joinByShortCode($user, (string) $request->input('short_code'));
        }

        return new PeladaResource(
            $member->pelada->load(['admin', 'members.user'])
        );
    }

    public function createInvite(Request $request, Pelada $pelada): PeladaInviteResource
    {
        $this->authorize('manageMembers', $pelada);

        $invite = $this->joiner->createInvite($request->user(), $pelada);

        return new PeladaInviteResource($invite);
    }

    public function updateMember(UpdateMemberRequest $request, Pelada $pelada, int $userId): PeladaMemberResource
    {
        $this->authorize('manageMembers', $pelada);

        $member = $pelada->members()->where('user_id', $userId)->firstOrFail();

        $data = $request->validated();
        if (array_key_exists('is_fixed_goalkeeper', $data) && ! $pelada->has_fixed_goalkeepers) {
            throw new AuthorizationException(
                'Esta pelada não usa goleiros fixos.'
            );
        }

        $member->fill($data)->save();

        return new PeladaMemberResource($member->load('user'));
    }

    public function removeMember(Request $request, Pelada $pelada, int $userId): JsonResponse
    {
        $target = User::findOrFail($userId);

        $this->authorize('removeMember', [$pelada, $target]);

        $pelada->members()->where('user_id', $userId)->delete();

        $this->notifications->send($target, new PushMessage(
            event: NotificationEvent::MEMBER_REMOVED,
            title: 'Você saiu da pelada',
            body: sprintf('Você não faz mais parte da pelada %s.', $pelada->name),
            data: ['pelada_id' => $pelada->id],
        ));

        return response()->json(['message' => 'Membro removido.']);
    }

    public function updateVotingSettings(UpdateVotingSettingsRequest $request, Pelada $pelada): JsonResponse
    {
        $this->authorize('configureVoting', $pelada);

        $byCode = VotingCategory::whereIn('code', array_column($request->input('settings', []), 'code'))
            ->pluck('id', 'code');

        DB::transaction(function () use ($request, $pelada, $byCode): void {
            foreach ($request->input('settings', []) as $entry) {
                $categoryId = $byCode[$entry['code']] ?? null;
                if ($categoryId === null) {
                    continue;
                }

                $pelada->votingSettings()->updateOrCreate(
                    ['voting_category_id' => $categoryId],
                    ['enabled' => (bool) $entry['enabled']],
                );
            }
        });

        return response()->json(['message' => 'Configuração de votação atualizada.']);
    }
}
