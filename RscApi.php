<?php
/**
 * Rackspace Cloud Server API PHP Library
 *
 * @package RscApi
 */

/**
 * Rackspace Cloud Server API PHP Library
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
	/**
	 * Timeout in seconds for an API call to respond
	 * @var integer
	 */
	private static $TIMEOUT = 10;

	private $serverUrl;
	private $authToken;
	private $authUser;
	private $authKey;
	private $lastResponseStatus;

	/**
	 * Creates a new Rackspace Cloud Servers API object to make calls with
	 *
	 * Your API key needs to be generated using the Rackspace Cloud Management
	 * Console. You can do this under Cloud Files (not Cloud Servers).
	 *
	 * Authentication is done automatically when making the first API call
	 * using this object.
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
	 * Creates an image of the given server
	 *
	 * You can use this method to create custom images. Once your image has
	 * been created, you can build new servers with it.
	 *
	 * @param string $name The name of the image to create
	 * @param integer $serverId The ID of the server to build the image from
	 * @return array Details of the new images (most importantly, it's ID)
	 */
	public function imageCreate($name, $serverId) {
		$url = "/images";

		$data = array(
			"image" => array(
				"serverId" => $serverId,
				"name" => $name,
			),
		);
		$jsonData = json_encode($data);

		$response = $this->makeApiCall($url, $jsonData);
		if (isset($response['image'])) {
			return $response['image'];
		}

		return NULL;
	}

	/**
	 * Gets the details of a specific image
	 *
	 * @param integer $imageId The ID of the image to get the details for
	 * @return array Image details
	 */
	public function imageDetails($imageId) {
		$url = "/images/$imageId";

		$response = $this->makeApiCall($url);
		if (in_array($this->getLastResponseStatus(), array(200, 203))) {
			if (isset($response['image'])) {
				return $response['image'];
			}
		}

		return NULL;
	}

	/**
	 * Get a list of images
	 *
	 * An image is a collection of files you can use to create or rebuild a
	 * server with. There are pre-build ones provided, but you can also create
	 * your own.
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
	 * There are two types of limits enforced by Rackspace Cloud - rate limits
	 * and absolute limits.
	 *
	 * @return array Absolute and rate limits
	 */
	public function limits() {
		$response = $this->makeApiCall("/limits");
		if (in_array($this->getLastResponseStatus(), array(200, 203))
				&& isset($response['limits'])) {
			return $response['limits'];
		}

		return NULL;
	}

	/**
	 * Gets a list of all of the IP addresses (public and private) for the
	 * specified server
	 *
	 * @param integer $serverId The ID of the server to list addresses of
	 * @return array The list of public and private IP addresses
	 */
	public function serverAddressList($serverId) {
		$url = "/servers/$serverId/ips";

		$response = $this->makeApiCall($url);
		if (in_array($this->getLastResponseStatus(), array(200, 203))) {
			if (isset($response['addresses'])) {
				return $response['addresses'];
			}
		}

		return NULL;
	}

	/**
	 * Share an IP Address with another server
	 *
	 * This is a little strange, but the way it works is you pick a public IP
	 * Address from a server in the Shared IP Group. You then pick another
	 * server to share that IP Address with in the same Shared IP Group.
	 *
	 * @param integer $serverId The server to share the IP Address with - the
	 * 		destination, not the source/owner of the address now.
	 * @param string $ipAddress The public IP address to share
	 * @param integer $sharedIpGroupId The ID of the group to share it with
	 * @param boolean $configure (Optional) Set to TRUE to configure and reboot
	 * 		the server to accept the new IP Address
	 * @return boolean TRUE if the IP Address is now shared with $serverId
	 */
	public function serverAddressShare($serverId, $ipAddress, $sharedIpGroupId,
			$configure = FALSE) {
		$data = array(
			"shareIp" => array(
				"sharedIpGroupId" => $sharedIpGroupId,
			),
		);
		if ($configure) {
			$data['shareIp']['configureServer'] = true;
		}
		$jsonData = json_encode($data);

		$url = "/servers/$serverId/ips/public/$ipAddress";
		$this->makeApiCall($url, $jsonData, "put");
		if ($this->getLastResponseStatus() == "202") {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Removes a shared IP Address from the specified server
	 *
	 * @param integer $serverId The server to remove a share the IP Address from
	 * @param string $ipAddress The shared public IP address to remove
	 * @return boolean TRUE if the IP Address is no longer shared
	 */
	public function serverAddressUnshare($serverId, $ipAddress) {
		$url = "/servers/$serverId/ips/public/$ipAddress";
		$this->makeApiCall($url, NULL, "delete");
		if ($this->getLastResponseStatus() == "202") {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Creates a new server
	 *
	 * Keep in mind that servers are created asynchronously. This means that
	 * after this call, your server will be built over time. You can use the
	 * server ID returned from this call to make subsequent calls to get the
	 * build progress status.
	 *
	 * Note that there is currently no support for server metadata.
	 *
	 * @param string $name The friendly name of the server
	 * @param integer $imageId The ID of the image to use
	 * @param integer $flavorId The ID of the hardware config (flavor)
	 * @param integer $sharedIpGroupId (Optional) The ID of the shared IP group
	 * 		to put the new server into (this is the only way you can add a
	 * 		server into a shared IP group)
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
	 * Delete a server, destroying all data on it
	 *
	 * Make sure you want to do this before calling this method. Server deletes
	 * also destroy all images created by that server (strange as it may seem).
	 *
	 * @param integer $serverId The ID of the server to delete
	 * @return boolean TRUE if the server has been deleted
	 */
	public function serverDelete($serverId) {
		$url = "/servers/$serverId";

		$this->makeApiCall($url, NULL, "delete");
		if ($this->getLastResponseStatus() == "202") {
			return TRUE;
		}

		return FALSE;
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
	 * Resize a server - either up or down
	 *
	 * Keep in mind that servers are created asynchronously. This means that
	 * after this call, your server will be resized over time.
	 *
	 * @param integer $serverId The ID of the server to get the details for
	 * @param integer $flavorId The ID of the hardware config (flavor)
	 * @return boolean TRUE if the reboot is underway
	 */
	public function serverResize($serverId, $flavorId) {
		$data = array(
			"resize" => array(
				"flavorId" => $flavorId,
			),
		);
		$jsonData = json_encode($data);

		$url = "/servers/$serverId/action";
		$response = $this->makeApiCall($url, $jsonData);
		if ($this->getLastResponseStatus() == "202") {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Reboot a server
	 *
	 * The default is to do a SOFT reboot, meaning a graceful shutdown and
	 * reboot of the system. You can also do a HARD reboot, which is the
	 * equivalent of taking the power cord out and putting it back in.
	 *
	 * @param integer $serverId The ID of the server to reboot
	 * @param boolean $hardReboot (Optional) Set to TRUE to perform a hard
	 * 		reboot
	 * @return boolean TRUE if the reboot is underway
	 */
	public function serverReboot($serverId, $hardReboot = FALSE) {
		$url = "/servers/$serverId/action";

		$data = array(
			"reboot" => array(),
		);
		if ($hardReboot) {
			$data['reboot']['type'] = "HARD";
		} else {
			$data['reboot']['type'] = "SOFT";
		}
		$jsonData = json_encode($data);

		$this->makeApiCall($url, $jsonData);
		if ($this->getLastResponseStatus() == "202") {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * List the backup schedule for a specific server
	 *
	 * @param integer $serverId The ID of the server to list the schedule for
	 * @return array Backup schedule details
	 */
	public function serverBackupList($serverId) {
		$url = "/servers/$serverId/backup_schedule";

		$response = $this->makeApiCall($url);
		$status = $this->getLastResponseStatus();
		if ($status == "200" || $status == "203") {
			if (isset($response['backupSchedule'])) {
				return $response['backupSchedule'];
			}
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
	 * Translates the HTTP response status from the last API call to a human
	 * friendly message
	 *
	 * @return string The response message from the last call
	 */
	public function getLastResponseMessage() {
		$map = array(
			"200" => "Successful informational response",
			"202" => "Successful action response",
			"203" => "Successful informational response from the cache",
			"204" => "Authentication successful",
			"400" => "Bad request (check the validity of input values)",
			"401" => "Unauthorized (check username and API key)",
			"403" => "Resize not allowed",
			"404" => "Item not found",
			"409" => "Build, backup or resize in process",
			"413" => "Over API limit (check limits())",
			"415" => "Bad media type",
			"500" => "Cloud server issue",
			"503" => "API service in unavailable, or capacity is not available",
		);

		$status = $this->getLastResponseStatus();
		if ($status) {
			return $map[$status];
		}

		return "UNKNOWN - Probably a timeout on the connection";
	}

	/**
	 * Gets the HTTP response status from the last API call
	 *
	 * - 200 - successful informational response
	 * - 202 - successful action response
	 * - 203 - successful informational response from the cache
	 * - 400 - bad request (possibly because the input values were invalid)
	 * - 401 - unauthorized (check username and API key)
	 * - 403 - resize not allowed
	 * - 404 - item not found
	 * - 409 - build, backup or resize in process
	 * - 413 - over API limit (check limits())
	 * - 415 - bad media type
	 * - 500 - cloud server issue
	 * - 503 - API service in unavailable, or capacity is not available
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
				return NULL;
			}
		}

		$this->lastResponseStatus = NULL;

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
		curl_setopt($ch, CURLOPT_TIMEOUT, RscApi::$TIMEOUT);
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
			$this->lastResponseStatus = $matches[1];
			if ($this->lastResponseStatus == "204") {
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