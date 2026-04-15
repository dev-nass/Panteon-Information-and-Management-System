<?php

use App\Http\Controllers\Api\MapDataController;
use App\Http\Controllers\Api\MapSearchDataController;
use App\Http\Controllers\Api\PathfinderController;
use App\Http\Controllers\Api\LotManagementSearchController;
use App\Http\Controllers\Api\LotManagementController as ApiLotManagement;

Route::controller(MapDataController::class)->group(function () {
    Route::get('/data/burials', 'burialRecords')->name('api.map.burials');
    Route::get('/data/phases', 'phaseRecords')->name('api.map.phases');
    Route::get('/data/partial-burials', 'partialBurialRecords')->name('api.map.partial.burials');
    Route::get('/data/cluster/{clusterId}/burials', 'clusterBurialRecords')->name('api.map.cluster.burials');
});

Route::controller(MapSearchDataController::class)->group(function () {
    Route::get('/data-search/burials', 'search')->name('api.map.search');
});

Route::controller(LotManagementSearchController::class)->group(function () {
    Route::get('/data/phase', 'phase')->name('api.lot.management.phase');
    Route::get('/data/cluster', 'cluster')->name('api.lot.management.cluster');
    Route::get('/data/lot', 'lot')->name('api.lot.management.lot');
});

Route::controller(ApiLotManagement::class)->group(function () {
    Route::get('/data/phase/{phaseId}/clusters', 'getClusters')->name('api.lot.management.clusters');
    Route::get('/data/cluster/{clusterId}/lots', 'getLots')->name('api.lot.management.lots');
});

Route::controller(PathfinderController::class)->group(function () {
    Route::get('/api/junctions', 'junctions')->name('api.junctions');
    Route::get('/api/pathways', 'pathways')->name('api.pathways');
});