<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HasRolesAndPermissions;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Contracts\Auth\CanResetPassword;
use App\Notifications\PasswordResetNotification;

class User extends Authenticatable implements CanResetPassword
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, HasRolesAndPermissions, \Illuminate\Auth\Passwords\CanResetPassword;

    protected $fillable = [
        'email',
        'phone',
        'national_id',
        'password',
        'first_name',
        'last_name',
        'domain',
        'locale_pref',
        'mfa_enabled',
        'mfa_secret',
        'status',
        'is_system_account',
        'failed_login_attempts',
        'locked_until',
        'password_changed_at',
        'photo_url',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mfa_secret',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'mfa_enabled' => 'boolean',
            'is_system_account' => 'boolean',
            'locked_until' => 'datetime',
            'password_changed_at' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    // ── Scopes ───────────────────────────────────────────────────
    public function scopeVisibleToAdmin($query)
    {
        return $query->where('is_system_account', false);
    }

    public function scopeSystemAccounts($query)
    {
        return $query->where('is_system_account', true);
    }

    // ── Relationships ────────────────────────────────────────────

    public function systemUser(): HasOne
    {
        return $this->hasOne(SystemUser::class);
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function associateAgent(): HasOne
    {
        return $this->hasOne(AssociateAgent::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SystemTransaction::class, 'user_id')->orderByDesc('id');
    }


    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
            ->withPivot('assigned_by')
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions', 'user_id', 'permission_id')
            ->withPivot(['granted', 'start_date', 'end_date'])
            ->withTimestamps();
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }


    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function membership(): HasOne
    {
        return $this->hasOne(Membership::class);
    }

    public function activeShift(): HasOne
    {
        return $this->hasOne(Shift::class)->ofMany(['start_time' => 'max'], fn ($q) => $q->where('status', 'active'));
    }

    // ── Accessors ────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}") ?: ($this->email ?? '');
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->domain === 'system_user';
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isLocked(): bool
    {
        return $this->status === 'locked'
            || ($this->locked_until && $this->locked_until->isFuture());
    }

    public function incrementFailedLogins(): void
    {
        $this->increment('failed_login_attempts');

        if ($this->failed_login_attempts >= 5) {
            $this->update([
                'status' => 'locked',
                'locked_until' => now()->addMinutes(30),
            ]);
        }
    }

    public function resetFailedLogins(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'status' => 'active',
        ]);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class);
    }

    public function shiftOvertimeRequests()
    {
        return $this->hasMany(ShiftOvertimeRequest::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new PasswordResetNotification($token));
    }
}
