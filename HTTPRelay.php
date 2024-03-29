<?php
/**
 * Project: HTTPRelay :: HTTP Relay class<br>
 * File:    HTTPRelay.php
 *
 * HTTPRelay 패키지는 HTTP 요청을 간단하게 하거나 또는 HTTP
 * 요청을 다른 서버로 Relay를 하기 위한 기능을 제공한다.
 *
 * 예제:
 * {@example HTTPRelay/tests/test.php}
 *
 * @category    HTTP
 * @package     HTTPRelay
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 2018, OOPS.org
 * @license     BSD License
 * @link        http://pear.oops.org/package/HTTPRelay
 * @since       File available since release 0.0.1
 * @filesource
 */

/**
 * import myException API
 */
@require_once 'myException.php';
HTTPRelay_REQUIRES ();

/**
 * HTTPRelay 패키지는 HTTP 요청을 간단하게 하거나 또는 HTTP
 * 요청을 다른 서버로 Relay를 하기 위한 기능을 제공한다.
 *
 * 예제:
 * {@example HTTPRelay/tests/test.php}
 *
 * @package HTTPRelay
 */
Class HTTPRelay {
	// {{{ properities
	/**
	 * Default User Agent
	 */
	const UAGENT = 'OOPS HTTP Relay Wrapper 1.0';
	/**#@+
	 * @access public
	 */
	/**
	 * 에러 발생시에 에러 메시지 저장
	 * @var string
	 */
	static public $error = '';
	/**
	 * HTTP 요청 정보 결과 저장
	 *
	 * 1.0.5 부터는 30x 반환시에 redirect를 내부 처리를 하며,
	 * 이 경우, $info는 2차 배열로 referer의 info 정보를 보관한다.
	 *
	 * @var array
	 */
	public $info = null;
	/**
	 * Post data encoding type
	 *
	 * Post data를 어떤 방식으로 인코딩 할지를 결정한다.
	 * 'url-encode'와 'form-data' 둘 중의 하나로 지정을 해야 한다.
	 * Post data에 파일 전송을 하기 위해서는 무조건 'form-data'로
	 * 지정을 해야 한다.
	 *
	 * @var string
	 */
	public $posttype = 'url-encode';
	/**
	 * print debug messages to stderr
	 *
	 * 디버그 메시지를 stderr로 출력한다.
	 *
	 * @var array
	 */
	public $debug = false;
	/**#@-*/
	/**
	 * User Define Header
	 *
	 * 사용자 Request header를 배열로 지정한다.
	 * 배열 키는 Header Name으로 지정하며, 배열값에는 Header의 값을 지정한다.
	 * 배열값을 '-'으로 지정을 하면, 빈 값으로 처리를 한다.
	 *
	 * {@example HTTPRelay/tests/test-relay.php 30 1}
	 *
	 * @access private
	 * @var array
	 */
	private $header = array ();
	/**
	 * Number of recursive calls
	 *
	 * redirect 시에 무한 루프를 방지하기 위한 counter
	 *
	 * @access private
	 * @var integer
	 */
	private $reno = 0;
	// }}}

	// {{{ +-- public __construct ($header = null)
	/**
	 * HTTPRelay 초기화
	 *
	 * 예제:
	 * {@example HTTPRelay/tests/test-relay.php 23 6}
	 *
	 * @access public
	 * @param array $header (optional) 사용자 정의 HTTP Header
	 *   - Key는 Header 이름이어야 하며 '-'는 '_'로 표기한다.
	 *   - Heder값이 없어야 할 경우에는 값을 '-'로 넣어준다.
	 *   - Key값에 POSTTYPE을 지정할 경우, Post encoding 방식을
	 *     직접 지정이 가능하다. 이 경우 POSTTYPE은 HTTP Header
	 *     에 영향을 주지 않으며, 값으로는 'form-data'와 'url-encode'
	 *     중에 지정할 수 있다. POSTTYPE은 제거될 예정이니 $posttype
	 *     property를 직접 설정하여야 한다.
	 *
	 */
	function __construct ($header = null) {
		$this->error = &self::$error;

		// deprecated soon
		if ( $header['POSTTYPE'] ) {
			switch ($header['POSTTYPE']) {
				case 'form-data' :
				case 'json' :
				case 'url-encode' :
					$this->posttype = $header['POSTTYPE'];
			}
			unset ($header['POSTTYPE']);
		}
		// deprecated soon

		if ( is_array ($header) )
			$this->header = &$header;
	}
	// }}}

	// {{{ +-- public (stdClass) head ($to, $tmout = 60, $httphost = '', $recursion = false)
	/**
	 * HTTP/1.1 HEAD 요청
	 *
	 * 예제:
	 * {@example HTTPRelay/tests/test-head.php}
	 *
	 * @access public
	 * @return stdClass 
	 * @param  string $to        요청할 URL
	 * @param  int    $tmout     (optional) timeout 값
	 * @param  string $httphost  (optional) HTTP/1.1 Host Header. 지정을 하지 않을 경우
	 *                           $to의 도메인으로 지정됨
	 * @param  boolean $recursion redirec 처리를 위한 재귀 호출 구분 변수 (1.0.5)
	 * @since 1.0.2
	 */
	public function head ($to, $tmout = 60, $httphost = '', $recursion = false) {
		$to = trim ($to);
		if ( ! $to ) {
			self::$error = 'Empty request url';
			return false;
		}

		if ( ! is_resource ($c = curl_init ()) ) {
			self:$error = 'Failed initialize';
			return false;
		}

		$this->header['Host'] = self::http_host ($to, $httphost);

		if ( $this->debug ) {
			fprintf (STDERR, "** Request URL  : %s\n", $to);
			fprintf (STDERR, "** Request Host : %s\n", $this->header['Host']);
		}

		# header information
		$header = self::http_header ();

		if ( $this->debug )
			fprintf (STDERR, "** Header SET   :\n%s\n", print_r ($header, true));

		curl_setopt ($c, CURLOPT_URL, $to);
		curl_setopt ($c, CURLOPT_TIMEOUT, $tmout);
		curl_setopt ($c, CURLOPT_NOPROGRESS, 1);
		curl_setopt ($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt (
			$c, CURLOPT_USERAGENT,
			$this->header['User_Agent'] ? $this->header['User_Agent'] : self::UAGENT
		);
		curl_setopt ($c, CURLOPT_HEADER, 1);
		curl_setopt ($c, CURLOPT_NOBODY, 1);
		curl_setopt ($c, CURLOPT_HTTPHEADER, $header);
		curl_setopt ($c, CURLOPT_FAILONERROR, 1);
		curl_setopt ($c, CURLINFO_HEADER_OUT, 1);
		if ( preg_match ('/^https:/', $to) )
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

		$data = curl_exec ($c);
		$info = $this->set_return_info ($c);

		if ( curl_errno ($c) ) {
			self::$error = curl_error ($c);
			curl_close ($c);
			return false;
		}

		curl_close ($c);

		if ( $recursion === true )
			return trim ($data);

		if ( $info->http_code < 400 && $info->redirect_url ) {
			unset ($data);
			$info->call_method = __FUNCTION__;
			if ( ($data = $this->redirect_call ($info, $tmout, $httphost)) === false )
				return false;
		}

		$r = new stdClass;
		$content = preg_split ('/\r\n/', trim ($data));
		foreach ( $content as $v ) {
			$v = trim ($v);
			if ( preg_match ('!^HTTP/([0-9.]+) ([0-9]+) (.*)!', $v, $matches) ) {
				$r->{'VERSION'} = $matches[1];
				$r->{'RETURN-CODE'} = $matches[2];
				$r->{'RETURN-MSG'} = $matches[3];
				continue;
			}
			$tmp = preg_split ('/:[\s]+/', $v);
			$r->{$tmp[0]} = $tmp[1];
		}

		return $r;
	}
	// }}}

	// {{{ +-- public (string) fetch ($to, $tmout = 60, $httphost = '', $post = null, $recursion = false)
	/**
	 * HTML 요청의 결과를 반환한다.
	 *
	 * 예제:
	 * {@example HTTPRelay/tests/test.php}
	 *
	 * @access public
	 * @return string
	 * @param  string $to        요청할 URL
	 * @param  int    $tmout     (optional) timeout 값
	 * @param  string $httphost  (optional) HTTP/1.1 Host Header. 지정을 하지 않을 경우
	 *                           $to의 도메인으로 지정됨
	 * @param  array  $post      (optional) Post방식으로 요청시 전송할 Post data
	 * @param  boolean $recursion redirec 처리를 위한 재귀 호출 구분 변수 (1.0.5)
	 */
	public function fetch ($to, $tmout = 60, $httphost = '', $post = null, $recursion = false) {
		if ( ! $recursion )
			$this->reno = 0;

		$to = trim ($to);
		if ( ! $to ) {
			self::$error = 'Empty request url';
			return false;
		}

		if ( ! is_resource ($c = curl_init ()) ) {
			self:$error = 'Failed initialize';
			return false;
		}

		$this->header['Host'] = self::http_host ($to, $httphost);

		if ( $this->debug ) {
			fprintf (STDERR, "** Request URL  : %s\n", $to);
			fprintf (STDERR, "** Request Host : %s\n", $this->header['Host']);
		}

		# header information
		$header = self::http_header ();

		curl_setopt ($c, CURLOPT_URL, $to);
		curl_setopt ($c, CURLOPT_TIMEOUT, $tmout);
		curl_setopt ($c, CURLOPT_NOPROGRESS, 1);
		curl_setopt ($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt (
			$c, CURLOPT_USERAGENT,
			$this->header['User_Agent'] ? $this->header['User_Agent'] : self::UAGENT
		);
		curl_setopt ($c, CURLOPT_HEADER, 0);
		curl_setopt ($c, CURLOPT_NOBODY, 0);
		curl_setopt ($c, CURLOPT_FAILONERROR, 1);
		curl_setopt ($c, CURLINFO_HEADER_OUT, 1);
		if ( preg_match ('/^https:/', $to) )
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

		if ( $post ) {
			curl_setopt ($c, CURLOPT_POST, 1);
			if ( is_array ($post) ) {
				if ( $this->posttype == 'json' ) {
					self::set_header ($header, 'Content-Type', 'application/json');
					$post = json_encode ($post);
				} else if ( $this->posttype == 'url-encode' )
					$post = http_build_query ($post);
			} else {
				// strings are json format, change Content-Type to 'application/json'
				if ( json_decode ($post) != NULL )
					self::set_header ($header, 'Content-Type', 'application/json');
			}
			curl_setopt ($c, CURLOPT_POSTFIELDS, $post);
		}

		curl_setopt ($c, CURLOPT_HTTPHEADER, $header);

		if ( $this->debug )
			fprintf (STDERR, "** Header SET   :\n%s\n", print_r ($header, true));

		$data = curl_exec ($c);
		$info = $this->set_return_info ($c);

		if ( curl_errno ($c) ) {
			self::$error = curl_error ($c);
			curl_close ($c);
			return false;
		}

		curl_close ($c);

		if ( $info->http_code < 400 && $info->redirect_url ) {
			unset ($data);
			$info->call_method = __FUNCTION__;
			if ( ($data = $this->redirect_call ($info, $tmout, $httphost, $post)) === false )
				return false;
		}

		return trim ($data);
	}
	// }}}

	// {{{ +-- public (string) relay ($to, $tmout = 60, $httphost = '', $recursion = false)
	/**
	 * HTML 요청을 다른 호스트로 중계를 한다.
	 *
	 * 예제:
	 * {@example HTTPRelay/tests/test.php}
	 *
	 * @access public
	 * @return string
	 * @param  string $to        중계할 URL
	 * @param  int    $tmout     (optional) timeout 값
	 * @param  string $httphost  (optional) HTTP/1.1 Host Header. 지정을 하지 않을 경우
	 *                           $to의 도메인으로 지정됨
	 * @param  boolean $recursion redirec 처리를 위한 재귀 호출 구분 변수 (1.0.5)
	 */
	public function relay ($to, $tmout = 60, $httphost = '', $recursion = false) {
		$to = trim ($to);
		if ( ! $to ) {
			self::$error = 'Empty request url';
			return false;
		}

		if ( ! is_resource ($c = curl_init ()) ) {
			self:$error = 'Failed initialize';
			return false;
		}


		$this->header['Host'] = self::http_host ($to, $httphost);

		if ( $this->debug ) {
			fprintf (STDERR, "** Request URL  : %s\n", $to);
			fprintf (STDERR, "** Request Host : %s\n", $this->header['Host']);
		}

		# basic information
		$uri = trim ($_SERVER['QUERY_STRING']);

		if ( $uri )
			$uri = '?' . $uri;

		# header information
		$header = self::http_header ();

		if ( $this->debug )
			fprintf (STDERR, "** Header SET   :\n%s\n", print_r ($header, true));

		curl_setopt ($c, CURLOPT_URL, $to . $uri);
		curl_setopt ($c, CURLOPT_TIMEOUT, $tmout);
		curl_setopt ($c, CURLOPT_NOPROGRESS, 1);
		curl_setopt ($c, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt (
			$c, CURLOPT_USERAGENT,
			$this->header['User_Agent'] ? $this->header['User_Agent'] : self::UAGENT
		);
		curl_setopt ($c, CURLOPT_HEADER, 0);
		curl_setopt ($c, CURLOPT_NOBODY, 0);
		curl_setopt ($c, CURLOPT_HTTPHEADER, $header);
		curl_setopt ($c, CURLOPT_FAILONERROR, 1);
		curl_setopt ($c, CURLINFO_HEADER_OUT, 1);
		if ( preg_match ('/^https:/', $to) )
			curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);

		self::relay_post ($c);

		$data = curl_exec ($c);
		$info = $this->set_return_info ($c);

		if ( curl_errno ($c) ) {
			self::$error = curl_error ($c);
			curl_close ($c);
			return false;
		}

		curl_close ($c);

		if ( $info->http_code < 400 && $info->redirect_url ) {
			unset ($data);
			$info->call_method = __FUNCTION__;
			if ( ($data = $this->redirect_call ($info, $tmout, $httphost)) === false )
				return false;
		}

		return trim ($data);
	}
	// }}}

	// {{{ +-- private (mixed) redirect_call (void)
	/**
	 * @access private
	 * @return mixed
	 */
	private function redirect_call () {
		if ( ++$this->reno > 9 ) {
			self::$error = 'Overflows 10 times redirectioins!';
			return false;
		}

		$arg = func_get_args ();

		$to = &$arg[0]->redirect_url;
		$narg = array ($to, $arg[1], $arg[2]);
		if ( $arg[0]->call_method == 'fetch' ) {
			$narg[3] = $arg[3];
			$narg[4] = true;
		} else
			$narg[3] = true;

		if ( $this->debug )
			fprintf (STDERR, "\n** Redirect Host: %s\n", $to);

		$this->header['Referer'] = $arg[0]->url;

		$callback_func = array ($this, $arg[0]->call_method);
		unset ($arg);
		if ( ($data = call_user_func_array ($callback_func, $narg)) === false )
			return false;

		return $data;
	}
	// }}}

	// {{{ +-- private (stdClass) set_return_info ($c)
	/**
	 * @access private
	 * @return stdClass
	 */
	private function set_return_info ($c) {
		if ( ! is_resource ($c) )
			return new stdClass;

		$info = curl_getinfo ($c);

		if ( $this->reno == 0 )
			$this->info = $info;
		else if ( $this->reno == 1 ) {
			$old = $this->info;
			unset ($this->info);
			$this->info[0] = $old;
			$this->info[1] = $info;
		} else
			$this->info[$this->reno] = $info;

		return (object) $info;
	}
	// }}}

	// {{{ +-- private (array) http_header (void)
	/**
	 * 사용자 정의 헤더와 기본 헤더를 셋팅 한다.
	 *
	 * @access private
	 * @return array
	 */
	private function http_header () {
		foreach ( $this->header as $name => $val ) {
			if ( $name == 'User_Agent' )
				continue;

			self::set_header ($header, $name, $val);
		}

		if ( ! isset ($this->header['Expect']) )
			self::set_header ($header, 'Expect', '-');
			
		if ( ! $this->header['Accept_Language'] )
			self::set_header ($header, 'Accept-Language', 'ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4');

		if ( ! $this->header['Connection'] )
			self::set_header ($header, 'Connection', 'close');

		self::client_ip_set ($header);

		return $header;
	}
	// }}}

	// {{{ +-- private (void) set_header (&$h, $d, $v)
	/**
	 * 헤더를 셋팅한다. 헤더 값이 '-'로 지정이 될 경우, 해당 헤더는
	 * 빈 값을 가지는 헤더로 처리를 한다.
	 *
	 * @access private
	 * @return void
	 * @param array 헤더를 저장할 변수
	 * @param string 헤더 이름
	 * @param string 헤더 값. '-' 값을 가지면, 빈 값을 가지는 헤더라는 의미로 처리된다.
	 */
	private function set_header (&$h, $d, $v) {
		if ( ! trim ($d) || ! trim ($v) )
			return;

		$d = @trim ($d);
		$v = @trim ($v);

		if ( $v == '-' )
			$v = '';

		$d = preg_replace ('/_/', '-', $d);

		if ( sizeof ($h) > 0 ) {
			foreach ( $h as $key => $val ) {
				if ( preg_match ("/^{$d}:\s+/", $val) ) {
					$h[$key] = $d . ': ' . $v;
					return;
				}
			}
		}

		$h[] = $d . ': ' . $v;
	}
	// }}}

	// {{{ +-- private (string) http_host ($t, $h)
	/**
	 * HTTP/1.1 Host header에서 사용할 hostname을 구한다.
	 * Hostname (2번째 인자)가 따로 지정되지 않으면 주어진 Full URL(1번째 인자)
	 * 에서 hostname을 구한다.
	 *
	 * @access private
	 * @return string
	 * @param string   전체 URL  
	 * @param string   HTTP/1.1 Host header 값
	 */
	private function http_host ($t, $h = '') {
		if ( ! $h) {
			$src = array ('!^https?://!i', '!/.*!');
			$dsc = array ('', '');
			$h = preg_replace ($src, $dsc, $t);
		}

		return $h;
	}
	// }}}

	// {{{ +-- private (void) relay_post (&$c)
	/**
	 * Post로 넘어온 Data를 relay하기 위한 post data template
	 *
	 * @access private
	 * @return void
	 * @param resource CURL resource
	 */
	private function relay_post (&$c) {
		if ( $_SERVER['REQUEST_METHOD'] != 'POST' )
			return;

		$post = array ();

		if ( is_array ($_FILES['file']) ) {
			$post['file'] = "@{$_FILES['file']['tmp_name']}";
			$this->posttype = 'form-data';
		}
		$post = array_merge ($post, $_POST);

		if ( $this->posttype == 'url-encode' )
			$post = http_build_query ($post);

		curl_setopt ($c, CURLOPT_POST, 1);
		curl_setopt ($c, CURLOPT_POSTFIELDS, $post);
	}
	// }}}

	// {{{ +-- private (void) client_ip_set (&$h, $ip = null)
	/**
	 * Relay를 위한 Client IP 주소 설정
	 *
	 * @access private
	 * @return void
	 * @param array 헤더 저장 변수
	 * @param string (optional) IP 주소
	 */
	private function client_ip_set (&$h, $ip = null) {
		if ( php_sapi_name () == 'cli' )
			return;

		if ( $ip == null )
			$v = $_SERVER['HTTP_X_FORWARDED_FOR'] ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
		else {
			if ( $_SERVER['HTTP_X_FORWARDED_FOR'] )
				$v = $ip . ', ' . $_SERVER['HTTP_X_FORWARDED_FOR'];
			else
				$v = $ip . ', ' . $_SERVER['REMOTE_ADDR'];
		}

		if ( $v )
			self::set_header ($h, 'X-Forwarded-For', $v);
	}
	// }}}
}

// {{{ +-- public HTTPRelay_REQUIRES (void)
/**
 * HTTRelay 패키지에서 필요한 의존성을 검사한다.
 *
 * @access public
 * @return void
 */
function HTTPRelay_REQUIRES () {
	$br = PHP_EOL;
	if ( PHP_SAPI != 'cli' )
		$br = '<br>' . PHP_EOL;
	$brbr = $br . $br;

	if ( ! class_exists ('myException') ) {
		$msg = $brbr .
			'-------------------------------------------------' . $brbr .
			'HTTPRelay ERROR!' . $brbr .
			'HTTPRelay class needs oops/myException pear package' . $br .
			'You can get myException pear package at' . $br .
			'http://mirror.oops.org/pub/oops/php/pear/myException/' . $br .
			'Also you can install with pear command as follow command' . $br .
			'shell> pear channel-discover pear.oops.org' . $br .
			'shell> pear install oops/myException' . $brbr .
			'-------------------------------------------------' . $brbr;
		throw new Exception ($msg, E_USER_ERROR);
	}

	if ( ! extension_loaded ('curl') ) {
		$br = PHP_EOL;
		if ( PHP_SAPI != 'cli' )
			$br = '<br>' . PHP_EOL;
		$brbr = $br . $br;

		$msg = $brbr .
			'-------------------------------------------------' . $brbr .
			'HTTPRelay ERROR!' . $br .
			'HTTPRelay class needs curl extension' . $brbr .
			'-------------------------------------------------' . $brbr;
		throw new myException ($msg, E_USER_ERROR);
	}
}
// }}}

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
