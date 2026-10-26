<?php

namespace App\Http\Controllers\Api;

use App\Events\MemberChatMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Http\Resources\ConversationResource;
use App\Models\ChatAttachment;
use App\Models\Conversation;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemberChatController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $actor = $this->actor($request);

        $conversations = Conversation::query()
            ->where('type', Conversation::TYPE_DIRECT)
            ->whereHas('members', fn ($query) => $query->where('members.id', $actor->id))
            ->with(['members', 'latestMessage'])
            ->orderByDesc('updated_at')
            ->get();

        return ConversationResource::collection($conversations)
            ->additional(['success' => true])
            ->response();
    }

    public function open(Request $request, Member $member): JsonResponse
    {
        $actor = $this->actor($request);

        if ($actor->id === $member->id) {
            return response()->json([
                'success' => false,
                'message' => 'Không thể nhắn tin với chính mình.',
            ], 422);
        }

        if (! $member->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Tài khoản thành viên đang tạm khóa.',
            ], 422);
        }

        $conversation = Conversation::findOrCreateDirect($actor, $member)
            ->load(['members', 'latestMessage']);
        $conversation->loadRecentMessages();
        $conversation->markReadFor($actor);
        $conversation->load('members');

        return (new ConversationResource($conversation))
            ->additional(['success' => true])
            ->response()
            ->setStatusCode(200);
    }

    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $actor = $this->actor($request);
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

    public function messages(Request $request, Conversation $conversation): JsonResponse
    {
        $actor = $this->actor($request);
        if ($forbidden = $this->mustParticipate($conversation, $actor)) {
            return $forbidden;
        }

        $before = $request->integer('before') ?: null;
        $limit = min(50, max(1, $request->integer('limit', Conversation::MESSAGE_PAGE_SIZE)));
        [$messages, $hasMore] = $conversation->pageMessages($limit, $before);

        return ChatMessageResource::collection($messages)
            ->additional([
                'success' => true,
                'hasMore' => $hasMore,
            ])
            ->response();
    }

    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $actor = $this->actor($request);
        if ($forbidden = $this->mustParticipate($conversation, $actor)) {
            return $forbidden;
        }

        $conversation->markReadFor($actor);

        return response()->json([
            'success' => true,
            'unread' => false,
        ]);
    }

    public function store(
        StoreChatMessageRequest $request,
        Conversation $conversation,
    ): JsonResponse {
        $actor = $this->actor($request);
        if ($forbidden = $this->mustParticipate($conversation, $actor)) {
            return $forbidden;
        }

        $uploads = $request->file('files', []);
        if ($uploads instanceof UploadedFile) {
            $uploads = [$uploads];
        }

        $message = $conversation->messages()->create([
            'member_id' => $actor->id,
            'content' => trim((string) $request->input('content', '')) ?: null,
        ]);

        foreach ($uploads as $file) {
            $message->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'path' => $file->store('chat/'.$conversation->id, 'public'),
                'mime' => $file->getClientMimeType(),
                'size' => $file->getSize() ?: 0,
            ]);
        }

        $conversation->touch();
        $conversation->markReadFor($actor);
        $message->load(['member', 'attachments', 'conversation']);

        try {
            broadcast(new MemberChatMessageSent($message));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return (new ChatMessageResource($message))
            ->additional([
                'success' => true,
                'message' => 'Đã gửi tin nhắn.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    public function download(Request $request, ChatAttachment $attachment): StreamedResponse|JsonResponse
    {
        $actor = $this->actor($request);
        $attachment->loadMissing('message.conversation.members');
        $conversation = $attachment->message?->conversation;

        if (! $conversation || $this->mustParticipate($conversation, $actor)) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền tải tệp này.',
            ], 403);
        }

        $path = $attachment->path;
        abort_unless(
            $path && str_starts_with($path, 'chat/') && Storage::disk('public')->exists($path),
            404,
            'Không tìm thấy tệp đính kèm.',
        );

        $filename = str_replace(['"', '\\', "\r", "\n"], '', $attachment->original_name) ?: 'tep-dinh-kem';

        return Storage::disk('public')->download($path, $filename);
    }

    private function actor(Request $request): Member
    {
        return $request->attributes->get('member');
    }

    private function mustParticipate(Conversation $conversation, Member $actor): ?JsonResponse
    {
        if ($conversation->hasMember($actor)) {
            return null;
        }

        return response()->json([
            'success' => false,
            'message' => 'Bạn không thuộc cuộc trao đổi này.',
        ], 403);
    }
}
