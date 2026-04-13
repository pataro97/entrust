<?php

declare(strict_types=1);

namespace Trebol\Entrust;

use Closure;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Events\RouteMatched;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * This class is the main entry point of entrust. Usually the interaction
 * with this class will be done through the Entrust Facade
 *
 * @license MIT
 * @package Trebol\Entrust
 */

class Entrust
{
    /**
     * Laravel application
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;

    /**
     * Create a new confide instance.
     *
     * @param \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Checks if the current user has a role by its name
     *
     * @param string $name Role name.
     *
     * @return bool
     */
    public function hasRole($role, $requireAll = false)
    {
        if ($user = $this->user()) {
            return $user->hasRole($role, $requireAll);
        }

        return false;
    }

    /**
     * Check if the current user has a permission by its name
     *
     * @param string $permission Permission string.
     *
     * @return bool
     */
    public function can($permission, $requireAll = false)
    {
        if ($user = $this->user()) {
            try {
                return $user->can($permission, $requireAll);
            } catch (\BadMethodCallException | \Error) {
                return $user->cans($permission, $requireAll);
            }
        }

        return false;
    }

    /**
     * Register a legacy route filter or emulate it on modern Laravel versions.
     */
    private function registerRouteFilter(string $filterName, string $route, Closure $closure): void
    {
        try {
            $this->app->router->filter($filterName, $closure);
            $this->app->router->when($route, $filterName);

            return;
        } catch (\BadMethodCallException | \Error) {
        }

        $this->app->events->listen(RouteMatched::class, function (RouteMatched $event) use ($route, $closure): void {
            if (!fnmatch($route, $event->request->path())) {
                return;
            }

            $response = $closure();

            if ($response instanceof Responsable) {
                throw new HttpResponseException($response->toResponse($event->request));
            }

            if ($response instanceof SymfonyResponse) {
                throw new HttpResponseException($response);
            }
        });
    }

    /**
     * Check if the current user has a role or permission by its name
     *
     * @param array|string $roles            The role(s) needed.
     * @param array|string $permissions      The permission(s) needed.
     * @param array $options                 The Options.
     *
     * @return bool
     */
    public function ability($roles, $permissions, $options = [])
    {
        if ($user = $this->user()) {
            return $user->ability($roles, $permissions, $options);
        }

        return false;
    }

    /**
     * Get the currently authenticated user or null.
     *
     * @return Illuminate\Auth\UserInterface|null
     */
    public function user()
    {
        return $this->app->auth->user();
    }

    /**
     * Filters a route for a role or set of roles.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string       $route      Route pattern. i.e: "admin/*"
     * @param array|string $roles      The role(s) needed
     * @param mixed        $result     i.e: Redirect::to('/')
     * @param bool         $requireAll User must have all roles
     */
    public function routeNeedsRole($route, $roles, $result = null, $requireAll = true): void
    {
        $filterName  = is_array($roles) ? implode('_', $roles) : $roles;
        $filterName .= '_'.substr(md5($route), 0, 6);

        $closure = function () use ($roles, $result, $requireAll) {
            $hasRole = $this->hasRole($roles, $requireAll);

            if (!$hasRole) {
                return empty($result) ? $this->app->abort(403) : $result;
            }
        };

        $this->registerRouteFilter($filterName, $route, $closure);
    }

    /**
     * Filters a route for a permission or set of permissions.
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string       $route       Route pattern. i.e: "admin/*"
     * @param array|string $permissions The permission(s) needed
     * @param mixed        $result      i.e: Redirect::to('/')
     * @param bool         $requireAll  User must have all permissions
     */
    public function routeNeedsPermission($route, $permissions, $result = null, $requireAll = true): void
    {
        $filterName  = is_array($permissions) ? implode('_', $permissions) : $permissions;
        $filterName .= '_'.substr(md5($route), 0, 6);

        $closure = function () use ($permissions, $result, $requireAll) {
            $hasPerm = $this->can($permissions, $requireAll);

            if (!$hasPerm) {
                return empty($result) ? $this->app->abort(403) : $result;
            }
        };

        $this->registerRouteFilter($filterName, $route, $closure);
    }

    /**
     * Filters a route for role(s) and/or permission(s).
     *
     * If the third parameter is null then abort with status code 403.
     * Otherwise the $result is returned.
     *
     * @param string       $route       Route pattern. i.e: "admin/*"
     * @param array|string $roles       The role(s) needed
     * @param array|string $permissions The permission(s) needed
     * @param mixed        $result      i.e: Redirect::to('/')
     * @param bool         $requireAll  User must have all roles and permissions
     */
    public function routeNeedsRoleOrPermission($route, $roles, $permissions, $result = null, $requireAll = false): void
    {
        $filterName  =      is_array($roles)       ? implode('_', $roles)       : $roles;
        $filterName .= '_'.(is_array($permissions) ? implode('_', $permissions) : $permissions);
        $filterName .= '_'.substr(md5($route), 0, 6);

        $closure = function () use ($roles, $permissions, $result, $requireAll) {
            $hasRole  = $this->hasRole($roles, $requireAll);
            $hasPerms = $this->can($permissions, $requireAll);

            $hasRolePerm = $requireAll ? $hasRole && $hasPerms : $hasRole || $hasPerms;

            if (!$hasRolePerm) {
                return empty($result) ? $this->app->abort(403) : $result;
            }
        };

        $this->registerRouteFilter($filterName, $route, $closure);
    }
}
