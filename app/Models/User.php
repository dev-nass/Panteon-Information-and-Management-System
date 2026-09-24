<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'contact_number',
        'email',
        'email_verified_at',
        'password',
        'role',
        'terminated_at',
        'terminated_reason',
        'terminated_by',
        'terminated_notes',
        'reinstated_at',
        'reinstated_reason',
        'reinstated_by',
        'reinstated_notes',
        'active_session_id',
        'last_login_at',
        'last_login_ip',
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

    protected $appends = ['is_terminated', 'terminated', 'reinstated'];

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
            'terminated_at' => 'datetime',
            'reinstated_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Determine if the user has an active session that is still valid.
     */
    public function hasActiveSession(): bool
    {
        if ($this->active_session_id === null) {
            return false;
        }

        $lifetime = (int) config('session.lifetime', 120);
        $threshold = now()->subMinutes($lifetime)->getTimestamp();

        return DB::table(config('session.table', 'sessions'))
            ->where('id', $this->active_session_id)
            ->where('user_id', $this->id)
            ->where('last_activity', '>', $threshold)
            ->exists();
    }

    protected function getIsTerminatedAttribute(): bool
    {
        return $this->terminated_at !== null;
    }

    protected function getTerminatedAttribute(): ?array
    {
        if ($this->terminated_at === null) {
            return null;
        }

        $by = null;
        if ($this->relationLoaded('terminatedBy') && $this->terminatedBy) {
            $by = [
                'id' => $this->terminatedBy->id,
                'full_name' => trim("{$this->terminatedBy->first_name} {$this->terminatedBy->middle_name} {$this->terminatedBy->last_name}"),
                'email' => $this->terminatedBy->email,
                'first_name' => $this->terminatedBy->first_name,
                'middle_name' => $this->terminatedBy->middle_name,
                'last_name' => $this->terminatedBy->last_name,
            ];
        } elseif ($this->terminated_by) {
            $by = ['id' => $this->terminated_by];
        }

        return [
            'at' => $this->terminated_at?->toISOString(),
            'reason' => $this->terminated_reason,
            'notes' => $this->terminated_notes,
            'by' => $by,
        ];
    }

    protected function getReinstatedAttribute(): ?array
    {
        if ($this->reinstated_at === null) {
            return null;
        }

        $by = null;
        if ($this->relationLoaded('reinstatedBy') && $this->reinstatedBy) {
            $by = [
                'id' => $this->reinstatedBy->id,
                'full_name' => trim("{$this->reinstatedBy->first_name} {$this->reinstatedBy->middle_name} {$this->reinstatedBy->last_name}"),
                'email' => $this->reinstatedBy->email,
                'first_name' => $this->reinstatedBy->first_name,
                'middle_name' => $this->reinstatedBy->middle_name,
                'last_name' => $this->reinstatedBy->last_name,
            ];
        } elseif ($this->reinstated_by) {
            $by = ['id' => $this->reinstated_by];
        }

        return [
            'at' => $this->reinstated_at?->toISOString(),
            'reason' => $this->reinstated_reason,
            'notes' => $this->reinstated_notes,
            'by' => $by,
        ];
    }

    public function importLogs()
    {
        return $this->hasMany(ImportedExcelLog::class);
    }

    public function burialRecords(): HasMany
    {
        return $this->hasMany(BurialRecord::class);
    }

    public function terminatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'terminated_by');
    }

    public function reinstatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reinstated_by');
    }

    public function isTerminated(): bool
    {
        return $this->terminated_at !== null;
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('terminated_at');
    }

    /**
     * @param  Builder<self>  $query
     */
    public function scopeTerminated(Builder $query): Builder
    {
        return $query->whereNotNull('terminated_at');
    }
}
