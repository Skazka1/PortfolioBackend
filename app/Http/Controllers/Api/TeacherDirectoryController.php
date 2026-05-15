<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherBriefResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeacherDirectoryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        if ($request->user() === null) {
            abort(401);
        }

        $q = User::query()
            ->where('role', UserRole::Teacher)
            ->where('is_active', true)
            ->when($request->filled('q'), function ($builder) use ($request) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->query('q')).'%';
                $builder->where('name', 'ilike', $term);
            })
            ->orderBy('name');

        return TeacherBriefResource::collection($q->paginate(min((int) $request->query('per_page', 100), 500)));
    }
}
