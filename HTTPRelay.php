<?php
/**
 * Project: HTTPRelay :: HTTP Relay class<br>
 * File:    HTTPRelay.php
 *
 * HTTPRelay 패키지는 HTTP 요청을 간단하게 하거나 또는 HTTP
 * 요청을 다른 서버로 Relay를 하기 위한 기능을 제공한다.
 *
 * @category    HTTP
 * @package     HTTPRelay
 * @author      JoungKyun.Kim <http://oops.org>
 * @copyright   (c) 1997-2013 OOPS.org
 * @license     BSD License
 * @version     SVN: $Id$
 * @link        http://pear.oops.org/package/HTTPRelay
 * @since       File available since release 0.0.1
 * @filesource
 */

HTTPRelay_REQUIRES ();

/**
 * HTTPRelay 패키지는 HTTP 요청을 간단하게 하거나 또는 HTTP
 * 요청을 다른 서버로 Relay를 하기 위한 기능을 제공한다.
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
	 * 에러 메시지 저장
	 * @var string
	 */
	static public $error = '';
	/**
	 * HTTP 요청 결과 저장
	 * @var array
	 */
	public $info = null;
	/**#@-*/
	/**
	 * User Define Header
	 *
	 * @access private
	 * @var array
	 */
	private $header = array ();
	// }}}

	// {{{ +-- public __construct ($header = null)
	/**
	 * HTTPRelay 초기화
	 *
	 * @access public
	 * @param array $header 사용자 정의 HTTP Header
	 */
	function __construct ($header = null) {
		$this->error = &self::$error;
		if ( is_array ($header) )
			$this->header = &$header;
	}
	// }}}

	// {{{ +-- public (string) fetch ($to, $tmout = 60, $httphost = '', $post = null)
	/**
	 * HTML 요청의 결과를 반환한다.
	 *
	 * @access public
	 * @return string
	 * @param  string $to        요청할 URL
	 * @param  int    $tmout     timeout 값
	 * @param  string $httphost  HTTP/1.1 Host Header. 지정을 하지 않을 경우
	 *                           $to의 도메인으로 지정됨
	 * @param  array  $post      Post방식으로 요청시 전송할 Post data
	 */
	public function fetch ($to, $tmout = 60, $httphost = '', $post = null) {
		if ( ! trim ($to) )
			return '';

		if ( ! is_resource ($c = curl_init ()) )
			return '';

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
		curl_setopt ($c, CURLOPT_HTTPHEADER, $header);
		curl_setopt ($c, CURLOPT_FAILONERROR, 1);

		if ( $post && is_array ($post) ) {
			curl_setopt ($c, CURLOPT_POST, 1);
			if ( $post['type'] != 'form-data' ) {
				unset ($post['type']);
				$post = http_build_query ($post);
			} else
				unset ($post['type']);
			curl_setopt ($c, CURLOPT_POSTFIELDS, $post);
		}

		$data = curl_exec ($c);

		if ( curl_errno ($c) ) {
			self::$error = curl_error ($c);
			return false;
		}

		$this->info = curl_getinfo ($c);
		curl_close ($c);

		return trim ($data);
	}
	// }}}

	// {{{ +-- public (string) relay ($to, $tmout = 60, $httphost = '')
	/**
	 * HTML 요청을 다른 호스트로 중계를 한다.
	 *
	 * @access public
	 * @return string
	 * @param  string $to        중계할 URL
	 * @param  int    $tmout     timeout 값
	 * @param  string $httphost  HTTP/1.1 Host Header. 지정을 하지 않을 경우
	 *                           $to의 도메인으로 지정됨
	 */
	public function relay ($to, $tmout = 60, $httphost = '') {
		if ( ! trim ($to) )
			return '';

		if ( ! is_resource ($c = curl_init ()) )
			return '';

		# basic information
		$uri = trim ($_SERVER['QUERY_STRING']);

		if ( $uri )
			$uri = '?' . $uri;

		# header information
		$header = self::http_header ();

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

		self::relay_post ($c);

		$data = curl_exec ($c);

		if ( curl_errno ($c) ) {
			self::$error = curl_error ($c);
			return false;
		}

		$this->info = curl_getinfo ($c);
		curl_close ($c);

		return trim ($data);
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

			self::set_header ($header, $name);
		}

		if ( ! $this->header['Host'] )
			self::set_header ($header, 'Host', self::http_host ($to, $httphost));

		if ( ! isset ($this->header['Expect']) )
			self::set_header ($header, 'Expect', '-');
			
		if ( ! $this->header['Accept_Language'] )
			self::set_header ($header, 'Accept-Language', 'ko-KR,ko;q=0.8,en-US;q=0.6,en;q=0.4');

		if ( ! $this->header['Connection'] )
			self::set_header ($header, 'Connection', 'close');

		if ( $relay ) {
		}
		self::client_ip_set ($header);

		return $header;
	}
	// }}}

	// {{{ +-- private (void) set_header (&$h, $d, $v, $noval = false)
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
	private function set_header (&$h, $d, $v, $noval = false) {
		if ( ! trim ($d) || ! trim ($v) )
			return;

		$d = @trim ($d);
		$v = @trim ($v);

		if ( $v == '-' )
			$v = '';

		$d = preg_replace ('/_/', '-', $d);

		$h[] = $d . ': ' . $v;
	}
	// }}}

	// {{{ +-- private (string) http_host ($t, $h)
	/**
	 * HTTP/1.1 Host header에서 사용할 hostname을 구한다.
	 * Hostname (2번째 인자)가 따로 지정되지 않으면 주어진 Full URL(1번째 인자)
	 * 에서 hostname을 구한다.
	 *
	 * @acces private
	 * @return string
	 * @param string   전체 URL  
	 * @param string   HTTP/1.1 Host header 값
	 */
	private function http_host ($t, $h) {
		if ( ! $h) {
			$src = array ('!^http://!', '!/.*!');
			$dsc = array ('', '');
			$h = preg_replace ($src, $dsc, $t);
		}

		return $h;
	}
	// }}}

	// {{{ +-- public (void) relay_post (&$c)
	/**
	 * Post로 넘어온 Data를 relay하기 위한 post data template
	 *
	 * @access private
	 * @return void
	 * @param resource CURL resource
	 */
	function relay_post (&$c) {
		if ( $_SERVER['REQUEST_METHOD'] != 'POST' )
			return;

		$post = array ();

		$posttype = 'url-encode';
		if ( is_array ($_FILES['file']) ) {
			$post['file'] = "@{$_FILES['file']['tmp_name']}";
			$posttype = 'form-data';
		}
		$post = array_merge ($post, $_POST);

		if ( $posttype == 'url-encode' )
			$post = http_build_query ($post);

		curl_setopt ($c, CURLOPT_POST, 1);
		curl_setopt ($c, CURLOPT_POSTFIELDS, $post);
	}
	// }}}

	// {{{ +-- private (void) client_ip_set (&$h, $ip = null)
	/**
	 * Relay를 위한 Client IP 주소 설정
	 *
	 * @acces private
	 * @return void
	 * @param array 헤더 저장 변수
	 * @param string IP 주소
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
function HTTPRelay_REQUIRES () {
	if ( ! extension_loaded ('curl') ) {
		$br = PHP_EOL;
		if ( PHP_SAPI != 'cli' )
			$br = '<br>' . PHP_EOL;
		$brbr = $br . $br;

		$msg = sprintf (
			$brbr .
			'-------------------------------------------------' . $brbr .
			'HTTPRelay ERROR!' . $brbr .
			'HTTPRelay class needs curl extension' . $brbr .
			'-------------------------------------------------' . $brbr
		);
		throw new Exception ($msg, E_USER_ERROR);
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
