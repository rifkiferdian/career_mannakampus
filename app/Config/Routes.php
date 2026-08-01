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
    $routes->get('dashboard', 'DashboardController::index', ['filter' => 'permission:dashboard.admin.view', 'as' => 'hrd.dashboard']);
    $routes->get('profil', 'ProfileController::index', ['filter' => 'hrd-auth', 'as' => 'hrd.profile']);
    $routes->post('profil', 'ProfileController::update', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.update']);
    $routes->post('profil/password', 'ProfileController::updatePassword', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.password']);
    $routes->post('profil/perangkat/(:num)/revoke', 'ProfileController::revokeSession/$1', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.session.revoke']);
    $routes->post('profil/perangkat/revoke-all', 'ProfileController::revokeAllSessions', ['filter' => 'hrd-auth', 'as' => 'hrd.profile.sessions.revoke']);
    $routes->get('akses', 'AccessController::index', ['filter' => 'super-admin', 'as' => 'hrd.access']);
    $routes->post('akses/users', 'AccessController::createUser', ['filter' => 'super-admin', 'as' => 'hrd.access.users.create']);
    $routes->post('akses/users/(:num)/status', 'AccessController::updateStatus/$1', ['filter' => 'super-admin', 'as' => 'hrd.access.users.status']);
    $routes->post('akses/users/(:num)/role', 'AccessController::updateRole/$1', ['filter' => 'super-admin', 'as' => 'hrd.access.users.role']);
    $routes->post('akses/roles/(:num)/permissions', 'AccessController::updatePermissions/$1', ['filter' => 'super-admin', 'as' => 'hrd.access.permissions']);
    $routes->get('departemen', 'DepartmentController::index', ['filter' => 'permission:departments.view', 'as' => 'hrd.departments']);
    $routes->post('departemen', 'DepartmentController::create', ['filter' => 'permission:departments.manage', 'as' => 'hrd.departments.create']);
    $routes->post('departemen/(:num)', 'DepartmentController::update/$1', ['filter' => 'permission:departments.manage', 'as' => 'hrd.departments.update']);
    $routes->post('departemen/(:num)/status', 'DepartmentController::toggle/$1', ['filter' => 'permission:departments.manage', 'as' => 'hrd.departments.status']);
    $routes->post('departemen/(:num)/hapus', 'DepartmentController::delete/$1', ['filter' => 'permission:departments.delete', 'as' => 'hrd.departments.delete']);
    $routes->get('pengaturan-rekrutmen', 'RecruitmentSettingsController::index', ['filter' => 'permission:recruitment.settings.view', 'as' => 'hrd.recruitment.settings']);
    $routes->post('pengaturan-rekrutmen/tahapan', 'RecruitmentSettingsController::updateStages', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.stages']);
    $routes->post('pengaturan-rekrutmen/tahapan/(:num)', 'RecruitmentSettingsController::updateStage/$1', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.stages.update']);
    $routes->post('pengaturan-rekrutmen/penolakan', 'RecruitmentSettingsController::createRejectionTemplate', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.rejections.create']);
    $routes->post('pengaturan-rekrutmen/penolakan/(:num)', 'RecruitmentSettingsController::updateRejectionTemplate/$1', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.rejections.update']);
    $routes->post('pengaturan-rekrutmen/penolakan/(:num)/status', 'RecruitmentSettingsController::toggleRejectionTemplate/$1', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.rejections.status']);
    $routes->post('pengaturan-rekrutmen/penolakan/(:num)/hapus', 'RecruitmentSettingsController::deleteRejectionTemplate/$1', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.rejections.delete']);
    $routes->post('pengaturan-rekrutmen/screening', 'RecruitmentSettingsController::createScreeningQuestion', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.screening.create']);
    $routes->post('pengaturan-rekrutmen/screening/(:num)', 'RecruitmentSettingsController::updateScreeningQuestion/$1', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.screening.update']);
    $routes->post('pengaturan-rekrutmen/screening/(:num)/status', 'RecruitmentSettingsController::toggleScreeningQuestion/$1', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.screening.status']);
    $routes->post('pengaturan-rekrutmen/screening/(:num)/hapus', 'RecruitmentSettingsController::deleteScreeningQuestion/$1', ['filter' => 'permission:recruitment.settings.manage', 'as' => 'hrd.recruitment.screening.delete']);
    $routes->post('logout', 'AuthController::logout', ['filter' => 'hrd-auth', 'as' => 'hrd.logout']);
});
