<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class ItemFilters
{
    public static function apply(
        Builder $query,
        array $filters
    ): Builder {

        return $query

            ->when(
                $filters['search'] ?? null,
                function ($query, $search) use ($filters) {

                    $query->whereHas('translations', function ($q) use ($search, $filters) {

                        $q->where('locale', $filters['locale'])
                        ->where(function ($q2) use ($search) {

                            $q2->where('title', 'like', "%{$search}%")
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                );
                        });
                    });
                }
            )

            ->when(
                $filters['dateFrom'] ?? null,
                fn ($q, $value) =>
                    $q->whereDate('lost_at', '>=', $value)
            )

            ->when(
                $filters['dateTo'] ?? null,
                fn ($q, $value) =>
                    $q->whereDate('lost_at', '<=', $value)
            )

            ->when(
                ($filters['myItems'] ?? false) && auth()->check(),
                fn ($q) =>
                    $q->where('user_id', auth()->id())
            );
    }
}