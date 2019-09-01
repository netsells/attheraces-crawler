<?php

//Get json data and send an email (if there is any data). Email is optional.
Route::get('get-data/{email?}')
    ->uses('RaceController@api')
    ->name('get.api.data');
