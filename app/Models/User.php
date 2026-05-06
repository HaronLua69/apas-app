<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable([
    'id_number',
    'username',
    'name',
    'first_name',
    'middle_name',
    'last_name',
    'name_suffix',
    'email',
    'role',
    'secondary_role',
    'password',
])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'secondary_role' => UserRole::class,
        ];
    }

    public function isAdministrator(): bool
    {
        return $this->ownsRole(UserRole::Administrator);
    }

    public function isAdviser(): bool
    {
        return $this->ownsRole(UserRole::Adviser);
    }

    public function isStudent(): bool
    {
        return $this->ownsRole(UserRole::Student);
    }

    public function ownsRole(UserRole|string $role): bool
    {
        $resolvedRole = $role instanceof UserRole ? $role : UserRole::from($role);

        return in_array($resolvedRole, $this->ownedRoles(), true);
    }

    /**
     * @return array<int, UserRole>
     */
    public function ownedRoles(): array
    {
        return collect([$this->role, $this->secondary_role])
            ->filter(fn (mixed $role): bool => $role instanceof UserRole)
            ->unique(fn (UserRole $role): string => $role->value)
            ->values()
            ->all();
    }

    public function activeRole(): UserRole
    {
        $sessionRole = session('active_role');

        if (is_string($sessionRole)) {
            $resolvedRole = UserRole::tryFrom($sessionRole);

            if ($resolvedRole !== null && $this->ownsRole($resolvedRole)) {
                return $resolvedRole;
            }
        }

        return $this->role;
    }

    public function hasActiveRole(UserRole|string $role): bool
    {
        $resolvedRole = $role instanceof UserRole ? $role : UserRole::from($role);

        return $this->activeRole() === $resolvedRole;
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function adviserProfile(): HasOne
    {
        return $this->hasOne(AdviserProfile::class);
    }

    public function recordedSubjectEnrollments(): HasMany
    {
        return $this->hasMany(StudentSubjectEnrollment::class, 'recorded_by_user_id');
    }

    public function fullName(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->name_suffix,
        ])->filter()->implode(' ');
    }

    public function adviserDisplayName(): string
    {
        $primaryName = collect([
            $this->first_name,
            $this->name_suffix,
        ])->filter()->implode(' ');
        $middleInitial = filled($this->middle_name)
            ? Str::upper(Str::substr(trim((string) $this->middle_name), 0, 1)).'.'
            : null;

        return collect([
            filled($this->last_name) ? $this->last_name.',' : null,
            $primaryName,
            $middleInitial,
        ])->filter()->implode(' ');
    }

    public function dashboardRouteName(): string
    {
        return match ($this->activeRole()) {
            UserRole::Administrator => 'admin.dashboard',
            UserRole::Adviser => 'adviser.dashboard',
            UserRole::Student => 'student.dashboard',
        };
    }

    public function roleLabel(): string
    {
        return match ($this->activeRole()) {
            UserRole::Administrator => 'Administrator',
            UserRole::Adviser => 'Program Adviser',
            UserRole::Student => 'Student',
        };
    }

    public static function uniqueUsernameFromEmail(string $email, ?int $ignoreUserId = null): string
    {
        $baseUsername = (string) Str::of(Str::before($email, '@'))
            ->trim()
            ->whenEmpty(fn ($value) => $value->append('student'));

        $candidate = $baseUsername;
        $suffix = 2;

        while (static::query()
            ->when($ignoreUserId !== null, fn ($query) => $query->whereKeyNot($ignoreUserId))
            ->where('username', $candidate)
            ->exists()) {
            $candidate = $baseUsername.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
