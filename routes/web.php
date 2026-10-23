<?php

use Illuminate\Support\Facades\Route;

use App\Livewire\Tes;
use App\Livewire\Depo;
use App\Livewire\Divisi;
use App\Livewire\Departement;

Route::livewire('/tes', Tes::class);
Route::livewire('/depo', Depo::class);
Route::livewire('/divisi', Divisi::class);
Route::livewire('/departement', Departement::class);
