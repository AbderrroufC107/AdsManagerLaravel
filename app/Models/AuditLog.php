<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model {
    use HasUuids;
    protected $fillable = ['pending_action_id', 'profile_id', 'tool_name', 'tool_input', 'preview', 'before_state', 'after_state', 'meta_response', 'approved', 'error'];
    protected $casts = ['tool_input' => 'array', 'before_state' => 'array', 'after_state' => 'array', 'meta_response' => 'array', 'approved' => 'boolean'];
    public function pendingAction(): BelongsTo { return $this->belongsTo(PendingAction::class); }
    public function profile(): BelongsTo { return $this->belongsTo(Profile::class); }
}
