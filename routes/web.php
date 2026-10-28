<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogoutController;
use App\Livewire\Tes;
use App\Livewire\Depo;
use App\Livewire\Divisi;
use App\Livewire\Segment;
use App\Livewire\Departement;
use App\Livewire\TrialBalance;
use App\Livewire\Login;
use App\Livewire\Dashboard;

// Route::livewire('/', Login::class);
// Route::livewire('/tes', Tes::class);
// Route::livewire('/dashboard', Dashboard::class);
// Route::livewire('/depo', Depo::class);
// Route::livewire('/divisi', Divisi::class);
// Route::livewire('/segment', Segment::class);
// Route::livewire('/departement', Departement::class);
// Route::livewire('/trial-balance', TrialBalance::class);


Route::livewire('/', Login::class)->name('login')->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');

    Route::livewire('/dashboard', Dashboard::class);
    Route::livewire('/depo', Depo::class);
    Route::livewire('/divisi', Divisi::class);
    Route::livewire('/segment', Segment::class);
    Route::livewire('/departement', Departement::class);
    Route::livewire('/trial-balance', TrialBalance::class);
    Route::livewire('/tes', Tes::class);
});
