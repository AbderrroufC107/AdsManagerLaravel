<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use HasFactory, Notifiable;

    protected $fillable = ['username', 'password'];
    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return ['password' => 'hashed'];
    }

    public function profiles(): HasMany {
        return $this->hasMany(Profile::class);
    }
}
