<?php

abstract class CM_Request_Abstract {
	/**
	 * @var string
	 */
	protected $_path;

	/**
	 * @var array
	 */
	protected $_query = array();

	/**
	 * @var array
	 */
	protected $_headers = array();

	/**
	 * @var CM_Model_User|null
	 */
	protected $_viewer = false;

	/**
	 * @var CM_DeviceCapabilities
	 */
	private $_capabilities;

	/**
	 * @var CM_Session
	 */
	private $_session;
	/**
	 * @var int|null
	 */
	private $_sessionId;
	/**
	 * @var CM_Request_Abstract
	 */
	private static $_instance;

	/**
	 * @param string				   $uri
	 * @param array|null			   $headers OPTIONAL
	 * @param CM_Model_User|null	   $viewer
	 */
	public function __construct($uri, array $headers = null, CM_Model_User $viewer = null) {
		if (is_null($headers)) {
			$headers = array();
		}
		if (false === ($this->_path = parse_url($uri, PHP_URL_PATH))) {
			throw new CM_Exception_Invalid('Cannot detect path from `' . $uri . '`.');
		}

		if (false === ($queryString = parse_url($uri, PHP_URL_QUERY))) {
			throw new CM_Exception_Invalid('Cannot detect query from `' . $uri . '`.');
		}
		parse_str($queryString, $this->_query);

		$this->_headers = array_change_key_case($headers);

		$sessionId = $_COOKIE['sessionId'];
		if ($sessionId) {
			$this->_sessionId = $sessionId;
		}

		self::$_instance = $this;
	}

	/**
	 * @return CM_DeviceCapabilities
	 */
	public function getDeviceCapabilities() {
		if (!isset($this->_capabilities)) {
			$userAgent = '';
			if ($this->hasHeader('user-agent')) {
				$userAgent = $this->getHeader('user-agent');
			}
			$this->_capabilities = new CM_DeviceCapabilities($userAgent);
		}
		return $this->_capabilities;
	}

	/**
	 * @return array
	 */
	public final function getHeaders() {
		return $this->_headers;
	}

	/**
	 * @param string $name
	 * @return string
	 * @throws CM_Exception_Invalid
	 */
	public final function getHeader($name) {
		$name = strtolower($name);
		if (!$this->hasHeader($name)) {
			throw new CM_Exception_Invalid('Header `' . $name . '` not set.');
		}
		return (string) $this->_headers[$name];
	}

	/**
	 * @return string
	 */
	public final function getPath() {
		return $this->_path;
	}

	/**
	 * @param string $path
	 * @return CM_Request_Abstract
	 */
	public function setPath($path) {
		$this->_path = (string) $path;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getQuery() {
		return $this->_query;
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	public function setQueryParam($key, $value) {
		$key = (string) $key;
		$value = (string) $value;
		$this->_query[$key] = $value;
	}

	/**
	 * @return CM_Session
	 */
	public function getSession() {
		if (!$this->_session) {
			try {
				$this->_session = new CM_Session($this->_sessionId);
			} catch (CM_Exception_Nonexistent $ex) {
				$this->_session = new CM_Session();
			}
			$this->_session->start();
		}
		return $this->_session;
	}

	/**
	 * @return boolean
	 */
	public function hasSession() {
		return ($this->_session || $this->_sessionId);
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name) {
		$name = strtolower($name);
		return isset($this->_headers[$name]);
	}

	/**
	 * @param bool $needed OPTIONAL Throw an CM_Exception_AuthRequired if not authenticated
	 * @return CM_Model_User|null
	 * @throws CM_Exception_AuthRequired
	 */
	public function getViewer($needed = false) {
		if ($this->_viewer === false) {
			if ($this->hasSession()) {
				$this->_viewer = $this->getSession()->getUser();
			}
		}
		if (!$this->_viewer) {
			if ($needed) {
				throw new CM_Exception_AuthRequired();
			}
			return null;
		}
		return $this->_viewer;
	}

	/**
	 * @return int|false
	 */
	public function getIp() {
		if (IS_TEST || IS_DEBUG) {
			$ip = CM_Config::get()->testIp;
		} else {
			if (!isset($_SERVER['REMOTE_ADDR'])) {
				return false;
			}
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		$long = sprintf('%u', ip2long($ip));
		if (0 == $long) {
			return false;
		}
		return $long;
	}

	/**
	 * @return bool
	 */
	public function getIpBlocked() {
		$ip = $this->getIp();
		if (!$ip) {
			return false;
		}
		$blockedIps = new CM_Paging_Ip_Blocked();
		return $blockedIps->contains($ip);
	}

	/**
	 * @return bool
	 */
	public static function hasInstance() {
		return isset(self::$_instance);
	}

	/**
	 * @return CM_Request_Abstract
	 */
	public static function getInstance() {
		if (!self::hasInstance()) {
			throw new CM_Exception_Invalid('No request set');
		}
		return self::$_instance;
	}
}
