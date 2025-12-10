<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->group('posts', ['namespace' => 'Tests\Support\Modules\Posts\Controllers'], static function ($routes) {
    $routes->get('api', 'PostsController::api', ['as' => 'posts.api']);
});
