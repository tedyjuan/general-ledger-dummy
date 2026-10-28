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
use App\Livewire\AksesPerMenu;

Route::livewire('/', Login::class)->name('login')->middleware('guest');
Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::livewire('/dashboard', Dashboard::class)->middleware('permission:dashboard,read');
    Route::livewire('/depo', Depo::class)->middleware('permission:depo,read');
    Route::livewire('/divisi', Divisi::class)->middleware('permission:divisi,read');
    Route::livewire('/segment', Segment::class)->middleware('permission:segment,read');
    Route::livewire('/departement', Departement::class)->middleware('permission:departement,read');
    Route::livewire('/trial-balance', TrialBalance::class)->middleware('permission:trial-balance,read');
    Route::livewire('/tes', Tes::class)->middleware('permission:tes,read');
    Route::livewire('/akses-menu', AksesPerMenu::class)->middleware('permission:permissions,manage');
});
