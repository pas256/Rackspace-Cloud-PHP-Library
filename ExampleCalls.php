<?php
/**
 * Example API calls using the Rackspace Cloud Server API PHP Library
 *
 * For full documentation, please see the PHPdoc in the doc directory.
 *
 * The examples below are ones that will not cause a chargable action to be
 * performed, so feel free to run this to test.
 */

require_once "RscApi.php";

// Change below to your account settings
define("API_USER", "someUser");
define("API_KEY",  "abc123def456abc78900000000000000");


// Create an instance of the library
$rsc = new RscApi(API_USER, API_KEY);

// Check how we are going with the API limits that are enforced
$apiLimits = $rsc->limits();

// Display response of API call
var_dump($apiLimits);
var_dump($rsc->getLastResponseStatus());
var_dump($rsc->getLastResponseMessage());


// Get a detailed list of your servers
$myServers = $rsc->serverList(TRUE);
var_dump($myServers);


// List available hardware configurations (flavors) and images
$flavors = $rsc->flavorList();
$images = $rsc->imageList();
var_dump($flavors);
var_dump($images);

?>