<?php

// Front-end routes
Route::get('/inschrijven', 'Front@index');
Route::get('/inschrijven/{id}', ['as' => 'inschrijven_specifiek', 'uses' => 'Front@inschrijven_specifiek']);
Route::post('/aanmelden/{id}', ['as' => 'aanmelden_specifiek', 'uses' => 'Front@aanmelden_specifiek']);
Route::get('/uitgebreid-inschrijven', 'Front@uitgebreidinschrijven');
Route::post('/uitgebreidaanmelden', 'Front@uitgebreidaanmelden');
Route::post('/aanmelden', 'Front@aanmelden');
Route::get('/afmelden/{id}/{salt}', 'Front@afmelden');
Route::get('/disclaimer', 'Front@disclaimer');
Route::get('/privacy', 'Front@privacy');
Route::get('/', 'Front@index');

// Administration routes
Route::get('/administratie', 'Administratie@index');
Route::get('/administratie/nieuwe_maaltijd', 'Administratie@nieuwe_maaltijd');
Route::post('/administratie/nieuwe_maaltijd_maken', 'Administratie@nieuwe_maaltijd_maken');
