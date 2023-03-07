<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProjectAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        //TODO: make this a generic middleware that check the $request (get, post, route parameters) for parameters like project_id, sample_id, file_id, etc and then checks if the current user has access to that project

        if(!userCanAccessProject(request()->route('project')))
            return response('Unauthorized', 401);

        return $next($request);
    }
}
