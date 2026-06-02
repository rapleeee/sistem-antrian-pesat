<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Admin\Dashboard as AdminDashboard;
use App\Livewire\Admin\Panels as AdminPanels;
use App\Livewire\Admin\Participants as AdminParticipants;
use App\Livewire\Admin\Reports as AdminReports;
use App\Livewire\Operator\PanelOperator;
use App\Livewire\Display\AllPanels;
use App\Livewire\Display\SinglePanel;

// Redirect root ke display
Route::redirect('/', '/display')->name('home');

// ── Display (publik) ─────────────────────────────────────────────────
Route::prefix('display')->name('display.')->group(function () {
    Route::get('/', AllPanels::class)->name('all');
    Route::get('/{panel}', SinglePanel::class)->name('panel');
});

// ── Admin (butuh login + role super_admin) ───────────────────────────
Route::middleware(['auth', 'verified', 'role:super_admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', AdminDashboard::class)->name('dashboard');
        Route::get('/panels', AdminPanels::class)->name('panels');
        Route::get('/panels/{panel}/participants', AdminParticipants::class)->name('participants');
        Route::get('/reports', AdminReports::class)->name('reports');
    });

// ── Operator (publik, auth via PIN session) ──────────────────────────
Route::get('/operator/{panel}', PanelOperator::class)->name('operator.panel');

// Dashboard default redirect
Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    return redirect()->route('admin.dashboard');
})->name('dashboard');

require __DIR__.'/settings.php';

