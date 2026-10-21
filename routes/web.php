<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Tes;
use App\Livewire\Depo;
use App\Livewire\ChartOfAccount;

Route::livewire('/tes', Tes::class);
Route::livewire('/depo', Depo::class);
Route::livewire('/coa', ChartOfAccount::class);
