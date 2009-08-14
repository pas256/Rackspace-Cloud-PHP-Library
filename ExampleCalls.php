<?php
/**
 * Example API calls using the Rackspace Cloud API PHP Library
 */

require_once "RscApi.php";

// Account settings
define("API_USER", "someUser");
define("API_KEY",  "abc123def456abc78900000000000000");


// Create an instance of the library
$rsc = new RscApi(API_USER, API_KEY);

// Check how we are going with the API limits that are enforced
$apiLimits = $rsc->limits();
var_dump($apiLimits);



?>