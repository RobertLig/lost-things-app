<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class MapPointController extends Controller
{
    public function index(Request $request)
    {
        logger('API request', $request->all());

        $bbox = $request->query('bbox');

        if (!$bbox) {
            return response()->json([
                'points' => [],
                'bounds' => null,
            ]);
        }

        [$west, $south, $east, $north] = explode(',', $bbox);

        $items = Item::query()
            ->whereHas('location', function ($q) use ($south, $north, $west, $east) {
                $q->whereBetween('lat', [(float)$south, (float)$north])
                  ->whereBetween('lng', [(float)$west, (float)$east]);
            })

            // 🔎 SEARCH
            ->when($request->search, function ($query) use ($request) {
                $query->whereHas('translations', function ($q) use ($request) {
                    $q->where('locale', app()->getLocale())
                      ->where(function ($q2) use ($request) {
                          $q2->where('title', 'like', "%{$request->search}%")
                             ->orWhere('description', 'like', "%{$request->search}%");
                      });
                });
            })

            // 📅 DATE
            ->when($request->dateFrom, fn($q) =>
                $q->whereDate('lost_at', '>=', $request->dateFrom)
            )
            ->when($request->dateTo, fn($q) =>
                $q->whereDate('lost_at', '<=', $request->dateTo)
            )

            // 👤 USER
            ->when($request->myItems && auth()->check(), fn($q) =>
                $q->where('user_id', auth()->id())
            )

            ->with('location')
            ->get();

        // 📍 GROUP BY LOCATION
        $grouped = $items->groupBy('location_id');

        $points = $grouped->map(function ($items) {
            $loc = $items->first()->location;

            return [
                'id' => $loc->id,
                'lat' => (float) $loc->lat,
                'lng' => (float) $loc->lng,
                'count' => $items->count(),
            ];
        })->values();

        // 🔥 CALCULATE BOUNDS FROM POINTS (NOT ITEMS)
        $bounds = null;

        if ($points->isNotEmpty()) {
            $bounds = [
                'south' => (float) $items->min(fn($i) => $i->location->lat),
                'north' => (float) $items->max(fn($i) => $i->location->lat),
                'west'  => (float) $items->min(fn($i) => $i->location->lng),
                'east'  => (float) $items->max(fn($i) => $i->location->lng),
            ];
        }

        return response()->json([
            'points' => $points,
            'bounds' => $bounds,
        ]);
    }
}