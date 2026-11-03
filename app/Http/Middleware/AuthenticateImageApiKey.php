<?php
namespace App\Http\Middleware;
use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;
class AuthenticateImageApiKey {
 public function handle(Request $request,Closure $next): Response {
  $secret=(string)$request->bearerToken();
  if($secret==='' || !str_starts_with($secret,'rms_live_')) return response()->json(['message'=>'Unauthenticated. Provide a valid Bearer API key.'],401);
  $key=ApiKey::query()->where('prefix',substr($secret,0,16))->whereNull('revoked_at')->first();
  if(!$key || !hash_equals($key->key_hash,hash('sha256',$secret))) return response()->json(['message'=>'Invalid API key.'],401);
  if(!$key->isUsable()) return response()->json(['message'=>'This API key has expired or been revoked.'],401);
  $user=$key->user;
  if(!$user) return response()->json(['message'=>'API key owner is unavailable.'],401);
  $limiter='image-api-key:'.$key->id;
  if(RateLimiter::tooManyAttempts($limiter,max(1,$key->rate_limit))) {
   $wait=RateLimiter::availableIn($limiter);
   return response()->json(['message'=>'API rate limit exceeded.','retry_after'=>$wait],429)->header('Retry-After',(string)$wait);
  }
  RateLimiter::hit($limiter,60);
  $key->forceFill(['last_used_at'=>now()])->save(); $key->increment('usage_count');
  $request->attributes->set('api_key',$key); $request->setUserResolver(static fn()=>$user);
  return $next($request);
 }
}