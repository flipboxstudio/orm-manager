<?php 

return [
	/*
	|--------------------------------------------------------------------------
	| Model Base Path
	|--------------------------------------------------------------------------
	| base path of models should be path of folder of model
	| by default is app/
	*/

	'basepath' => app_path(),

	/*
	|--------------------------------------------------------------------------
	| Exclude Directory
	|--------------------------------------------------------------------------
	| exclude directory for scan inside given basepath
	*/

	'exclude_dir' => ['Console', 'Exceptions', 'Http', 'Provid']
];
