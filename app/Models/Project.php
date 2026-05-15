<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\ProjectFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string|null $github_url
 * @property string|null $preview_image_path
 * @property array<int, string>|null $gallery_paths
 * @property array<int, string> $technologies жанры мероприятия
 * @property bool $is_published
 * @property int|null $supervisor_user_id
 * @property int|null $created_by_user_id
 * @property int|null $campus_event_id
 * @property int|null $likes_count
 * @property bool|null $liked_by_me
 * @property string|null $preview_image_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Project extends Model
{
    /** @use HasFactory<ProjectFactory> */
    use HasFactory;

    protected $fillable = [
        'title', 'description', 'github_url', 'preview_image_path', 'gallery_paths', 'technologies', 'is_published', 'supervisor_user_id', 'created_by_user_id', 'campus_event_id',
    ];

    public static function projectMediaDisk(): string
    {
        return config('filesystems.project_media_disk', 'public');
    }

    public function getPreviewImageUrlAttribute(): ?string
    {
        if (! $this->preview_image_path) {
            return null;
        }

        return Storage::disk(static::projectMediaDisk())->url($this->preview_image_path);
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'bool',
            'technologies' => 'array',
            'gallery_paths' => 'array',
        ];
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_user', 'project_id', 'user_id');
    }

    /** Пользователь, создавший карточку проекта. */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /** Руководитель проекта — преподаватель из списка активных пользователей. */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_user_id');
    }

    /** Связанное кампусное событие (обычно уже прошедшее). */
    public function campusEvent(): BelongsTo
    {
        return $this->belongsTo(CampusEvent::class, 'campus_event_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function isAuthoredBy(User $user): bool
    {
        return $this->isParticipantOf($user);
    }

    public function isParticipantOf(User $user): bool
    {
        return $this->students()->whereKey($user->id)->exists();
    }

    /**
     * @param  Builder<Project>  $query
     * @return Builder<Project>
     */
    public function scopeVisibleFor(Builder $query, ?User $viewer): void
    {
        if (! $viewer) {
            $query->where('is_published', true);

            return;
        }
        if ($viewer->isTeacher() || $viewer->isAdmin()) {
            return;
        }
        if ($viewer->isStudent()) {
            $query->where(function (Builder $q) use ($viewer) {
                $q->where('is_published', true)
                    ->orWhereHas('students', function (Builder $s) use ($viewer) {
                        $s->where('users.id', $viewer->id);
                    });
            });
        }
    }

    /**
     * @param  array<int, int>  $userIds
     */
    public function syncStudents(array $userIds, bool $isAdmin, User $editor): void
    {
        $allStudents = User::query()->where('role', UserRole::Student)
            ->whereIn('id', $userIds)
            ->pluck('id')
            ->map(fn (int $id) => (int) $id)
            ->all();

        if (count($allStudents) !== count($userIds)) {
            throw ValidationException::withMessages([
                'collaborator_ids' => 'Каждый выбранный участник должен существовать и иметь роль студента.',
            ]);
        }

        if (! $isAdmin) {
            if (! in_array($editor->id, $allStudents, true)) {
                $allStudents[] = $editor->id;
            }
        }

        $this->students()->sync(array_unique($allStudents));
    }
}
