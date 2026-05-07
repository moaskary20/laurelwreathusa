<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasDefaultTenant;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, HasDefaultTenant, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'name_ar',
        'name_en',
        'username',
        'email',
        'password',
        'phone',
        'office_id',
        'company_id',
        'is_main_user',
        'is_super_user',
        'subscription_validity',
        'permissions',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_main_user' => 'boolean',
            'is_super_user' => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isAdminTenantSwitcher()) {
            return true;
        }

        return (int) ($this->company_id ?? 0) > 0;
    }

    public function isAdminTenantSwitcher(): bool
    {
        return (bool) ($this->is_super_user || $this->is_main_user);
    }

    public function getTenants(Panel $panel): array|Collection
    {
        if ($this->isAdminTenantSwitcher()) {
            return Company::query()->orderBy('trade_name')->get();
        }

        $companyId = (int) ($this->company_id ?? 0);
        if ($companyId <= 0) {
            return collect();
        }

        return Company::query()->whereKey($companyId)->get();
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant instanceof Company) {
            return false;
        }

        if ($this->isAdminTenantSwitcher()) {
            return true;
        }

        return (int) ($this->company_id ?? 0) === (int) $tenant->getKey();
    }

    public function getDefaultTenant(Panel $panel): ?Model
    {
        if (! $this->isAdminTenantSwitcher()) {
            $companyId = (int) ($this->company_id ?? 0);
            if ($companyId > 0) {
                return Company::query()->whereKey($companyId)->first();
            }
        }

        return Company::query()->orderBy('id')->first();
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
