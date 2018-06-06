<?php

/*
First of all you need to create a Space at https://spaces.mgniers.com/ (It is currently free).
After you create it, you will receive the credentials to use the API.
Enter the required parameters in the variables below.
*/
	
$_cfg = [
	'api' => [
		'space' => "my_space_name", //your space name
		'url' => "api.na1.spaces.mgniers.com", //api URL
		'key' => "my_space_key_with_41_chars", //your space key
		'directory' => "webs" //the directory in your space where the files will be registered
	],
	'tmp_dir' => '/path/to/temp_dir' //the temp directory to upload files
];