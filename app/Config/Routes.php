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
