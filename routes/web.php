<?php


//Get a page and send an email with data (if there is any data), email is required. Page will auto-refresh every minute
Route::get('get-data/{email}')
    ->uses('RaceController@index')
    ->name('get.data');
