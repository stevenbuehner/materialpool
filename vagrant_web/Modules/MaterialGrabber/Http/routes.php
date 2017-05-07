<?php

Route::group(['middleware' => 'web', 'prefix' => 'materialgrabber', 'namespace' => 'Modules\MaterialGrabber\Http\Controllers'], function()
{
    Route::get('/', 'MaterialGrabberController@index');
});
