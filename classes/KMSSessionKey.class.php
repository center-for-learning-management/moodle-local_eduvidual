<?php
class KMSSessionKey
{
	const EXTRA_USER_INFO_PAIR_SEPARATOR = ',';
	const EXTRA_USER_INFO_KEY_VALUE_SEPARATOR = ':';
	const SESSION_KEY_INFO_SEPARATOR = ';';
	const SESSION_SIGNATURE_INFO_SEPARATOR = '|';

	public $sessionKey;
	public $strInfo;
	public $userId;
	public $role;
	public $expiry;
	public $extraUserInfo = array();
	private $isValid = false;
	private $secret;

	function __construct($hashedString, $secret)
	{
		$this->sessionKey = $hashedString;
		$this->secret = $secret;

		$this->crackSessionKey();

		if($this->expiry >= time()) $this->isValid = true;
	}

	private function crackSessionKey()
	{
		$decodedString = base64_decode($this->sessionKey);
		if($decodedString === false) return; // could not base64 decode the string, isValid stays false

		$parts = explode(self::SESSION_SIGNATURE_INFO_SEPARATOR, $decodedString);
		if(count($parts) != 2) return; // unexpected content in base64 decoded string, isValid stays false

		$originalSignature = $parts[0];
		$this->strInfo = $parts[1];
		$sig = self::createSignature($this->secret, $this->strInfo);
		if($sig != $originalSignature) return; //signature not valid - spoof attempt, isValid stays false

		$info = explode(self::SESSION_KEY_INFO_SEPARATOR, $this->strInfo);
		$this->userId = $info[0];
		$this->role = $info[1];
		$extraUserInfo = explode(self::EXTRA_USER_INFO_PAIR_SEPARATOR, $info[2]);
		foreach($extraUserInfo as $pair)
		{
			list($key, $value) = explode(self::EXTRA_USER_INFO_KEY_VALUE_SEPARATOR, $pair);
			$this->extraUserInfo[$key] = $value;
		}
		$this->expiry = $info[3];
	}

	public function getIsValid()
	{
		return $this->isValid;
	}

	private static function createSignature($salt, $info)
	{
		return sha1($salt.$info);
	}

	/**
	 * example of function to create hashed string.
	 *
	 * @param string $userId
	 * @param string $role
	 * @param int $expiry seconds to expiry from time()
	 * @param array $extraUserInfo associative-key array of key-value pairs of user info
	 *
	 * @return string a hashed session key
	 *
	*/
	public static function createSessionKey($userId, $role, $secret, $expiry = 5, $extraUserInfo = array())
	{
		$rand = rand(0,32000);
		$strExtraUserInfo = '';
		$expiryTime = time()+$expiry;
		foreach($extraUserInfo as $key => $value)
		{
			$strExtraUserInfo .= $key.self::EXTRA_USER_INFO_KEY_VALUE_SEPARATOR.$value.self::EXTRA_USER_INFO_PAIR_SEPARATOR;
		}
		$strExtraUserInfo = rtrim($strExtraUserInfo, self::EXTRA_USER_INFO_PAIR_SEPARATOR);

		$fields = array(
			$userId,
			$role,
			$strExtraUserInfo,
			$expiryTime,
			$rand,
		);

		$strInfo = implode(self::SESSION_KEY_INFO_SEPARATOR, $fields);

		$salt = $secret;

		$signature = self::createSignature($salt, $strInfo);

		$stringToHash = $signature.self::SESSION_SIGNATURE_INFO_SEPARATOR.$strInfo;

		$hashedString = base64_encode($stringToHash);

		return $hashedString;

	}
}
