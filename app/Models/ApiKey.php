<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ApiKey extends Model {
 protected $fillable=['user_id','store_id','name','prefix','key_hash','scopes','rate_limit','usage_count','last_used_at','expires_at','revoked_at'];
 protected $hidden=['key_hash'];
 protected function casts(): array { return ['scopes'=>'array','last_used_at'=>'datetime','expires_at'=>'datetime','revoked_at'=>'datetime','rate_limit'=>'integer','usage_count'=>'integer']; }
 public function user(): BelongsTo { return $this->belongsTo(User::class); }
 public function store(): BelongsTo { return $this->belongsTo(Store::class); }
 public function isUsable(): bool { return $this->revoked_at===null && ($this->expires_at===null || $this->expires_at->isFuture()); }
 public function allows(string $scope): bool { return in_array($scope,$this->scopes??[],true); }
}