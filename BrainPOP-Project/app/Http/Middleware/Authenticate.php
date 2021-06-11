<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use App\Models\Session;
use Closure;
use Carbon\Carbon;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
            return route('login');
        }
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next) {
      if ( !$request->user() ) {
        // user session is not valid (user is not authenticated)
        // return response('Unauthorized', 401);
      }
      else {
        // check if user session expired.
        $session = Session::where('user_id', $request->user()->id )->first();
        $now = Carbon::now();
        $lastActivity = new Carbon($session->last_activity);
        $diffInHours = $now->diffInHours($lastActivity); // number of hours from last activity till now
        if ($diffInHours >= 72) {
          return response('Unauthorized', 401);
        }
        // update user session last activity
        $session->update([
          'last_activity' => $now->timestamp,
        ]);
      }

      // Process the request (token is valid)
      return $next($request);
    }
}
