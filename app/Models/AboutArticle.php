<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AboutArticle extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'show_title',
        'publication_order',
        'status',
        'content',
        'blocks',
    ];

    protected function casts(): array
    {
        return [
            'show_title' => 'boolean',
            'publication_order' => 'integer',
            'status' => 'boolean',
            'blocks' => 'array',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', true);
    }

    public function setExclusiveStatus(bool $on): void
    {
        DB::transaction(function () use ($on) {
            if ($on) {
                static::query()
                    ->whereKeyNot($this->getKey())
                    ->where('status', true)
                    ->update(['status' => false]);
            }

            $this->forceFill(['status' => $on])->save();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->where($field ?? $this->getRouteKeyName(), $value)
            ->orWhere('id', $value)
            ->first();
    }

    public static function uniqueSlug(string $title, ?string $requested = null, ?int $ignoreId = null): string
    {
        $base = Str::slug($requested ?: $title) ?: 'gioi-thieu';
        $slug = $base;
        $index = 2;

        while (
            static::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$index;
            $index++;
        }

        return $slug;
    }

    public static function nextOrder(): int
    {
        return (int) static::query()->max('publication_order') + 1;
    }
}
