<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_no',
        'profile_picture',
        'status',
        'password',
        'real_password',
        'deleted_at',
        'otp_code',
        'otp_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    public function statusBadge()
    {
        return $this->status == 1 ? '<span class="badge badge-pill badge-status bg-success">Active</span>' : '<span class="badge badge-pill badge-status bg-danger">Inactive</span>';
    }

    public function scopeBrokers(Builder $query): Builder
    {
        return $query->whereHas('roles', fn (Builder $q) => $q->where('name', 'broker'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /** Active broker users for dropdowns, in database storage order. */
    public static function activeBrokersForDropdown(array $columns = ['*']): Collection
    {
        return static::query()
            ->brokers()
            ->active()
            ->orderBy('id')
            ->get($columns);
    }

    public static function isActiveBroker(int $userId): bool
    {
        return static::query()
            ->brokers()
            ->active()
            ->whereKey($userId)
            ->exists();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at'    => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
