<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;

class MapPointController extends Controller
{
    /**
     * Return map points inside the given bounding box
     */
    public function index(Request $request)
    {
        $bbox = $request->query('bbox');

        if (!$bbox) {
            return response()->json([]);
        }

        [$west, $south, $east, $north] = explode(',', $bbox);

        $locations = Location::query()
            ->whereBetween('lat', [(float)$south, (float)$north])
            ->whereBetween('lng', [(float)$west, (float)$east])
            ->withCount('items')
            ->limit(500) // safety cap
            ->get();

        // map the response to exactly what frontend expects
        $points = $locations->map(function ($loc) {
            return [
                'id' => $loc->id,
                'lat' => $loc->lat,
                'lng' => $loc->lng,
                'count' => $loc->items_count,
            ];
        });

        return response()->json($points);
    }
}