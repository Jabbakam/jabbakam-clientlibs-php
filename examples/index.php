<?php
/**
 * Jabbakam API Example - request
 * Tries to request data from the API. 
 **/
session_start();


error_reporting(E_ALL);
ini_set('display_errors', TRUE);

require '../jabbakam.api.v1.php';


$JK_API = new JK_API();
$request_token = $JK_API->getRequestToken();
// save the temporary token secret
$_SESSION['oauth_token_secret'] = $request_token['oauth_token_secret'];
$url = $JK_API->getAuthorizeUrl($request_token['oauth_token'], 'http://test.jk.dev/client_libraries/PHP/examples/callback.php');
?>
<html>
<head>
<title>Demo of Jabbakam API PHP Client Library</title>
</head>
<body>
<h1>You now have to go to Jabbakam to Login</h1>
<p><a href="<?php echo $url;?>">Redirect to Authenticate</a></p>
</body>
</html>
