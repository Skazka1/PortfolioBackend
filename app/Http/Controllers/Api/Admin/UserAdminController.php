<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $q = User::query()
            ->orderBy('role')
            ->orderBy('name');
        if ($request->query('role')) {
            $q->where('role', $request->query('role'));
        }
        if ($request->query('course')) {
            $q->where('course', (string) $request->query('course'));
        }
        if ($request->query('group')) {
            $q->where('group', (string) $request->query('group'));
        }
        if ($request->filled('q')) {
            $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->query('q')).'%';
            $q->where(function ($builder) use ($term) {
                $builder
                    ->where('name', 'ilike', $term)
                    ->orWhere('email', 'ilike', $term);
            });
        }

        $perPage = min(max((int) $request->query('per_page', 30), 1), 50);

        return UserResource::collection(
            $q->paginate($perPage)->withQueryString()
        );
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $this->authorize('create', User::class);
        $d = $request->validated();
        $user = User::query()->create([
            'name' => $d['name'],
            'email' => $d['email'],
            'role' => UserRole::from($d['role']),
            'password' => $d['password'] ?? null,
            'course' => $d['course'] ?? null,
            'group' => $d['group'] ?? null,
            'is_active' => true,
        ]);
        if (($d['password'] ?? null) === null) {
            $token = Password::broker()->getRepository()->create($user);
            $user->notify(new UserInvitedNotification($token));
        }

        return new UserResource($user);
    }

    public function update(Request $request, User $user): UserResource
    {
        $this->authorize('updateAsAdmin', $user);
        $d = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['sometimes', Rule::in([UserRole::Admin->value, UserRole::Teacher->value, UserRole::Student->value])],
            'course' => ['nullable', 'string', 'max:32'],
            'group' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'string', 'min:8'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (isset($d['role'])) {
            $user->role = UserRole::from($d['role']);
        }
        $user->fill($d);
        $user->save();

        return new UserResource($user->fresh());
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->isPrimaryAdmin()) {
            return response()->json([
                'message' => __('portfolio.cannot_delete_primary_admin'),
            ], 422);
        }

        $this->authorize('deleteAsAdmin', $user);
        $user->delete();

        return response()->json(['ok' => true]);
    }
}
