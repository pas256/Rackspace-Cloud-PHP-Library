#!/usr/bin/php
<?php
/**
 * Generate the PHP doc for the API Library
 */
system('phpdoc -f RscApi.php -t doc -ti "Rackspace Cloud API PHP Library" -o HTML:Smarty:HandS');
?>
