<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item;

class MapPointController extends Controller
{
    /**
     * Return map points inside the given bounding box
     */
    public function index(Request $request)
    {
        logger('API request', $request->all());

        $bbox = $request->query('bbox');

        if (!$bbox) {
            return response()->json([]);
        }

        [$west, $south, $east, $north] = explode(',', $bbox);

        $items = Item::query()
            ->whereHas('location', function ($q) use ($south, $north, $west, $east) {
                $q->whereBetween('lat', [(float)$south, (float)$north])
                ->whereBetween('lng', [(float)$west, (float)$east]);
            })
            ->when($request->search, function ($query) use ($request) {
                $query->whereHas('translations', function ($q) use ($request) {
                    $q->where('locale', app()->getLocale())
                    ->where(function ($q2) use ($request) {
                        $q2->where('title', 'like', "%{$request->search}%")
                            ->orWhere('description', 'like', "%{$request->search}%");
                    });
                });
            })
            ->when($request->dateFrom, fn($q) =>
                $q->whereDate('lost_at', '>=', $request->dateFrom)
            )
            ->when($request->dateTo, fn($q) =>
                $q->whereDate('lost_at', '<=', $request->dateTo)
            )
            ->when($request->myItems && auth()->check(), fn($q) =>
                $q->where('user_id', auth()->id())
            )
            ->with('location')
            ->get();

        // group by location
        $points = $items->groupBy('location_id')->map(function ($items) {
            $loc = $items->first()->location;

            return [
                'id' => $loc->id,
                'lat' => $loc->lat,
                'lng' => $loc->lng,
                'count' => $items->count(),
            ];
        })->values();

        return response()->json($points);
    }
}