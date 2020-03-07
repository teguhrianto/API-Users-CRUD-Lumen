<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['middleware' => 'auth'], function() use($router) {
    $router->get('/users', 'Users@index');
    $router->get('/users/login', 'Users@getUserLogin');
    $router->post('/logout', 'Users@logout');
    $router->post('/users', 'Users@store');
    $router->get('/users/{id}', 'Users@edit');
    $router->put('/users/{id}', 'Users@update');
    $router->delete('/users/{id}', 'Users@destroy');
});

$router->post('/login', 'Users@login');
$router->post('/reset', 'Users@sendResetToken');
$router->put('/reset/{token}', 'Users@verifyResetPassword');
