<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogoutController;
use App\Livewire\Depo;
use App\Livewire\Divisi;
use App\Livewire\Segment;
use App\Livewire\Departement;
use App\Livewire\TrialBalance;
use App\Livewire\Login;
use App\Livewire\Dashboard;
use App\Livewire\AksesPerMenu;
use App\Livewire\Users;
use App\Livewire\FiscalPeriod;
use App\Livewire\JournalSource;
use App\Livewire\Coa;
use App\Livewire\CostCenter;
use App\Livewire\Product;

Route::livewire('/', Login::class)->name('login')->middleware('guest');
Route::middleware('auth')->group(function () {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::livewire('/dashboard', Dashboard::class)->middleware('permission:dashboard,read');
    Route::livewire('/depo', Depo::class)->middleware('permission:depo,read');
    Route::livewire('/divisi', Divisi::class)->middleware('permission:divisi,read');
    Route::livewire('/segment', Segment::class)->middleware('permission:segment,read');
    Route::livewire('/departement', Departement::class)->middleware('permission:departement,read');
    Route::livewire('/trial-balance', TrialBalance::class)->middleware('permission:trial-balance,read');
    Route::livewire('/akses-menu', AksesPerMenu::class)->middleware('permission:permissions,manage');
    Route::livewire('/user', Users::class)->middleware('permission:user,read');
    Route::livewire('/fiscal-period', FiscalPeriod::class)->middleware('permission:fiscal-period,read');
    Route::livewire('/journal-sources', JournalSource::class)->middleware('permission:journal-sources,read');
    Route::livewire('/coa', Coa::class)->middleware('permission:coa,read');
    Route::livewire('/cost-center', CostCenter::class)->middleware('permission:cost-center,read')->name('cost-center.index');
    Route::livewire('/product', Product::class)->middleware('permission:product,read');
});
