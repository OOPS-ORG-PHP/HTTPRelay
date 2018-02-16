<?php
/*
 * Test code for HTTPRelay
 * $Id$
 */

$iniget = function_exists ('___ini_get') ? '___ini_get' : 'ini_get';
$iniset = function_exists ('___ini_set') ? '___ini_set' : 'ini_set';

$cwd = getcwd ();
$ccwd = basename ($cwd);
if ( $ccwd == 'tests' ) {
	$oldpath = $iniget ('include_path');
	$newpath = preg_replace ("!/{$ccwd}!", '', $cwd);
	$iniset ('include_path', $newpath . ':' . $oldpath);
}

require_once 'HTTPRelay.php';

set_error_handler('myException::myErrorHandler');

try {
	$header = array (
		'X_REQ_HEADER' => 'addtional request header',
		'X_REQ_HEADER2' => 'addtional request header 2'
	);

	$http = new HTTPRelay ($header);
	// you can set 'form-data' or 'url-encode'
	$http->posttype = 'form-data';

	$buf = $http->relay (
		'http://relay-host.domain.com/relaypage/',
		'10',
		'relay-host.domain.com'
	);

	if ( $buf === false )
		throw new myException ($http->error, E_USER_ERROR);

	echo $buf . "\n\n\n";
	print_r ($http->info);
} catch ( myException $e ) {
	printf ("%s\n", $e->Message ());
	print_r ($e->TraceAsArray ()) . "\n";
	$e->finalize ();
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 * vim: set filetype=php noet sw=4 ts=4 fdm=marker:
 * vim600: noet sw=4 ts=4 fdm=marker
 * vim<600: noet sw=4 ts=4
 */
?>
