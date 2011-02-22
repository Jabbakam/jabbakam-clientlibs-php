<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', TRUE);

require '../jabbakam/jabbakam.inc.php';

$JK = Jabbakam::requestAuthorisation();
print_r($JK);


if( isset($JK->key) && isset($JK->secret) ) {
   // save the token secret for use later
   $_SESSION['oauth_token_secret'] = $JK->secret;

   // NOTE: use $JK->send() instead, I am just outputting so I can see the constructed URL
   $url = $JK->getAuthURL();
   echo 'URL = <a href="'.$url.'">'.$url.'</a><BR>';
}
else {
   echo "Failed to get request token<BR>";
}
