<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
{
    /**
     * Run the request filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
       //Header rules must be set to spesific
        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => 'Content-Type, Authorization, X-Requested-With'
        ];

        //If method options
        if ($request->isMethod('OPTIONS')) {
            //Return method as options
            return response()->json('{"method": "OPTIONS"}', 200, $headers);
        }

        //We'll forwarding response with following headers
        $response = $next($request);
        foreach ($headers as $key => $row) {
            $response->header($key, $row);
        }
        return $response;
    }

}
