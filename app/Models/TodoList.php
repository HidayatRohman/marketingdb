<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TodoList extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'priority',
        'status',
        'due_date',
        'due_time',
        'user_id',
        'assigned_to',
        'tags',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'due_time' => 'datetime:H:i',
        'tags' => 'array',
    ];

    /**
     * Get the user who created this todo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user assigned to this todo
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for filtering by date
     */
    public function scopeForDate($query, $date)
    {
        return $query->whereDate('due_date', $date);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by priority
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Check if todo is overdue
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'completed') {
            return false;
        }

        $dueDateTime = $this->due_date;
        if ($this->due_time) {
            $dueDateTime = $this->due_date->format('Y-m-d') . ' ' . $this->due_time;
            $dueDateTime = \Carbon\Carbon::parse($dueDateTime);
        }

        return $dueDateTime->isPast();
    }
}
