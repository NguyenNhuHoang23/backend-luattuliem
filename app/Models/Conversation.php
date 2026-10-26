<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Conversation extends Model
{
    public const MESSAGE_PAGE_SIZE = 10;

    public const TYPE_DIRECT = 'direct';

    public const TYPE_CASE = 'case';

    public bool $hasMoreMessages = false;

    protected $fillable = [
        'uuid',
        'type',
        'name',
        'direct_key',
    ];

    protected static function booted(): void
    {
        static::creating(function (Conversation $conversation): void {
            if (! $conversation->uuid) {
                $conversation->uuid = (string) Str::uuid();
            }
        });
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'conversation_members')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function hasMember(Member $member): bool
    {
        return $this->members()->where('members.id', $member->id)->exists();
    }

    public static function directKey(int $first, int $second): string
    {
        [$low, $high] = $first < $second ? [$first, $second] : [$second, $first];

        return $low.':'.$high;
    }

    public static function findOrCreateDirect(Member $first, Member $second): self
    {
        $key = static::directKey((int) $first->id, (int) $second->id);

        $conversation = static::query()->where('direct_key', $key)->first();
        if ($conversation) {
            return $conversation;
        }

        $conversation = static::query()->create([
            'type' => static::TYPE_DIRECT,
            'direct_key' => $key,
        ]);
        $conversation->members()->attach([$first->id, $second->id]);

        return $conversation->load('members');
    }

    /**
     * @param  array<int, int|string>  $memberIds
     */
    public static function createCase(Member $director, string $name, array $memberIds): self
    {
        $conversation = static::query()->create([
            'type' => static::TYPE_CASE,
            'name' => $name,
        ]);
        $conversation->syncCaseMembers($director, $memberIds);

        return $conversation->load('members');
    }

    /**
     * @param  array<int, int|string>  $memberIds
     */
    public function syncCaseMembers(Member $director, array $memberIds): void
    {
        $ids = collect($memberIds)
            ->map(fn ($id) => (int) $id)
            ->push((int) $director->id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->members()->sync($ids);
        $this->unsetRelation('members');
    }

    public function isCase(): bool
    {
        return $this->type === static::TYPE_CASE;
    }

    public function peerFor(Member $member): ?Member
    {
        $this->loadMissing('members');

        return $this->members->firstWhere('id', '!=', $member->id);
    }

    public function markReadFor(Member $member): void
    {
        $this->members()->updateExistingPivot($member->id, [
            'last_read_at' => now(),
        ]);
        $this->unsetRelation('members');
    }

    public function isUnreadFor(Member $member): bool
    {
        $latest = $this->relationLoaded('latestMessage')
            ? $this->latestMessage
            : $this->latestMessage()->first();

        if (! $latest || (int) $latest->member_id === (int) $member->id) {
            return false;
        }

        $this->loadMissing('members');
        $lastRead = $this->members->firstWhere('id', $member->id)?->pivot?->last_read_at;

        if (! $lastRead) {
            return true;
        }

        return $latest->created_at?->gt(Carbon::parse($lastRead)) ?? false;
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return array{0: \Illuminate\Support\Collection<int, ChatMessage>, 1: bool}
     */
    public function pageMessages(int $limit = self::MESSAGE_PAGE_SIZE, ?int $beforeId = null): array
    {
        $query = $this->messages()
            ->with(['member', 'attachments'])
            ->reorder()
            ->orderByDesc('id');

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        $rows = $query->limit($limit + 1)->get();
        $hasMore = $rows->count() > $limit;
        $messages = $rows->take($limit)->reverse()->values();

        return [$messages, $hasMore];
    }

    public function loadRecentMessages(int $limit = self::MESSAGE_PAGE_SIZE): self
    {
        [$messages, $hasMore] = $this->pageMessages($limit);
        $this->hasMoreMessages = $hasMore;
        $this->setRelation('messages', $messages);

        return $this;
    }
}
