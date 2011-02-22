<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
require '../jabbakam/jabbakam.inc.php';

if(isset($_GET['oauth_token'])) {
   // construct a request token based on key passed, and secret saved
   $key = $_GET['oauth_token'];
   $secret = $_SESSION['oauth_token_secret'];
   $JK_RequestToken = new JK_RequestToken($key, $secret);

   $User = JK_UserAuthorisation::fromRequestToken($JK_RequestToken);
   print_r($User);

   // save the access key and request
   $_SESSION['oauth_token_key'] = $User->getKey();
   $_SESSION['oauth_token_secret'] = $User->getSecretKey();
   echo '<a href="./account.php">Use the API</a>';

}
else {
   echo "no oauth_token returned<BR>\n";
   exit;
}



