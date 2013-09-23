<?php
/*
 * Test code for pear_HTTPRelay
 * $Id$
 */
require_once 'HTTPRelay.php';

$http = new HTTPRelay;

$buf = $http->fetch ('https://raw.github.com/twbs/bootstrap/master/bower.json', 3);

if ( $buf === false ) {
	echo 'ERROR:  ' . $http->error . "\n";
	exit;
}

$buf = json_decode ($buf);

print_r ($buf);
print_r ($http->info);

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
