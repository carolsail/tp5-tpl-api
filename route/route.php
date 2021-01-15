<?php

Route::group('', function(){
	Route::get('hello/:name', 'admin/index/hello');
})->allowCrossDomain();

