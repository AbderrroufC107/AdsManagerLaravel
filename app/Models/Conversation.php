<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model {
    use HasUuids, HasFactory;
    protected $fillable = ['title'];
    public function messages(): HasMany { return $this->hasMany(Message::class); }
    public function pendingActions(): HasMany { return $this->hasMany(PendingAction::class); }
}
