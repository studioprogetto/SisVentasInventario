<?php

namespace App\Support;

use Illuminate\Database\Query\Builder as Query;

final class SearchTerms
{
    /** All terms must match, but may occur in different columns or in any order. */
    public static function matching(string $text, array $columns): \Closure
    {
        $terms = array_unique(preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY));
        return static function ($query) use ($terms, $columns) {
            foreach ($terms as $term) {
                $pattern = '%'.strtr($term, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $query->where(function ($group) use ($columns, $pattern) {
                    $builder = $group instanceof Query ? $group : $group->getQuery();
                    foreach ($columns as $column) {
                        $field = $builder->getGrammar()->wrap($column);
                        $group->orWhereRaw("$field LIKE ? ESCAPE '!'", [$pattern]);
                    }
                });
            }
        };
    }
}
