<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as Axios;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $host = env('API_HOST');
        $endpoint = "{$host}/auth/me";
        $requestable = false;
        
        if ($request->headers->has('authorization')) {
            $token = $request->header('authorization');
            $options['headers']['authorization'] = $token;
            $request->access_token = $token;
            $requestable = true;
        } else if ($request->has('access_token')) {
            $token = $request->get('access_token');
            $queryString = http_build_query(['access_token' => $token]);
            $request->access_token = "Bearer {$token}";
            $endpoint = $endpoint."?".$queryString;
            $requestable = true;
        }
        if ($requestable) {
            $axios = new Axios();
            try {
                if (isset($options)) {
                    $response = $axios->request('GET', $endpoint, $options);
                } else {
                    $response = $axios->request('GET', $endpoint);
                }
                return $next($request);
            } catch (\Exception $e) {
                return response('Unauthorizeds.', 401);
            }
        } else {
            return response('Unauthorized.', 401);
        }
    }
}
