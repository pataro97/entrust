<?php

declare(strict_types=1);

namespace Trebol\Entrust\Middleware;

/**
 * This file is part of Entrust,
 * a role & permission management solution for Laravel.
 *
 * @license MIT
 * @package Trebol\Entrust
 */

use Closure;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

class EntrustPermission
{
	const DELIMITER = '|';

	/**
     * Creates a new instance of the middleware.
     */
    public function __construct(protected \Illuminate\Contracts\Auth\Guard $guard)
    {
    }

	/**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  $permissions
     * @return mixed
     */
    public function handle($request, Closure $next, $permissions)
	{
		if (!is_array($permissions)) {
			$permissions = explode(self::DELIMITER, (string) $permissions);
		}

        try {
            $hasPermission = $request->user()->can($permissions);
        } catch (\BadMethodCallException | \Error) {
            $hasPermission = $request->user()->cans($permissions);
        }

        if ($this->guard->guest() || !$hasPermission) {
            switch (Config::get('entrust.type')) {
                case 'api':
                    return response()->json(Config::get('entrust.response-error'),403);
                default:
                    abort(403);
                    break;
            }
		}

		return $next($request);
	}
}
