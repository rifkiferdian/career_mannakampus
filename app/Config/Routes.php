<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->set404Override('\\App\\Controllers\\NotFound::index');

$routes->get('/', 'Home::index');
$routes->get('lowongan', '\App\Modules\Recruitment\Controllers\VacancyController::index');
$routes->get('lowongan/cari', '\App\Modules\Recruitment\Controllers\VacancyController::search');
$routes->get('tahapan-seleksi', 'Home::selectionProcess');
