<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AuditLog extends Model {
    use HasUuids;
    protected $fillable = ['pending_action_id', 'tool_name', 'tool_input', 'preview', 'before_state', 'after_state', 'meta_response', 'approved', 'error'];
    protected $casts = ['tool_input' => 'array', 'before_state' => 'array', 'after_state' => 'array', 'meta_response' => 'array', 'approved' => 'boolean'];
}
