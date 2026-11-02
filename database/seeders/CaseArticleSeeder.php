<?php

namespace Database\Seeders;

use App\Models\CaseArticle;
use Illuminate\Database\Seeder;

class CaseArticleSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/case-articles.json');
        $items = json_decode((string) file_get_contents($path), true) ?: [];

        foreach ($items as $item) {
            $attributes = CaseArticle::attributesFromImported($item);
            $slug = $attributes['slug'];
            unset($attributes['slug']);

            CaseArticle::query()->updateOrCreate(
                ['slug' => $slug],
                $attributes,
            );
        }
    }
}
