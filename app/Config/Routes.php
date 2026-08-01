<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->set404Override('\\App\\Controllers\\NotFound::index');

$routes->get('/', 'Home::index');
$routes->get('lowongan', '\App\Modules\Recruitment\Controllers\VacancyController::index');
$routes->get('lowongan/cari', '\App\Modules\Recruitment\Controllers\VacancyController::search');
$routes->get('lowongan/(:segment)/lamar', '\App\Modules\Recruitment\Controllers\ApplicationController::create/$1');
$routes->post('lowongan/(:segment)/lamar', '\App\Modules\Recruitment\Controllers\ApplicationController::store/$1');
$routes->get('lamaran/berhasil', '\App\Modules\Recruitment\Controllers\ApplicationController::success');
$routes->get('lamaran/status', '\App\Modules\Recruitment\Controllers\ApplicationStatusController::index');
$routes->post('lamaran/status', '\App\Modules\Recruitment\Controllers\ApplicationStatusController::lookup');
$routes->get('tahapan-seleksi', 'Home::selectionProcess');

$routes->group('adminhrdmannakampus', ['namespace' => 'App\Modules\Admin\Controllers'], static function ($routes): void {
    $routes->get('', 'AuthController::login', ['as' => 'hrd.login']);
    $routes->post('', 'AuthController::authenticate', ['as' => 'hrd.authenticate']);
    $routes->get('dashboard', 'DashboardController::index', ['filter' => 'hrd-auth', 'as' => 'hrd.dashboard']);
    $routes->get('profil', 'ProfileController::index', ['filter' => 'hrd-auth', 'as' => 'hrd.profile']);
    $routes->post('profil', 'ProfileController::update', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.update']);
    $routes->post('profil/password', 'ProfileController::updatePassword', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.password']);
    $routes->post('profil/perangkat/(:num)/revoke', 'ProfileController::revokeSession/$1', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.session.revoke']);
    $routes->post('profil/perangkat/revoke-all', 'ProfileController::revokeAllSessions', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.sessions.revoke']);
    $routes->post('logout', 'AuthController::logout', ['filter' => 'hrd-auth', 'as' => 'hrd.logout']);
});
