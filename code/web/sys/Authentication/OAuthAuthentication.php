<?php /** @noinspection SpellCheckingInspection */

require_once 'bootstrap.php';
require_once ROOT_DIR . '/CatalogConnection.php';
require_once ROOT_DIR . '/CatalogFactory.php';
require_once ROOT_DIR . '/sys/Authentication/SSOSetting.php';

class OAuthAuthentication extends Action {
	protected $basicAuth;
	protected $state;
	protected $gateway;
	protected $accessToken;
	protected $refreshToken;
	protected $grantType;
	protected $resourceOwner;
	protected $redirectUri;
	protected $matchpoints;
	protected $staffPType;
	protected $staffPTypeAttr;
	protected $staffPTypeAttrValue;
	/** @var CurlWrapper */
	private $curlWrapper;

	protected bool $ssoAuthOnly = false;
	protected bool $updateAccount = false;

	public function __construct() {
		parent::__construct();

		global $library;
		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = "oauth";
		if ($ssoSettings->find(true)) {
			$this->gateway = $ssoSettings->oAuthGateway;
			$this->state = $this->getRandomState();
			$this->basicAuth = $ssoSettings->getBasicAuthToken();
			$this->redirectUri = $ssoSettings->getRedirectUrl();
			$this->matchpoints = $ssoSettings->getMatchpoints();
			$this->grantType = $ssoSettings->getAuthenticationGrantType();
			$this->staffPTypeAttr = $ssoSettings->oAuthStaffPTypeAttr ?? null;
			$this->staffPTypeAttrValue = $ssoSettings->oAuthStaffPTypeAttrValue ?? null;
			$this->updateAccount = $ssoSettings->updateAccount ?? false;

			if($ssoSettings->staffOnly === 1 || $ssoSettings->staffOnly === '1') {
				$this->staffPType = $ssoSettings->oAuthStaffPType;
			}

			if(($ssoSettings->staffOnly === 0 || $ssoSettings->staffOnly === '0') &&
				(!empty($ssoSettings->oAuthStaffPTypeAttr) && !empty($ssoSettings->oAuthStaffPTypeAttrValue)) &&
				($ssoSettings->oAuthStaffPType != '-1' || $ssoSettings->oAuthStaffPType != -1)) {
				$this->staffPType = $ssoSettings->oAuthStaffPType;
			}

			require_once ROOT_DIR . '/sys/Account/AccountProfile.php';
			$accountProfile = new AccountProfile();
			$accountProfile->id = $library->accountProfileId;
			if($accountProfile->find(true)) {
				if($accountProfile->authenticationMethod === 'sso') {
					$this->ssoAuthOnly = true;
				} else {
					$this->ssoAuthOnly = $ssoSettings->ssoAuthOnly;
				}
			}

		} else {
			global $logger;
			$logger->log('No single sign-on settings found for library', Logger::LOG_ALERT);
			echo("Single sign-on settings must be configured to use OAuth 2.0 for user authentication.");
			die();
		}
	}

	protected function getRandomState($length = 32): string {
		return bin2hex(random_bytes($length / 2));
	}

	public function verifyIdToken($payload): array {
		$success = false;
		$error = '';
		$message = '';
		$returnTo = '';

		global $library;

		if (isset($payload['code'])) {
			global $logger;
			$ssoSettings = new SSOSetting();
			$ssoSettings->id = $library->ssoSettingId;
			$ssoSettings->service = "oauth";
			if ($ssoSettings->find(true)) {
				$requestOptions = [
					'client_id' => $ssoSettings->clientId,
					'client_secret' => $ssoSettings->clientSecret,
					'grant_type' => 'authorization_code',
					'code' => $payload['code'],
					'redirect_uri' => $this->redirectUri,
					'access_type' => 'offline',
				];
				$requestToken = $this->getAccessToken($ssoSettings->getAccessTokenUrl(), $requestOptions);
				if (!$requestToken) {
					$logger->log('Error getting access token', Logger::LOG_ERROR);
					return [
						'success' => false,
						'message' => '',
						'error' => 'Did not get expected JSON results from OAuth to get a valid Access Token',
					];
				}

				$resourceOwner = $this->getResourceOwner($ssoSettings->getResourceOwnerDetailsUrl());
				if (!$resourceOwner) {
					$logger->log('Error getting resource owner', Logger::LOG_ERROR);
					return [
						'success' => false,
						'message' => '',
						'error' => "Did not get expected JSON results from OAuth to get Resource Owner details",
					];
				}

				$account = $this->validateAccount();
				if (!$account) {
					$logger->log('Error validating account', Logger::LOG_ERROR);
					return [
						'success' => false,
						'message' => '',
						'error' => "Unable to find and/or register user with provided credentials",
					];
				}

				$success = true;
				$message = 'Successfully logged in using OAuth';
				$returnTo = '/MyAccount/Home';

			} else {
				$error = 'OAuth is not setup for library.';
			}
		} else {
			$error = 'No data from OAuth provided, unable to log into system.';
		}

		return [
			'success' => $success,
			'message' => $success ? $message : $error,
			'returnTo' => $returnTo,
		];
	}

	public function getAccessToken($accessTokenUrl, array $options = [], $returnToken = false) {
		$queryString = $this->buildQueryString($options);
		$url = $this->appendQuery($accessTokenUrl, $queryString);
		$this->initCurlWrapper();
		$response = $this->curlWrapper->curlPostPage($url, '');
		$options = json_decode($response, true);
		if (!empty($options['access_token'])) {
			$this->accessToken = $options['access_token'];
			if (!empty($options['refresh_token'])) {
				$this->refreshToken = $options['refresh_token'];
			}
			if ($returnToken) {
				return $options['access_token'];
			}
			return true;
		}
		return false;
	}

	protected function buildQueryString(array $params): string {
		return http_build_query($params, '', '&', \PHP_QUERY_RFC3986);
	}

	protected function appendQuery($url, $query): string {
		$query = trim($query, '?&');

		if ($query) {
			$glue = strstr($url, '?') === false ? '?' : '&';
			return $url . $glue . $query;
		}

		return $url;
	}

	protected function initCurlWrapper() {
		$this->curlWrapper = new CurlWrapper();
		$this->curlWrapper->timeout = 5;
		$this->curlWrapper->addCustomHeaders([
			"Authorization: Basic $this->basicAuth",
			"Cache-Control: no-cache",
			"Content-Type: application/x-www-form-urlencoded",
		], true);
	}

	private function getResourceOwner($resourceOwnerDetailsUrl): bool {
		$url = $resourceOwnerDetailsUrl . "?access_token=" . $this->accessToken;
		$this->initCurlWrapper();
		$response = $this->curlWrapper->curlGetPage($url);
		$options = json_decode($response, true);
		if (is_array($options)) {
			$this->resourceOwner = $options;
			return true;
		}
		return false;
	}

	private function validateAccount(): bool {
		global $logger;
		if($this->ssoAuthOnly === false) {
			$catalogConnection = CatalogFactory::getCatalogConnectionInstance();

			if ($this->getUserId()) {
				$logger->log('Checking to see if user ' . print_r($this->getUserId(), true) . ' exists in the database...', Logger::LOG_ERROR);
			} else {
				$logger->log('Error searching resource owner for userId', Logger::LOG_ERROR);
				return false;
			}

			$user = $catalogConnection->findNewUser($this->getUserId(), '');

			if (!$user instanceof User) {
				$logger->log('No user found in database... attempting to self-register...', Logger::LOG_ERROR);
				$newUser['email'] = $this->getEmail();
				$newUser['firstname'] = $this->getFirstName();
				$newUser['lastname'] = $this->getLastName();
				$newUser['cat_username'] = $this->getUserId();
				$newUser['ils_barcode'] = $this->getUserId();
				$newUser['category_id'] = null;
				if ($this->staffPType && $this->isStaffUser()) {
					$newUser['category_id'] = $this->staffPType;
				}
				$selfReg = $catalogConnection->selfRegister(true, $newUser);
				if ($selfReg['success'] != '1') {
					//unable to register the user
					$logger->log('Error self registering user ' . print_r($this->getUserId(), true), Logger::LOG_ERROR);
					return false;
				}
				$user = $catalogConnection->findNewUser($this->getUserId(), '');
			} else {
				$user->oAuthAccessToken = $this->accessToken;
				$user->oAuthRefreshToken = $this->refreshToken;
				$user->update();
				if($this->updateAccount) {
					$user->updatePatronInfo(true);
				}
				$user = $catalogConnection->findNewUser($this->getUserId(), '');
			}
			return $this->login($user);
		} else {
			// we only want to authenticate the user via SSO as determined by the account profile
			$user = UserAccount::findNewAspenUser('user_id', $this->getUserId());
			if (!$user instanceof User) {
				$logger->log('No user found in Aspen, creating a new one...', Logger::LOG_ERROR);
				$tmpUser = $this->createNewAspenUser();
				if($tmpUser) {
					$user = UserAccount::findNewAspenUser('user_id', $this->getUserId());
				} else {
					$logger->log('Error creating Aspen user ' . print_r($this->getUserId(), true), Logger::LOG_ERROR);
					return false;
				}
			}
			return $this->login($user);
		}
	}

	private function getUserId() {
		return $this->searchArray($this->resourceOwner, $this->matchpoints['userId']);
	}

	public function searchArray($array, $needle) {
		$result = false;
		foreach ($array as $obj) {
			if (is_array($obj)) {
				foreach ($obj as $n) {
					if (array_key_exists($needle, $n)) {
						$result = $n[$needle];
						break;
					}
				}
			} else {
				if (array_key_exists($needle, $obj)) {
					$result = $obj[$needle];
					break;
				}
			}
		}
		return $result;
	}

	private function getEmail() {
		return $this->searchArray($this->resourceOwner, $this->matchpoints['email']);
	}

	private function getFirstName() {
		return $this->searchArray($this->resourceOwner, $this->matchpoints['firstName']);
	}

	private function getLastName() {
		return $this->searchArray($this->resourceOwner, $this->matchpoints['lastName']);
	}

	private function getStaffAttribute() {
		return $this->searchArray($this->resourceOwner, $this->staffPTypeAttr);
	}

	private function isStaffUser(): bool {
		if($this->staffPTypeAttr && $this->staffPTypeAttrValue) {
			if($this->getStaffAttribute() === $this->staffPTypeAttrValue) {
				return true;
			}
		}
		return false;
	}

	private function createNewAspenUser(): bool {
		global $library;
		$tmpUser = new User();
		$tmpUser->email = $this->getEmail();
		$tmpUser->firstname = $this->getFirstName();
		$tmpUser->lastname = $this->getLastName() ?? '';
		$tmpUser->username = $this->getUserId();
		$tmpUser->unique_ils_id = $this->getUserId();
		$tmpUser->phone = '';
		$tmpUser->displayName = '';
		$tmpUser->patronType = '';
		$tmpUser->trackReadingHistory = false;

		$location = new Location();
		$location->libraryId = $library->libraryId;
		$location->orderBy('isMainBranch desc');
		if (!$location->find(true)) {
			$tmpUser->homeLocationId = 0;
		} else {
			$tmpUser->homeLocationId = $location->code;
		}
		$tmpUser->myLocation1Id = 0;
		$tmpUser->myLocation2Id = 0;
		$tmpUser->created = date('Y-m-d');
		if($tmpUser->insert()) {
			return true;
		}
		return false;
	}

	private function login(User $user): bool {
		if($this->ssoAuthOnly) {
			$login = UserAccount::loginWithAspen($user);
		} else {
			$_REQUEST['username'] = $this->getUserId();
			$_REQUEST['password'] = $user->password;
			$login = UserAccount::login(true);
		}

		if($login) {
			$user->isLoggedInViaSSO = 1;
			$user->update();
			$this->newSSOSession($login->id);
			return true;
		}

		return false;
	}

	private function newSSOSession($id) {
		global $timer;
		/** SessionInterface $session */
		global $session;
		@session_destroy();
		require_once ROOT_DIR . '/sys/Session/MySQLSession.php';
		session_name('aspen_session');
		$session = new MySQLSession();
		$session->init();

		$timer->logTime('Session initialization MySQLSession');

		$_SESSION['activeUserId'] = $id;
		$_SESSION['rememberMe'] = false;
		$_SESSION['loggedInViaSSO'] = true;
	}

	public function getAuthorizationRequestUrl(SSOSetting $settings): string {
		$authorizationUrl = $settings->getAuthorizationUrl();
		$requestOptions = [
			'client_id' => $settings->clientId,
			'response_type' => 'code',
			'redirect_uri' => $this->redirectUri,
			'state' => $this->state,
			'scope' => $settings->getScope(),
		];

		$queryString = $this->buildQueryString($requestOptions);
		return $this->appendQuery($authorizationUrl, $queryString);
	}

	public function refreshAccessToken(): bool {
		global $library;
		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = 'oauth';
		if ($ssoSettings->find(true)) {
			$requestOptions = [
				'client_id' => $ssoSettings->clientId,
				'client_secret' => $ssoSettings->clientSecret,
				'grant_type' => 'refresh_token',
				'refresh_token' => $this->refreshToken,
			];
			if ($this->getAccessToken($ssoSettings->getAccessTokenUrl(), $requestOptions)) {
				return true;
			}
		}
		return false;
	}

	public function logout(): bool {
		global $library;
		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = 'oauth';
		if ($ssoSettings->find(true)) {
			$url = $ssoSettings->getLogoutUrl();
			$url = $url . $this->accessToken;
			$this->initCurlWrapper();
			$this->curlWrapper->curlPostPage($url, '');
			if ($this->curlWrapper->getResponseCode() == 400) {
				return false;
			} else {
				UserAccount::logout();
				return true;
			}
		}
		return false;
	}

	function launch() {}

	function getBreadcrumbs(): array {
		return [];
	}

	protected function getAccessTokenByResourceOwnerCredentials(string $username, string $password, $returnToken = false) {
		global $library;
		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = 'oauth';
		if ($ssoSettings->find(true)) {
			$requestOptions = [
				'client_id' => $ssoSettings->clientId,
				'client_secret' => $ssoSettings->clientSecret,
				'grant_type' => 'password',
				'username' => $username,
				'password' => $password,
				'access_type' => 'offline',
			];
			$queryString = $this->buildQueryString($requestOptions);
			$url = $this->appendQuery($ssoSettings->getAccessTokenUrl(), $queryString);
			$this->initCurlWrapper();
			$response = $this->curlWrapper->curlPostPage($url, '');
			if ($this->curlWrapper->getResponseCode() == 200) {
				$options = json_decode($response, true);
				if (!empty($options['access_token'])) {
					$this->accessToken = $options['access_token'];
					if ($returnToken) {
						return $options['access_token'];
					}
					return true;
				}
			}
		}
		return false;
	}

	protected function getAccessTokenByClientCredentials($returnToken = false) {
		global $library;
		$ssoSettings = new SSOSetting();
		$ssoSettings->id = $library->ssoSettingId;
		$ssoSettings->service = 'oauth';
		if ($ssoSettings->find(true)) {
			$params = [
				'client_id' => $ssoSettings->clientId,
				'client_secret' => $this->createClientSecret($ssoSettings->oAuthPrivateKeys),
				'grant_type' => 'client_credentials',
			];
			$this->initCurlWrapper();
			$response = $this->curlWrapper->curlPostPage($ssoSettings->getAccessTokenUrl(), [
				'query' => $params,
				'timeout' => 10,
				'debug' => true,
			]);
			if ($this->curlWrapper->getResponseCode() == 200) {
				$options = json_decode($response, true);
				if (!empty($options['access_token'])) {
					$this->accessToken = $options['access_token'];
					if ($returnToken) {
						return $options['access_token'];
					}
					return true;
				}
			}
		}
		return false;
	}

	protected function createClientSecret($pkFile): string {
		$pk = openssl_pkey_get_private($pkFile);
		$timestamp = (string)intval(microtime(true) * 1000);
		openssl_private_encrypt($timestamp, $crypttext, $pk);
		return base64_encode($crypttext);
	}
}