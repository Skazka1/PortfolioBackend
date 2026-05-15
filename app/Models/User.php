<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Random\RandomException;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property UserRole $role
 * @property string|null $course
 * @property string|null $group
 * @property string|null $avatar_path
 * @property string|null $bio
 * @property bool $is_active
 * @property Carbon|null $email_verified_at
 * @property string|null $avatar_url
 * @property-read Collection<int, Project> $projects
 */
#[Fillable(['name', 'email', 'password', 'role', 'course', 'group', 'avatar_path', 'bio', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'bool',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::Teacher;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function canLogin(): bool
    {
        return $this->is_active
            && $this->password !== null
            && $this->password !== '';
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_user', 'user_id', 'project_id');
    }

    public function createdEvents(): HasMany
    {
        return $this->hasMany(CampusEvent::class, 'created_by_user_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if (! $this->avatar_path) {
            return null;
        }

        return Storage::disk((string) config('filesystems.avatar_disk', 'public'))->url($this->avatar_path);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeOnlyStudents(Builder $query): Builder
    {
        return $query->where('role', UserRole::Student);
    }

    public function isParticipantOf(Project $project): bool
    {
        return $this->projects()->whereKey($project->id)->exists();
    }

    /**
     * @throws RandomException
     */
    public static function generateDefaultPasswordString(): string
    {
        return bin2hex(random_bytes(8));
    }
}
