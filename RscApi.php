<?php
/**
 * Rackspace Cloud API PHP Library
 *
 * @package RscApi
 */

/**
 * Rackspace Cloud API PHP Library
 *
 * The Rackspace Cloud has released an API for their Cloud Servers which you can
 * find below
 *
 * The Cloud Server API is currently in beta, and does not have any libraries
 * available to simplify making calls, so I have created this PHP one.
 *
 * This is strictly for PHP5 since PHP4 should be forgotten forever.
 *
 * The API has some poorly written documentation, but it is still useful (link
 * below).
 *
 * @link http://www.rackspacecloud.com/cloud_hosting_products/servers/api
 * @link http://docs.rackspacecloud.com/servers/api/cs-devguide-latest.pdf
 *
 * @package RscApi
 */
class RscApi {
	private $serverUrl;
	private $authToken;
	private $authUser;
	private $authKey;
	private $lastResponseStatus;

	/**
	 * Creates a new Rackspace Cloud API object
	 *
	 * Your API key needs to be generated using the Rackspace Cloud Management
	 * Console. You can do this under Cloud Files (not Cloud Servers).
	 *
	 * @param string $user The username of the account to use
	 * @param string $key The API key to use
	 */
	public function __construct($user, $key) {
		$this->authUser = $user;
		$this->authKey = $key;

		$this->serverUrl = NULL;
		$this->authToken = NULL;
	}

	/**
	 * Get a list of flavors (available hardware configurations)
	 *
	 * A flavor is a hardware configuration for a server. Each flavor has a
	 * a different combination of disk space, memory capacity and priority for
	 * CPU time.
	 *
	 * @param boolean $detailed If TRUE, get detailed config information
	 * @return array A list of flavor details comprising of ID, name, RAM and
	 * 		disk information
	 */
	public function flavorList($detailed = FALSE) {
		$url = "/flavors";
		if ($detailed) {
			$url .= "/detail";
		}

		$response = $this->makeApiCall($url);
		if (isset($response['flavors'])) {
			return $response['flavors'];
		}

		return NULL;
	}

	/**
	 * Get a list of images
	 *
	 * @param boolean $detailed If TRUE, get detailed image information
	 * @return array Image details
	 */
	public function imageList($detailed = FALSE) {
		$url = "/images";
		if ($detailed) {
			$url .= "/detail";
		}

		$response = $this->makeApiCall($url);
		if (isset($response['images'])) {
			return $response['images'];
		}

		return NULL;
	}

	/**
	 * Gets the API call limits for this account
	 *
	 * @return array Absolute and rate limits
	 */
	public function limits() {
		$response = $this->makeApiCall("/limits");
		if (isset($response['limits'])) {
			return $response['limits'];
		}

		return NULL;
	}

	/**
	 * Share an IP Address with another server
	 *
	 * Caution - work in progress
	 *
	 * @param integer $serverId (unsure - possibly the server to
	 * @param string $ipAddress The IP address to share
	 * @param integer $sharedIpGroupId The ID of the group to share it with
	 * @param boolean $configure
	 * @return boolean TRUE if it worked
	 */
	public function serverAddressShare($serverId, $ipAddress, $sharedIpGroupId,
			$configure = FALSE) {
		$url = "/servers/$serverId/ips/public/$ipAddress";

		$data = array(
			"shareIp" => array(
				"sharedIpGroupId" => $sharedIpGroupId,
			),
		);
		if ($configure) {
			$data['shareIp']['configureServer'] = "true";
		}
		$jsonData = json_encode($data);

		$response = $this->makeApiCall($url, $jsonData, "put");

		return TRUE;
	}

	/**
	 * Creates a new server
	 *
	 * @param string $name The friendly name of the server
	 * @param integer $imageId The ID of the image to use
	 * @param integer $flavorId The ID of the hardware config (flavor)
	 * @param integer $sharedIpGroupId (Optional) The ID of the shared IP group to put
	 * 		the new server into
	 * @return array New server details including the generated root password
	 */
	public function serverCreate($name, $imageId, $flavorId,
			$sharedIpGroupId = NULL) {
		$data = array(
			"server" => array(
				"name" => $name,
				"imageId" => $imageId,
				"flavorId" => $flavorId,
			),
		);
		if ($sharedIpGroupId) {
			$data['server']['sharedIpGroupId'] = $sharedIpGroupId;
		}
		$jsonData = json_encode($data);

		$url = "/servers";
		$response = $this->makeApiCall($url, $jsonData);
		if (isset($response['server'])) {
			return $response['server'];
		}

		return NULL;

	}

	/**
	 * Gets the details of a specific server
	 *
	 * @param integer $serverId The ID of the server to get the details for
	 * @return array Server details
	 */
	public function serverDetails($serverId) {
		$url = "/servers/$serverId";

		$response = $this->makeApiCall($url);
		if (isset($response['server'])) {
			return $response['server'];
		}

		return NULL;
	}

	/**
	 * Get a list of servers associated with this account
	 *
	 * @param boolean $detailed If TRUE, get detailed server information
	 * @return array Server details
	 */
	public function serverList($detailed = FALSE) {
		$url = "/servers";
		if ($detailed) {
			$url .= "/detail";
		}

		$response = $this->makeApiCall($url);
		if (isset($response['servers'])) {
			return $response['servers'];
		}

		return NULL;
	}

	/**
	 * Creates a new shared IP group
	 *
	 * @param string $name The friendly name of the group
	 * @param integer $serverId (Optional) The ID of the server to add to the group
	 * @return array Details of the new group
	 */
	public function sharedIpGroupCreate($name, $serverId = NULL) {
		$data = array(
			"sharedIpGroup" => array(
				"name" => $name,
			),
		);
		if ($serverId) {
			$data['sharedIpGroup']['server'] = $serverId;
		}
		$jsonData = json_encode($data);

		$url = "/shared_ip_groups";
		$response = $this->makeApiCall($url, $jsonData);
		if (isset($response['sharedIpGroup'])) {
			return $response['sharedIpGroup'];
		}

		return NULL;
	}

	/**
	 * Gets details about a specific shared IP group
	 *
	 * @param integer $sharedIpGroupId The ID of the group to get details for
	 * @return array Group details
	 */
	public function sharedIpGroupDetails($sharedIpGroupId) {
		$url = "/shared_ip_groups/$sharedIpGroupId";

		$response = $this->makeApiCall($url);
		if (isset($response['sharedIpGroup'])) {
			return $response['sharedIpGroup'];
		}

		return NULL;
	}


	/**
	 * Get a list of all shared IP groups
	 *
	 * @param boolean $detailed If TRUE, get detailed shared IP group information
	 * @return array Shared IP group details
	 */
	public function sharedIpGroupList($detailed = FALSE) {
		$url = "/shared_ip_groups";
		if ($detailed) {
			$url .= "/detail";
		}

		$response = $this->makeApiCall($url);
		if (isset($response['sharedIpGroups'])) {
			return $response['sharedIpGroups'];
		}

		return NULL;
	}

	/**
	 * Gets the HTTP response status from the last API call
	 *
	 * @return integer The 3 digit HTTP response status, or NULL if the call had
	 * 		issues
	 */
	public function getLastResponseStatus() {
		return $this->lastResponseStatus;
	}

	/**
	 * Makes a call to the API
	 *
	 * @param string $url The relative URL to call (example: "/server")
	 * @param string $postData (Optional) The JSON string to send
	 * @param string $method (Optional) The HTTP method to use
	 * @return array The parsed response, or NULL if there was an error
	 */
	private function makeApiCall($url, $postData = NULL, $method = NULL) {
		// Authenticate if necessary
		if (!$this->isAuthenticated()) {
			if (!$this->authenticate()) {
				die("ERROR: Could not authenticate");
			}
		}

		$jsonUrl = $this->serverUrl . $url . ".json";
		$httpHeaders = array(
			"X-Auth-Token: {$this->authToken}",
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $jsonUrl);
		if ($postData) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
			$httpHeaders[] = "Content-Type: application/json";
		}
		if ($method) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
		}
		curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array(&$this, 'parseHeader'));
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$jsonResponse = curl_exec($ch);
		curl_close($ch);

		return json_decode($jsonResponse, TRUE);
	}

	/**
	 * Curl call back method to parse header values one by one (there will be
	 * many)
	 *
	 * @param resource $ch The Curl handler
	 * @param string $header The HTTP header line to parse
	 * @return integer The number of bytes in the header line
	 */
	private function parseHeader($ch, $header) {
		preg_match("/^HTTP\/1\.[01] (\d{3}) (.*)/", $header, $matches);
        if (isset($matches[1])) {
            $this->lastResponseStatus = $matches[1];
        }

        return strlen($header);
	}

	/**
	 * Determines if authentication has been complete
	 *
	 * @return boolean TRUE if authentication is complete, FALSE if it needs to
	 * 		be done
	 */
	private function isAuthenticated() {
		return ($this->serverUrl && $this->authToken);
	}

	/**
	 * Authenticates with the API
	 *
	 * @return boolean TRUE if the authentication was successful
	 */
	private function authenticate() {
		$authUrl = "https://auth.api.rackspacecloud.com/v1.0";
		$authHeaders = array(
			"X-Auth-User: {$this->authUser}",
			"X-Auth-Key: {$this->authKey}",
		);

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $authUrl);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $authHeaders);
		curl_setopt($ch, CURLOPT_HEADER, TRUE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$response = curl_exec($ch);
		curl_close($ch);

		preg_match("/^HTTP\/1\.[01] (\d{3}) (.*)/", $response, $matches);
		if (isset($matches[1])) {
			$responseStatus = $matches[1];
			if ($responseStatus == "204") {
				preg_match("/X-Server-Management-Url: (.*)/", $response,
						$matches);
				$this->serverUrl = trim($matches[1]);

				preg_match("/X-Auth-Token: (.*)/", $response, $matches);
				$this->authToken = trim($matches[1]);

				return TRUE;
			}
		}

		return FALSE;
	}
}
?>