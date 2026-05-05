<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
class TechnologyController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = Project::query()
            ->where('is_published', true)
            ->pluck('technologies');
        $set = [];
        foreach ($rows as $tech) {
            if (is_string($tech)) {
                $tech = json_decode($tech, true) ?? [];
            }
            if (is_array($tech)) {
                foreach ($tech as $t) {
                    if (is_string($t) && $t !== '') {
                        $set[$t] = true;
                    }
                }
            }
        }

        return response()->json(['data' => array_keys($set)]);
    }
}
