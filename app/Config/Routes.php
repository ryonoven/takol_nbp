<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute('true');
$routes->post('pdfself/uploadPdf', 'Pdfself::uploadPdf');
$routes->get('pdfself/generateFullReport', 'Pdfself::generateFullReport'); // Pastikan rute ini ada dan mengarah ke generateFullReport
$routes->get('pdfself/download/(:segment)', 'Pdfself::downloadPdf/$1');


// $routes->get('/faktor/getKomentarByFaktorId', 'FaktorController::getKomentarByFaktorId');



// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->group('', ['filter' => 'login'], function ($routes) {
    $routes->get('/', 'Home::index');
    $routes->get('/admin', 'Admin::index', ['filter' => 'role:admin']);
    $routes->get('/admin/index', 'Admin::index', ['filter' => 'role:admin']);
    $routes->get('/admin/(:num)', 'Admin::detail/$1', ['filter' => 'role:admin']);



});


/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
