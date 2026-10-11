<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
    ];

    protected $appends = [
        'subject_title',
        'subject_url',
        'subject_exists',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'properties' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getSubjectTitleAttribute(): string
    {
        return match (true) {
            str_ends_with($this->subject_type ?? '', 'BurialRecord') => 'Burial Record',
            str_ends_with($this->subject_type ?? '', 'User') => 'User',
            str_ends_with($this->subject_type ?? '', 'Phase') => 'Phase',
            str_ends_with($this->subject_type ?? '', 'Cluster') => 'Cluster',
            str_ends_with($this->subject_type ?? '', 'Lot') => 'Lot',
            str_ends_with($this->subject_type ?? '', 'ClerkInvitation') => 'Clerk Invitation',
            str_ends_with($this->subject_type ?? '', 'ImportedExcelLog') => 'Excel Import',
            default => $this->subject_type ? class_basename($this->subject_type) : 'System',
        };
    }

    public function getSubjectUrlAttribute(): ?string
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return null;
        }

        try {
            return match (true) {
                str_ends_with($this->subject_type, 'BurialRecord') => route('admin.burial_records.show', $this->subject_id),
                str_ends_with($this->subject_type, 'User') => route('admin.user_management.show', $this->subject_id),
                str_ends_with($this->subject_type, 'Lot') => route('admin.lot_management.show', $this->subject_id),
                str_ends_with($this->subject_type, 'Phase'),
                str_ends_with($this->subject_type, 'Cluster') => route('admin.lot_management.index'),
                str_ends_with($this->subject_type, 'ClerkInvitation') => route('admin.clerk_invitations.index'),
                str_ends_with($this->subject_type, 'ImportedExcelLog') => route('admin.import.index'),
                default => null,
            };
        } catch (\Throwable) {
            return null;
        }
    }

    public function getSubjectExistsAttribute(): bool
    {
        if (! $this->subject_type || ! $this->subject_id) {
            return false;
        }

        if ($this->relationLoaded('subject')) {
            return $this->subject !== null;
        }

        if (! class_exists($this->subject_type)) {
            return false;
        }

        return $this->subject_type::find($this->subject_id) !== null;
    }
}
