<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    use \App\Traits\ApiResponseTrait;

    public function handle(Request $request, Closure $next, string $permission, ?string $model = null)
    {
        if (!auth()->check()) {
            return $this->errorResponse('You must be logged in.', null, 401);
        }

        $user = auth()->user();

        if (!$user->can($permission)) {
            return $this->errorResponse(
                'You do not have permission to access this resource.',
                null,
                403
            );
        }

        if ($model) {
            $resource = $request->route($model);

            if (!$resource) {
                return $this->errorResponse('Resource not found.', null, 404);
            }

            if ($user->hasRole('super-admin')) {
                return $next($request);
            }

            if ($resource->user_id != $user->id) {
                return $this->errorResponse(
                    'You do not own this resource.',
                    null,
                    403
                );
            }
        }

        return $next($request);
    }
}
