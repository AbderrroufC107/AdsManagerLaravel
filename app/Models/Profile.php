<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Profile extends Model {
    use HasUuids;

    protected $fillable = ['user_id', 'name', 'meta_account_id', 'meta_account_name', 'meta_currency'];

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function conversations(): HasMany {
        return $this->hasMany(Conversation::class);
    }

    public function pendingActions(): HasMany {
        return $this->hasMany(PendingAction::class);
    }

    public function auditLogs(): HasMany {
        return $this->hasMany(AuditLog::class);
    }
}
