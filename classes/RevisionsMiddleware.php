<?php namespace NedStrk\Revisions\Classes;

use Closure;

class RevisionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if (\BackendAuth::check()) {
            return $next($request);
        }

        \App::abort(404);
    }
}
