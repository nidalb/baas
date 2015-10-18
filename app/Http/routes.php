<?php

Route::get('/', function () {
    return view('welcome');
});

/*
 * Object Routes based on Parse Rest API documentation
 * https://parse.com/docs/rest/guide#quick-reference-objects
*/
Route::group(['prefix' => '/1/'], function () {

    Route::group(['prefix' => 'classes/'], function () {
        Route::post('{className}',              'ParseObjectController@create');
        Route::get('{className}/{objectId}',    'ParseObjectController@getById');
        Route::put('{className}/{objectId}',    'ParseObjectController@update');
        Route::get('{className}',               'ParseObjectController@get');
        Route::delete('{className}/{objectId}', 'ParseObjectController@delete');
    });

});
