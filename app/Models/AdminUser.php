<?php

namespace App\Models;

use App\Enums\GuardNameEnum;
use App\Enums\SpatieMediaCollectionName;
use App\Notifications\AdminPasswordResetNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * @method static firstOrCreate(array $attributes, array $values = [])
 * @method static where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'and')
 * @method static find(mixed $id, array $columns = ['*'])
 */
class AdminUser extends Authenticatable implements HasMedia
{
    use HasFactory, Notifiable, HasRoles, HasApiTokens, SoftDeletes, InteractsWithMedia;

    protected $table = 'admin_users';

    protected $appends = ['profile_image'];

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'totp_secret',
        'totp_enabled_at',
        'totp_recovery_codes',
        'status',
        'remember_token',
        'email_verified_at',
        'mobile_verified_at',
        'legacy_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'name' => 'string',
            'email' => 'string',
            'mobile' => 'string',
            'password' => 'hashed',
            'totp_secret' => 'encrypted',
            'totp_enabled_at' => 'datetime',
            'totp_recovery_codes' => 'encrypted:array',
            'status' => 'boolean',
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'legacy_user_id' => 'integer',
        ];
    }

    public function isTotpEnabled(): bool
    {
        return !empty($this->totp_secret) && !empty($this->totp_enabled_at);
    }

    public function getDefaultGuardName(): string
    {
        return GuardNameEnum::ADMIN->value;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new AdminPasswordResetNotification($token));
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'admin_user_id');
    }

    public function seller(): ?Seller
    {
        return null;
    }

    public function getProfileImageAttribute(): ?string
    {
        return $this->getFirstMediaUrl(SpatieMediaCollectionName::PROFILE_IMAGE());
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(SpatieMediaCollectionName::PROFILE_IMAGE())->singleFile();
    }

    protected static function booted(): void
    {
        static::deleting(function (self $user) {
            $user->clearMediaCollection(SpatieMediaCollectionName::PROFILE_IMAGE());
        });
    }
}
