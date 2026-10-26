<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberCaseRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberCaseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);

        $conversations = Conversation::query()
            ->where('type', Conversation::TYPE_CASE)
            ->whereHas('members', fn ($query) => $query->where('members.id', $actor->id))
            ->with(['members', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get();

        return ConversationResource::collection($conversations)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreMemberCaseRequest $request): JsonResponse
    {
        $actor = $this->actor($request);
        if ($forbidden = $this->directorOrForbidden($actor)) {
            return $forbidden;
        }

        $conversation = Conversation::createCase(
            $actor,
            trim($request->validated('name')),
            $request->memberIds(),
        )->load(['members', 'latestMessage']);
        $conversation->loadRecentMessages();
        $conversation->markReadFor($actor);
        $conversation->load('members');

        return (new ConversationResource($conversation))
            ->additional([
                'success' => true,
                'message' => 'Đã tạo vụ việc.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $actor = $this->actor($request);
        if ($notCase = $this->mustBeCase($conversation)) {
            return $notCase;
        }
        if ($forbidden = $this->mustParticipate($conversation, $actor)) {
            return $forbidden;
        }

        $conversation->load(['members', 'latestMessage']);
        $conversation->loadRecentMessages();
        $conversation->markReadFor($actor);
        $conversation->load('members');

        return (new ConversationResource($conversation))
            ->additional(['success' => true])
            ->response();
    }

    public function update(StoreMemberCaseRequest $request, Conversation $conversation): JsonResponse
    {
        $actor = $this->actor($request);
        if ($notCase = $this->mustBeCase($conversation)) {
            return $notCase;
        }
        if ($forbidden = $this->directorOrForbidden($actor)) {
            return $forbidden;
        }
        if ($outside = $this->mustParticipate($conversation, $actor)) {
            return $outside;
        }

        $conversation->fill([
            'name' => trim($request->validated('name')),
        ])->save();
        $conversation->syncCaseMembers($actor, $request->memberIds());
        $conversation->load(['members', 'latestMessage']);
        $conversation->loadRecentMessages();
        $conversation->markReadFor($actor);
        $conversation->load('members');

        return (new ConversationResource($conversation))
            ->additional([
                'success' => true,
                'message' => 'Đã cập nhật vụ việc.',
            ])
            ->response();
    }

    private function actor(Request $request): Member
    {
        return $request->attributes->get('member');
    }

    private function directorOrForbidden(Member $actor): ?JsonResponse
    {
        if ($actor->role === 'director') {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Chỉ Giám đốc được tạo hoặc sửa vụ việc.',
        ], 403);
    }

    private function mustBeCase(Conversation $conversation): ?JsonResponse
    {
        if ($conversation->isCase()) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Không tìm thấy vụ việc.',
        ], 404);
    }

    private function mustParticipate(Conversation $conversation, Member $actor): ?JsonResponse
    {
        if ($conversation->hasMember($actor)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Bạn không thuộc vụ việc này.',
        ], 403);
    }
}
