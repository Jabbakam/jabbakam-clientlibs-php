<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', TRUE);

$key = isset($_SESSION['oauth_token']) ? $_SESSION['oauth_token'] : FALSE;
$secret = isset($_SESSION['oauth_token_secret']) ? $_SESSION['oauth_token_secret'] : FALSE;

// if oauth token has not been set, make the user get them
if( ! $key && ! $secret) {
   header('Location: ./request.php');
   exit;
}


require '../jabbakam.api.v1.php';
?>
<html>
<head>
<title>Demo of Jabbakam API PHP Client Library</title>
</head>
<body>
<?php
try {
   echo '<h3>Init the API</h3>';
   $API = new JK_API($key, $secret);
   echo '<h3>Get Account Details</h3>\n';
   $account = $API->get('account/details');
   var_dump($account); 
   echo '<h3>Get latest video clip</h3>';
   $items = $API->get('item/get_list', array('qty' => 1));
   var_dump($items->items[0]);

   echo '<h3>Change label of the atest clip found above</h3>';
   $resp = $API->post('item/update', 
                      array(
                         'item_id' => $items->items[0]->item_id,
                         'label' => 'Changed via the API'
                         )
      );
   var_dump($resp);
}
catch(JK_API_Exception $e) {
   echo "Error: ".$e->getMessage()."<BR>\n".
      "Code: ".$e->getCode()."<BR>\n".
      "HTTP Code: ".$e->getHttpCode()."<BR>\n";
}
?>
</body>
</html>