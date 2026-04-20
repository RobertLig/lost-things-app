<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;
use App\Filters\ItemFilters;

class MapPointController extends Controller
{
    public function index(Request $request)
    {
        $bbox = $request->query('bbox');

        if (!$bbox) {
            return response()->json([
                'points' => [],
                'bounds' => null,
            ]);
        }

        [$west, $south, $east, $north] =
            array_map('floatval', explode(',', $bbox));

        /*
        |--------------------------------------------------------------------------
        | Base filtered query
        |--------------------------------------------------------------------------
        */

        $baseQuery = Item::query();

        logger('API LOCALE', [
            'locale' => $request->locale
        ]);

        ItemFilters::apply($baseQuery, [
            'search' => $request->search,
            'dateFrom' => $request->dateFrom,
            'dateTo' => $request->dateTo,
            'myItems' => $request->myItems,
            'locale' => $request->locale,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Query 1 — viewport markers
        |--------------------------------------------------------------------------
        */

        $items = (clone $baseQuery)
            ->whereHas('location', function ($q) use (
                $south,
                $north,
                $west,
                $east
            ) {
                $q->whereBetween('lat', [$south, $north])
                  ->whereBetween('lng', [$west, $east]);
            })
            ->with('location')
            ->get();

        $points = $items
            ->groupBy('location_id')
            ->map(function ($items) {

                $loc = $items->first()->location;

                logger('MARKER LOCATION', [
                    'location_id' => $loc->id,
                    'item_ids' => $items->pluck('id')->all(),
                ]);

                return [
                    'id' => $loc->id,
                    'lat' => (float) $loc->lat,
                    'lng' => (float) $loc->lng,
                    'count' => $items->count(),
                ];
            })
            ->values();

        logger('POINTS', $points->toArray());

        /*
        |--------------------------------------------------------------------------
        | Query 2 — global bounds for auto-fit
        |--------------------------------------------------------------------------
        */

        $allMatchingItems = (clone $baseQuery)
            ->whereHas('location')
            ->with('location')
            ->get();

        logger('MAP IDS', [
            'ids' => $allMatchingItems->pluck('id')->sort()->values()->all(),
            'count' => $allMatchingItems->count(),
        ]);

        $bounds = null;

        if ($allMatchingItems->isNotEmpty()) {

            $bounds = [
                'south' => (float) $allMatchingItems->min(
                    fn($i) => $i->location->lat
                ),

                'north' => (float) $allMatchingItems->max(
                    fn($i) => $i->location->lat
                ),

                'west' => (float) $allMatchingItems->min(
                    fn($i) => $i->location->lng
                ),

                'east' => (float) $allMatchingItems->max(
                    fn($i) => $i->location->lng
                ),
            ];
        }

        return response()->json([
            'points' => $points,
            'bounds' => $bounds,
        ]);
    }
}