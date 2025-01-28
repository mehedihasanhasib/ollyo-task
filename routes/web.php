<?php

use App\Http\Controllers\HomeController;
use App\Http\Middlewares\AuthMiddleware;
use App\Http\Controllers\EventController;
use App\Http\Controllers\AttendeeController;
use App\Http\Controllers\EventUserController;

$router->get('/', [HomeController::class, 'index'])->name('home');
$router->get('/events', [EventUserController::class, 'index'])->name('events');
$router->get('/event', [EventUserController::class, 'show'])->name('event.show');

$router->get('/attendees', [AttendeeController::class, 'index'])->name('attendee.index');
$router->post('/attendee/store', [AttendeeController::class, 'store'])->name('attendee.store');

$router->get('/my-events', [EventController::class, 'index'])->name('myevents')->middleware(AuthMiddleware::class);
$router->get('/event/create', [EventController::class, 'create'])->name('event.create')->middleware(AuthMiddleware::class);
$router->post('/event/store', [EventController::class, 'store'])->name('event.store')->middleware(AuthMiddleware::class);
$router->get('/event/edit', [EventController::class, 'edit'])->name('event.edit')->middleware(AuthMiddleware::class);
$router->put('/event/update', [EventController::class, 'update'])->name('event.update')->middleware(AuthMiddleware::class);

// $router->get('/attendees', [AttendeeController::class, 'index'])->name('attendees');


require BASE_PATH . "/routes/auth.php";
