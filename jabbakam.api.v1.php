<?php
  /**
   * 
   **/
require_once dirname(__FILE__).'/OAuth.php'; 


define('CONSUMER_KEY', 'consumer-key');
define('CONSUMER_SECRET', 'shh-its-a-secret');


class JK_API {
   const REQUEST_URI = 'http://www.jabbakam.com/oauth/request_token';
   const AUTHORIZE_URL = 'http://www.jabbakam.com/oauth/authorize';
   const ACCESS_URL = 'http://www.jabbakam.com/oauth/access_token';
   const API_URL = 'http://api.jabbakam.com';


   private $consumer;
   private $sha1_method;
   private $token;


   // headers, response object and raw response from the API
   public $http_headers;
   public $http_response;
   public $http_response_raw;
   public $http_code;
   public $http_info;

   /* Set timeout default. */
   public $timeout = 30;
   /* Set connect timeout. */
   public $connecttimeout = 30; 
   public $useragent = 'JabbakamAPIv1-PHPlib';

   public function __construct($oauth_token = FALSE, $oauth_secret = FALSE) {
      $this->consumer = new OAuthConsumer(CONSUMER_KEY, CONSUMER_SECRET);
      $this->sha1_method = new OAuthSignatureMethod_HMAC_SHA1();
      if ( $oauth_token &&  $oauth_secret) {
         $this->token = new OAuthToken($oauth_token, $oauth_secret);
      } 
      else {
         $this->token = NULL;
      }
   }


   public function get($action, $params = NULL) {
      return $this->request($action, 'GET', $params);
   }
   public function post($action, $params = NULL) {
      return $this->request($action, 'POST', $params);
   }
   private function request($action, $method, $params) {
      $url = self::API_URL.'/'.$action.'.json';

      // if no oauth access tokens, then we need to tell the users to get them
      // (saves an unnecessary call to the API)
      if(is_null($this->token)) {
         throw new JK_API_Exception('No access tokens provided', JK_API_ErrorCodes::$NO_TOKEN, JK_API_ErrorCodes::$NO_HTTP_REQUEST, $url);
         return FALSE;
      }
      $response = $this->OAuthRequest($url, $method, $params);
      return $this->setResponseObject($response);
   }





   function getRequestToken() {
      $request = $this->OAuthRequest(self::REQUEST_URI, 'GET');
      $token = OAuthUtil::parse_parameters($request);
      $this->token = new OAuthConsumer($token['oauth_token'], $token['oauth_token_secret']);
      return $token;
  }
   /**
    * A callback MUST be specified
    **/
   public function getAuthorizeUrl($oauth_token, $oauth_callback) {
      return self::AUTHORIZE_URL.
         '?oauth_token='.$oauth_token.
         '&oauth_callback='.OAuthUtil::urlencode_rfc3986($oauth_callback);
   }
   function getAccessToken() {
      $parameters = array('oauth_token' => $this->token->key);
      $request = $this->OAuthRequest(self::ACCESS_URL, 'GET', $parameters);
      $token = OAuthUtil::parse_parameters($request);
      $this->token = new OAuthToken($token['oauth_token'], $token['oauth_token_secret']);
      return $this->token;
  }

   /**
    * Format and sign an OAuth / API request
    * @return JSON object response from API decoded
    */
  function OAuthRequest($url, $method, $parameters = NULL) {
    if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0) {
      $url = "{$this->host}{$url}.{$this->format}";
    }
    $request = OAuthRequest::from_consumer_and_token($this->consumer, $this->token, $method, $url, $parameters);
    $request->sign_request($this->sha1_method, $this->consumer, $this->token);

    if($method == 'GET') {
       return  $this->http($request->to_url(), 'GET');
    }
    return  $this->http($request->get_normalized_http_url(), $method, $request->to_postdata()); 
  }

  /**
   * Make an HTTP request
   *
   * @return API results
   */
  function http($url, $method, $postfields = NULL) {
    $this->http_info = array();
    $ci = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_USERAGENT, $this->useragent);
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connecttimeout);
    curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
    curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
    curl_setopt($ci, CURLOPT_HEADER, FALSE);

    switch ($method) {
      case 'POST':
        curl_setopt($ci, CURLOPT_POST, TRUE);
        if (!empty($postfields)) {
          curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
        }
        break;
      case 'DELETE':
        curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');
        if (!empty($postfields)) {
          $url = "{$url}?{$postfields}";
        }
    }
    curl_setopt($ci, CURLOPT_URL, $url);

    $response = curl_exec($ci);
    $this->http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    $this->http_info = array_merge($this->http_info, curl_getinfo($ci));
    $this->url = $url;
    curl_close ($ci);
    return $response;
  }

  function getHeader($ch, $header) {
     $i = strpos($header, ':');
     if (!empty($i)) {
        $key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
        $value = trim(substr($header, $i + 2));
        $this->http_header[$key] = $value;
     }
     return strlen($header);
  }


  protected function setResponseObject($response) {
     echo "url = ".$this->url."<BR>\n";
     echo "RESP = $response<BR>\n";

     $this->http_response_raw = $response;
     $this->http_response = json_decode($this->http_response_raw);

     // ensure that the response contains no errors
     $this->checkResponseOk(); 
     return $this->http_response;
  }
  protected function checkResponseOK() {
     // if http code is set to OK, all is OK!
     if($this->http_code == '200') {
        return TRUE;
     }
     // not all is OK, throw a wobbly
     throw new JK_API_Exception(
        $this->http_response->error->description,
        $this->http_response->error->code,  
        $this->http_code,
        $this->url);
  }
}


class JK_API_Exception extends Exception {
   private $http_code;
   private $url;
   
   public function __construct($message, $code, $http_code, $url = NULL) {
      $this->http_code = $http_code;
      $this->url = $url;
      parent::__construct($message, $code);
   }
   
   public function getHttpCode() {
      return $this->http_code;
   }
   public function getUrl() {
      return $this->url;
   }
}

abstract class JK_API_ErrorCodes {
   // errors before calling the API
   public static $NO_HTTP_REQUEST = -100;

   public static $INVALID_FORMAT = 1;
   public static $NO_TOKEN = 2;
   public static $INVALID_SIG = 4;
   public static $UNKOWN_USER = 8;

}