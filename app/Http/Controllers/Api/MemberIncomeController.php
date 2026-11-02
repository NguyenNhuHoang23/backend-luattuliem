<?php

namespace App\Http\Controllers\Api;

use App\Events\MemberChatMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreIncomeNoticeRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberIncomeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);

        $conversations = Conversation::listedFor($actor, Conversation::TYPE_INCOME);

        return ConversationResource::collection($conversations)
            ->additional(['success' => true])
            ->response();
    }

    public function store(StoreIncomeNoticeRequest $request): JsonResponse
    {
        $actor = $this->actor($request);
        if ($actor->role !== 'director') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ Giám đốc được gửi thông báo lương.',
            ], 403);
        }

        $content = trim((string) $request->validated('content'));
        $conversations = [];

        foreach ($request->memberIds() as $memberId) {
            $member = Member::query()->find($memberId);
            if (! $member) {
                continue;
            }

            $conversation = Conversation::findOrCreateIncome($actor, $member);
            $message = $conversation->messages()->create([
                'member_id' => $actor->id,
                'content' => $content,
            ]);
            $conversation->touch();
            $conversation->markReadFor($actor);
            $message->load(['member', 'attachments', 'conversation']);

            try {
                broadcast(new MemberChatMessageSent($message));
            } catch (\Throwable $exception) {
                report($exception);
            }

            $conversation->load(['members', 'latestMessage']);
            $conversation->loadRecentMessages();
            $conversation->load('members');
            $conversations[] = $conversation;
        }

        return ConversationResource::collection(collect($conversations))
            ->additional([
                'success' => true,
                'message' => 'Đã gửi thông báo lương.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    private function actor(Request $request): Member
    {
        return $request->attributes->get('member');
    }
}
