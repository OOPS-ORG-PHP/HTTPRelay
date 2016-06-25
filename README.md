# HTTPRelay pear package
![GitHub license](https://img.shields.io/badge/license-BSD-blue.svg)

## License

Copyright (c) 2016 JoungKyun.Kim &lt;http://oops.org&gt; All rights reserved

This program is under BSD license

## Description

Request HTTP GET/POST/HEAD or relay recieved query to the other server.

## Installation

We recommand to install with pear command cause of dependency pear packages.

### 1. use pear command

```bash
[root@host ~]$ # add pear channel 'pear.oops.org'
[root@host ~]$ pear channel-discover pear.oops.org
Adding Channel "pear.oops.org" succeeded
Discovery of channel "pear.oops.org" succeeded
[root@host ~]$ # add OAUTH2 pear package
[root@host ~]$ pear install oops/HTTPRelay
downloading HTTPRelay-1.0.5.tgz ...
Starting to download HTTPRelay-1.0.5.tgz (5,783 bytes)
...done: 5,783 bytes
downloading myException-1.0.1.tgz ...
Starting to download myException-1.0.1.tgz (3,048 bytes)
...done: 3,048 bytes
install ok: channel://pear.oops.org/myException-1.0.1
install ok: channel://pear.oops.org/HTTPRelay-1.0.5
[root@host ~]$
```

If you wnat to upgarde version:

```bash
[root@host ~]$ pear upgrade oops/HTTPRelay
```


### 2. install by hand

Get last release at https://github.com/OOPS-ORG-PHP/HTTPRelay/releases and uncompress pakcage within PHP include_path.

You must need follow dependency pear packages:
 * myException at https://github.com/OOPS-ORG-PHP/myException/releases/

## Usages

Refence siste: http://pear.oops.org/docs/HTTPRelay/HTTPRelay.html (with Korean)


### 1. GET method

```php
<?php
require_once 'HTTPRelay.php';

// all PHP error handler to myException
set_error_handler ('myException::myErrorHandler');

try {
	$http = new HTTPRelay;

	$buf = $http->fetch ('https://raw.github.com/twbs/bootstrap/master/bower.json', 3);

	if ( $buf === false ) {
		echo 'ERROR:  ' . $http->error . "\n";
		exit;
	}

	$buf = json_decode ($buf);

	print_r ($buf);
	print_r ($http->info);
} catch ( myException $e ) {
	echo $e->Message () . "\n";
	print_r ($e->TraceAsArray ()) . "\n";
	$e->finalize ();
}
?>
```

### 2. POST method

```php
<?php
require_once 'HTTPRelay.php';

// all PHP error handler to myException
set_error_handler ('myException::myErrorHandler');

try {
	$http = new HTTPRelay;

	$post = array (
		'name' => 'joungkyun',
		'grade' => 'A plus plus'
	);

	// prototype: string fetch( string $to, [int $tmout = 60], [string $httphost = ''], [array $post = null])
	$buf = $http->fetch ('https://raw.github.com/twbs/bootstrap/master/bower.json', 3, null, $post);

	if ( $buf === false ) {
		echo 'ERROR:  ' . $http->error . "\n";
		exit;
	}

	$buf = json_decode ($buf);

	print_r ($buf);
	print_r ($http->info);
} catch ( myException $e ) {
	echo $e->Message () . "\n";
	print_r ($e->TraceAsArray ()) . "\n";
	$e->finalize ();
}
?>
```

### 3. HEAD method

```php
<?php
require_once 'HTTPRelay.php';

// all PHP error handler to myException
set_error_handler ('myException::myErrorHandler');

try {
	$http = new HTTPRelay;

	// prototype: stdClass head( string $to, [int $tmout = 60], [string $httphost = ''])
	$buf = $http->head ('https://raw.github.com/twbs/bootstrap/master/bower.json', 3);

	if ( $buf === false ) {
		echo 'ERROR:  ' . $http->error . "\n";
		exit;
	}

	print_r ($http->info);
	print_r ($buf);
} catch ( myException $e ) {
	echo $e->Message () . "\n";
	print_r ($e->TraceAsArray ()) . "\n";
	$e->finalize ();
}
?>
```

### 4. Query relay

```php
<?php
require_once 'HTTPRelay.php';

// all PHP error handler to myException
set_error_handler ('myException::myErrorHandler');

try {
	$header = array (
		'X_RELAY_SERVER' => 'http://this-host',
		'X_REQ_HEADER' => 'addtional request header',
		'X_REQ_HEADER2' => 'addtional request header 2'
	);
	$http = new HTTPRelay ($header);
	// you can set 'form-data' or 'url-encode'
	$http->posttype = 'form-data';

	// prototype: string relay( string $to, [int $tmout = 60], [string $httphost = ''])
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
?>
```
