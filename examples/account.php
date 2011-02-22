<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', TRUE);
require '../jabbakam/jabbakam.inc.php';


$key = $_SESSION['oauth_token_key'];
$secret = $_SESSION['oauth_token_secret'];

$User = new JK_UserAuthorisation($key, $secret);
Jabbakam::init($User); 
echo "Key = $key, Secret = $secret<BR>\n";
echo "<hr />\n";
$account = JK_Account::load();
var_dump($account);
echo "<hr />\n";
$camera_list = JK_Camera::getList();
var_dump($camera_list);

