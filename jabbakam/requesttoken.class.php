<?php
/**
* @package jabbakam
* @version 1.0
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license 2.0 http://www.opensource.org/licenses/apache2.0.php Apache License Version
*/


/** An authorisation request token.
*
* @see Jabbakam::requestAuthorisation()
* @package jabbakam
* */
final class JK_RequestToken extends OAuthToken {

  /** @var int Time To Live - how long the token is valid for in seconds.*/
  public $ttl;

  /** @var int|string $user_id An _optional_ property that identifies the user on the local system, that this request token was used for. Default is NULL.*/
  public $user_id;

  public function __construct($key, $secret, $ttl=3600, $user_id=NULL){
    parent::__construct($key, $secret);
    $this->ttl=$ttl;
    $this->user_id=$user_id;
  }

  /** Sends the request token.
  *
  * This will redirect the browser to the Jabbakam website so the user can log-in and grant authorisation, and the script will end.
  * After the user has logged in and granted your application access, they will be taken back to JABBAKAM_CALLBACK_URL as defined in config.php.
  * At this point you can retrieve an access token.
  *
  *
  * Must be called before any HTML is outputted.
  *
  * This is the 2nd part of the OAuth authorisation process.
  *
  * @throws Exception If HTTP headers have already been sent and we can't issue the redirect.
  * @uses getAuthURL()
  * @see JK_UserAuthorisation::fromRequestToken()
  * */
  public function send(){
    if(headers_sent() ){
      throw new Exception('HTTP headers already sent');
    }
    $auth_url=$this->getAuthURL();
    header("Location: $auth_url");
    echo "Please continue to $auth_url";
    exit(0);
  }

  /** Returns the URL for this token that the user should be sent to in order to login and grant access permission.
  *
  * @return string
  * @see send()
  * */
  public function getAuthURL(){
    return Jabbakam::AUTHORIZE_URL. "?oauth_token={$this->key}&oauth_callback=".urlencode(JABBAKAM_CALLBACK_URL);
  }


}
