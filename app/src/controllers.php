<?php
/**
 * Routing and controllers.
 *
 * @copyright (c) 2016 Tomasz Chojna
 *
 * @link http://epi.chojna.info.pl
 */


use Controller\UserController;
use Controller\PostsController;
use Controller\AuthController;

$app->mount('/user', new UserController());
$app->mount('/posts', new PostsController());
$app->mount('/auth', new AuthController());