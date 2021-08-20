<?php

namespace App\Console\Crons;

use App\Product;
use App\ProductTag;
use Illuminate\Support\Facades\Log;

class ProductTagsCron
{
    public function __invoke()
    {
        Log::debug('ProductTagsCron :: start :: product tags update');
        $pTags = Product::whereNotNull('tags')->select('tags', 'category_id')->get();

        $newTags = [];

        foreach ($pTags as $pTag) {
            $tags = explode(',', $pTag->tags);
            $category_id = $pTag->category_id;

            foreach ($tags as $tag) {
                $tag = trim($tag);
                if (empty($tag)) continue;

                if (!$this->tagExists($tag, $category_id, $newTags)) {
                    $newTags[] = ['tag' => $tag, 'category_id' => $category_id];
                }
            }
        }

        ProductTag::truncate();
        ProductTag::insert($newTags);

        Log::debug('ProductTagsCron :: end :: product tags update');
    }

    private function tagExists($tag, $category_id, $tags)
    {
        foreach ($tags as $t) {
            if (strtolower($t['tag']) == strtolower($tag) && $t['category_id'] == $category_id) {
                return true;
            }
        }

        return false;
    }
}
