<?php
session_start();


error_reporting(E_ALL);
ini_set('display_errors', TRUE);

require '../jabbakam.api.v1.php';

$oauth_token = isset($_GET['oauth_token']) ? $_GET['oauth_token'] : FALSE;
if( ! $oauth_token) {
   echo "No oauth token passed. oh no<BR>\n";
   exit;
}


$JK_API = new JK_API($oauth_token, $_SESSION['oauth_token_secret']);
$token = $JK_API->getAccessToken();
echo "Token = <pre>"; print_r($token); echo "</pre>";
$_SESSION['oauth_token'] = $token->key;
$_SESSION['oauth_token_secret'] = $token->secret;
?>
<html>
<head>
<title>Demo of Jabbakam API PHP Client Library</title>
</head>
<body>
<h1>Now, start using the API</h1>
<p><a href="./account.php">Redirect to API requests</a></p>
</body>
</html>
