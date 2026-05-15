<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class TechnologyController extends Controller
{
    public function index(): JsonResponse
    {
        $genres = config('portfolio.event_genres', []);

        return response()->json(['data' => array_values($genres)]);
    }
}
