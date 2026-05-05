<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Notifications\UserInvitedNotification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class UserAdminController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);
        $q = User::query()->orderBy('role')->orderBy('name');
        if ($request->query('role')) {
            $q->where('role', $request->query('role'));
        }

        return UserResource::collection($q->paginate(30));
    }

    public function store(StoreUserRequest $request): UserResource
    {
        $this->authorize('create', User::class);
        $d = $request->validated();
        $user = User::query()->create([
            'name' => $d['name'],
            'email' => $d['email'],
            'role' => UserRole::from($d['role']),
            'password' => null,
            'course' => $d['course'] ?? null,
            'group' => $d['group'] ?? null,
            'year_of_graduation' => $d['year_of_graduation'] ?? null,
            'is_active' => true,
        ]);
        $token = Password::broker()->getRepository()->create($user);
        $user->notify(new UserInvitedNotification($token));

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
            'year_of_graduation' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        if (isset($d['role'])) {
            $user->role = UserRole::from($d['role']);
        }
        $user->fill($d);
        $user->save();

        return new UserResource($user->fresh());
    }
}
