<?php
/** Core Jabbakam API Client File.
*
* @package jabbakam
* @version 1.0
* @author Pete
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version 2.0
*/

/* Copyright 2009-2010 Jabbakam Ltd

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License. */

/* Check dependencies */
if( ! function_exists('curl_init') ){
  trigger_error('Jabbakam Library requires CURL support - http://www.php.net/manual/en/curl.installation.php', 	E_USER_ERROR);
}
if(! function_exists('simplexml_load_string') ){
  trigger_error('Jabbakam Library requires SimpleXML support - http://www.php.net/manual/en/simplexml.installation.php', E_USER_ERROR);
}

/* Set library path */
if(! isset($JK_PATH) ){
  $JK_PATH=realpath(dirname(__FILE__)).DIRECTORY_SEPARATOR;
}

/** Load in the config file */
require_once $JK_PATH.'config.php';

/** Load the OAuth library */
require_once $JK_PATH.'OAuth.php';

/** Base class that provides static utility methods.
*
*
* @package jabbakam
* */
final class Jabbakam{

  const API_URL='http://api.jabbakam.com/';

  const REQUEST_URL='http://www.jabbakam.com/oauth/request_token';

  const AUTHORIZE_URL='http://www.jabbakam.com/oauth/authorize';

  const ACCESS_TOKEN_URL='http://www.jabbakam.com/oauth/access_token';

  const VERSION=1.0;

  /** @var object $usr_auth An instance of OAuthToken.*/
  private static $user_auth=NULL;

  /** @var resource Curl Handle.*/
  private static $ch=NULL;

  /** @var object OAuthConsumer.*/
  private static $consumer=NULL;

  /** @var object OAuthSignatureMethod_HMAC_SHA1 initialised by init() */
  private static $sig_method;

  /** @internal Prevent initialisation.*/
  private function __construct(){
    // intentionally empty
  }


  /** Fetches an authorisation request token.
  *
  * This is the first stage of the authorisation process.
  *
  * @param int|string $user_id An _optional_ property that identifies the user on the local system, that $request_token was used for.
  * @return JK_RequestToken
  *
  * @throws Exception Unable to connect to API server
  * @throws JK_Exception API server is unable or unwilling to issue a request token
  * @throws OAuthException
  *
  * @see JK_RequestToken::send()
  * */
  public final static function requestAuthorisation($user_id=NULL){
     $params = NULL;
    if(self::$consumer==NULL){
      self::$consumer=new OAuthConsumer(JABBAKAM_KEY,JABBAKAM_SECRET_KEY, NULL);
    }
    $sig_method=new OAuthSignatureMethod_HMAC_SHA1();
    $req=OAuthRequest::from_consumer_and_token(self::$consumer, NULL, 'GET', self::REQUEST_URL, $params);
    $req->sign_request($sig_method, self::$consumer, NULL);

    if(self::$ch==NULL){
      self::_initCurl();
    }

    curl_setopt(self::$ch, CURLOPT_URL, $req->to_url());
    $answer=curl_exec(self::$ch);
    if(! $answer){
      throw new Exception('Could not fetch request token - '.curl_error(self::$ch));
    }
    if(curl_getinfo(self::$ch, CURLINFO_HTTP_CODE)!=200){
      throw new JK_Exception('No request token returned: '.$answer);
    }

    $answer=OAuthUtil::parse_parameters($answer);

    global $JK_PATH;
    require_once $JK_PATH.'requesttoken.class.php';
    return new JK_RequestToken($answer['oauth_token'], $answer['oauth_token_secret'], $answer['xoauth_token_ttl'], $user_id);
  }

  private function _initCurl(){
    self::$ch=curl_init();
    curl_setopt(self::$ch, CURLOPT_HEADER, 0);
    curl_setopt(self::$ch, CURLOPT_RETURNTRANSFER, TRUE);
  }

  /** Initialisates the API for use with a specific user.
  *
  * MUST be called before using any of the API methods.
  *
  *
  * @param object $user_authorisation JK_UserAuthorisation instance.
  * @throws InvalidArgumentException If $user_authorisation is NULL or not an instance of JK_UserAuthorisation.
  * */
  public static final function init(JK_UserAuthorisation $user_authorisation){
    if($user_authorisation==NULL){
      throw new InvalidArgumentException('NULL $user_authoristion passed to Jabbakam::init()');
    }
    if(! is_a($user_authorisation, 'JK_UserAuthorisation') ){
      throw new InvalidArgumentException('non JK_UserAuthorisation passed to Jabbakam::init()');
    }
    if(self::$ch==NULL){
      self::_initCurl();
    }


    self::$user_auth=new OAuthToken($user_authorisation->getKey(), $user_authorisation->getSecretKey());
    self::$consumer=new OAuthConsumer(JABBAKAM_KEY,JABBAKAM_SECRET_KEY, NULL);
    self::$sig_method=new OAuthSignatureMethod_HMAC_SHA1();
  }


  /** Sends a request to the Jabbakam API.
  *
  * You don't normally need to use this method directly - use the method of the individual Jababkam object classes.
  *
  * @param string $object_name
  * @param string $method
  * @param array $params Optional associative array of parameters
  * @param bool $user_post If we make a POST request.  This is FALSE by default so me make a GET request.
  *
  * @return object An instance of SimpleXMLElement.
  * @link http://www.php.net/manual/en/class.simplexmlelement.php
  *
  * @throws Exception If Jabbakam::init() Hasn't been called previously.
  * @throws Exception If unable to contact the API server.
  * @throws JK_Exception If there is an error returned by the API server.
  *
  * @see Jabbakam::init()
  * @see JK_Object::call()
  * */
  public final static function call($object_name, $method, $params=NULL, $use_post=FALSE){
    if(self::$user_auth==NULL){
      throw new Exception('Illegal State: Jabbakam::call() called before init() has been called');
    }
    $sig_method=new OAuthSignatureMethod_HMAC_SHA1();
    $url=self::API_URL.$object_name.'/'.$method.'.xml';
    $http_method=$use_post ? 'POST' : 'GET';
    $req=OAuthRequest::from_consumer_and_token(self::$consumer, self::$user_auth ,$http_method , $url, $params);
    $req->sign_request($sig_method, self::$consumer, self::$user_auth);

    if(! $use_post){
      curl_setopt(self::$ch, CURLOPT_POST, FALSE);
      curl_setopt(self::$ch, CURLOPT_HTTPGET, TRUE);
      curl_setopt(self::$ch, CURLOPT_URL, $req->to_url());
    }
    else{
      curl_setopt(self::$ch, CURLOPT_URL, $url);
      curl_setopt(self::$ch, CURLOPT_POST, TRUE);
      curl_setopt(self::$ch, CURLOPT_POSTFIELDS, $req->to_postdata());
    }

    $answer=curl_exec(self::$ch);

    if(! $answer){
      throw new Exception('Could not contact API server - '.curl_error(self::$ch), curl_errno(self::$ch));
      return FALSE;
    }

    if(curl_getinfo(self::$ch, CURLINFO_HTTP_CODE)!=200){
      try{
        $xml=@simplexml_load_string($answer);
        if($xml) :
          $error=$xml->error;
          $errno=(int) $error->attributes()->code;
          $err=(string) $error;
        else :
          $err='Unknown Error: '.$answer;
          $errno=0;
        endif;
      }
      catch(Exception $e){
        throw new Exception('Bad XML: '.$e->getMessage().' - '.$answer);
      }
      throw new JK_Exception($err, $errno);
    }

    try{
      $xml=simplexml_load_string($answer);
      if(!$xml){
        throw new Exception('Bad XML');
        return FALSE;
      }
      return $xml;
    }
    catch (Exception $e){
      throw new Exception('Bad XML: '.$e->getMessage());
    }

  }

}


/** Jabbakam API Exception class.
*
*  @package jabbakam */
class JK_Exception extends Exception{


}


/** Base class that all Jabbakam objects inherit from.
*
*  @package jabbakam
*  @abstract
* */
abstract class JK_Object{

  /** @ignore
  * @var string Name of this object for API calls.*/
  protected $_name='';

  /** Calls The Jabbakam API.
  *
  *
  * @param string $method
  * @param array $params
  * @param bool $use_post
  *
  * @uses Jabbakam::call()
  * @uses $name
  * */
  protected function call($method, $params=NULL, $use_post=FALSE){
    return Jabbakam::call($this->_name, $method, $params, $use_post);
  }

  /** Takes an XML element attribute and converts to a boolean.
  *
  *
  * @param object $element A SimpleXMLElement instance.
  * @param string $arrtibute The attibute of the element to use, default is value.
  * @return bool
  *
  * @final
  * */
  protected static final function attrib2bool(SimpleXMLElement $element, $attribute='value'){
    $att=(string) $element->attributes()->$attribute;
    return $att=='true';
  }

  /** Chomps a string.
  *
  * @param string &$str The string to chomp.
  * @param int $length It's maximum length.
  * */
  protected static final function chomp(& $str, $length=100){
    if($str==NULL){
      return;
    }
    if(strlen($str)>$length ){
      $str=substr($str, 0, $length);
    }
  }

  /** Converts a boolean value to a string.
  *
  *
  * @param bool $val.
  * @return string
  * */
  protected static final function bool2str($val){
    return $val ? 'true' : 'false';
  }

  /** Extracts a response code from an XML response.
  *
  *
  * @param object &$xml SimpleXMLElement instance containing the whole XML response.
  * @return int|bool The respnonse code or FALSE if none present.*/
  protected final function responseCode(& $xml){
    if($response=$xml->response){
      return (int) $response->attributes()->code;
    }
    return FALSE;
  }

}

/** Represents authorisation from a user for a 3rd party application to use the Jabbakam API.
*
* For each user in your system, you will need to request authorisation (once) before using the API on behalf of that user.
*
* After recieving authorisation for a user, you should store the token, for example in your user database table.
*
* @package jabbakam
* @see Jabbakam::requestAuthorisation()
* @see JK_RequestToken
* */
final class JK_UserAuthorisation{

  private $key;
  private $secret_key;
  private $user_id=NULL;


  /** Create a user authorisation token
  *
  * This will be from data stored locally (e.g. in a database) for a previously aquired authorisation.
  *
  * @param string $key The public key part of the token.
  * @param string $secret_key The secret key part of the token.
  * @param int|string $user_id An _optional_ property that identifies the user on the local system, e.g. the user.id from your user database table.
  *
  * @see fromRequestToken()
  * */
  public function __construct($key, $secret_key, $user_id=NULL){
    if($key==NULL || $secret_key==NULL){
      throw new InvalidArgumentException('NULL parameter passed to JK_UserAuthorisation constructor');
    }
    $this->key=$key;
    $this->secret_key=$secret_key;
    $this->user_id=$user_id;
  }

  /** Fetches a access authorisation token after a request token has been granted authorisation by the user.
  *
  * This is the 3rd and final step of the OAuth authorisation process.
  *
  * @param object $request_token Instance of JK_RequestToken
  * @param int|string $user_id An _optional_ property that identifies the user on the local system, that $request_token was used for.
  * @return object JK_UserAuthorisation instance
  *
  * @see Jabbakam::requestAuthorisation()
  *
  * @throws InvalidArgumentException If $request_token is NULL or not an instance of JK_RequestToken.
  * @throws Exception If unable to contact the API server.
  * @throws JK_Exception If Jabbakam is unable or unwilling to grant an access token, e.g. expired request token.
  * */
  public static final function fromRequestToken(JK_RequestToken $request_token, $user_id=NULL){
    if($request_token==NULL){
      throw new InvalidArgumentException('NULL $request_token passed to JK_UserAuthorisation::fromRequestToken()');
    }
    if(! is_a($request_token, 'JK_RequestToken') ){
      throw new InvalidArgumentException('non JK_RequestToken passed to JK_UserAuthorisation::fromRequestToken()');
    }

    $consumer=new OAuthConsumer(JABBAKAM_KEY,JABBAKAM_SECRET_KEY, NULL);
    $sig_method=new OAuthSignatureMethod_HMAC_SHA1();
    $req=OAuthRequest::from_consumer_and_token($consumer, $request_token, 'GET', Jabbakam::ACCESS_TOKEN_URL);
    $req->sign_request($sig_method, $consumer, $request_token);

    $ch=curl_init();
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_URL, $req->to_url());
    $answer=curl_exec($ch);

    if(! $answer){
      $err=curl_error($ch);
      $err_no=curl_errno($ch);
      curl_close($ch);
      throw new Exception('Could not fetch acess token - '.$err, $err_no);
    }
    if(curl_getinfo($ch, CURLINFO_HTTP_CODE)!=200){
      curl_close($ch);
      throw new JK_Exception('No access token returned: '.$answer);
    }

    curl_close($ch);
    echo $answer."\n";
    $answer=OAuthUtil::parse_parameters($answer);
    return new JK_UserAuthorisation($answer['oauth_token'], $answer['oauth_token_secret'], $user_id);
  }

  /** @return string The key for this authorisation token.*/
  public function getKey(){
    return $this->key;
  }

  /** @return string The secret key for this authorisation token.*/
  public function getSecretKey(){
    return $this->secret_key;
  }

  /** Returns a *local* identifier for the user this authorisation is for.
  *
  * This is NOT the user's Jabbakam user id.
  * May return NULL
  *
  * @return mixed|null
  * */
  public function getUserId(){
    return $this->user_id;
  }
}
