<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [InvitationController::class, 'index'])->name('home');
Route::get('i/{invitation}', [InvitationController::class, 'show'])
    ->middleware('throttle:60,1')
    ->name('invitation.show');
Route::get('wedding.ics', CalendarController::class)->name('calendar');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('guests', 'pages::guests')
        ->middleware('can:manage-invitations')
        ->name('guests.index');
});

require __DIR__.'/settings.php';
