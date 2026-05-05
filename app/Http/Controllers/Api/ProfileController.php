<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\AvatarUploadRequest;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();
        $user->fill($request->validated());
        $user->save();

        return UserResource::make($user->fresh());
    }

    public function updatePassword(UpdatePasswordRequest $request): UserResource
    {
        $user = $request->user();
        $user->update([
            'password' => $request->validated('password'),
        ]);

        return UserResource::make($user->fresh());
    }

    public function uploadAvatar(AvatarUploadRequest $request): UserResource
    {
        $user = $request->user();
        $disk = (string) config('filesystems.avatar_disk', 'public');
        $file = $request->file('avatar');
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = $file->storeAs(
            'avatars',
            (string) Str::uuid().'.'.$ext,
            $disk
        );
        if ($user->avatar_path) {
            Storage::disk($disk)->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => $path]);

        return UserResource::make($user->fresh());
    }
}
