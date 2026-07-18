<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->set404Override('\\App\\Controllers\\NotFound::index');

$routes->get('/', 'Home::index');
$routes->get('lowongan', 'Home::jobs');
$routes->get('tahapan-seleksi', 'Home::selectionProcess');
$routes->get('daftar', 'Home::register');
$routes->post('daftar', 'Home::createAccount');
$routes->get('masuk', 'Home::login');
$routes->post('masuk', 'Home::authenticate');
