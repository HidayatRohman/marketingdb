<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\HasRoleAccess;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoleAccess;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
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
        ];
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is marketing
     */
    public function isMarketing(): bool
    {
        return $this->hasRole('marketing');
    }

    /**
     * Check if user is customer service (CS)
     */
    public function isCs(): bool
    {
        return $this->hasRole('cs');
    }

    /**
     * Check if user is advertiser
     */
    public function isAdvertiser(): bool
    {
        return $this->hasRole('advertiser');
    }

    /**
     * Get the mitras for the user (marketing).
     */
    public function mitras()
    {
        return $this->hasMany(Mitra::class);
    }

    /**
     * Get the todo lists created by the user.
     */
    public function todoLists()
    {
        return $this->hasMany(TodoList::class, 'user_id');
    }

    /**
     * Get the todo lists assigned to the user.
     */
    public function assignedTodoLists()
    {
        return $this->hasMany(TodoList::class, 'assigned_to');
    }

    /**
     * Get role label
     */
    public function getRoleLabelAttribute(): string
    {
        return match($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Admin',
            'marketing' => 'Marketing',
            'advertiser' => 'Advertiser',
            'cs' => 'CS',
            default => ucfirst($this->role),
        };
    }
}
