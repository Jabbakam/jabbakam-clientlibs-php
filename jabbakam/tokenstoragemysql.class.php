<?php
/** MySQL Token Storage Helper class.
*
* @package jabbakam
* @version 1.0
* @author Pete
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license 2.0 http://www.opensource.org/licenses/apache2.0.php Apache License Version
*/

/** Make sure we have the core file loaded.*/
if(! class_exists('Jabbakam') ){
  require_once 'core.php';
}

/** Load the interface definition.*/
require_once $JK_PATH.'tokenstorage.interface.php';

/** Class that stores Jabbakam OAuth tokens in a mySQL database.
*
* Uses (and depends on) the mySQLi library.
*
* <code>
* CREATE TABLE `jabbakam_tokens` (
* `type` ENUM( 'request', 'access' ) NOT NULL ,
* `user_id` INT UNSIGNED NOT NULL ,
* `key` VARCHAR( 64 ) NOT NULL ,
* `secret` CHAR( 32 ) NOT NULL ,
* `expires` INT UNSIGNED NOT NULL DEFAULT '0' COMMENT 'UNIX time UTC',
* PRIMARY KEY ( `type` , `user_id` )
* ) ENGINE = MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci COMMENT = 'Holds Jabbakam API OAuth tokens';
*
* ALTER TABLE `jabbakam_tokens` ADD INDEX ( `type` , `key` ) ;
*
* </code>
*
* @package jabbakam
* @link http://www.php.net/manual/en/book.mysqli.php
*
* */
class JK_TokenStorageMySQL implements JK_TokenStorage{

  /** @var string The database table name enclosed in backtick quotes.*/
  protected $table='`jabbakam_tokens`';

  /** @var object MySQLi connection.
  * @private */
  private $conn;

  /** Creates an initialises a mysql toekn storage  helper.
  *
  * @param MySQLi &$connection An instance of MySQLi.
  *
  * @link http://www.php.net/manual/en/class.mysqli.php
  * @throws InvalidArgumentException If $connection is NULL or not an instance of MySQLi
  * */
  public function __construct(MySQLi & $connection){
    if($connection==NULL){
      throw new InvalidArgumentException('NULL connection passed to JK_TokenStorageMySQL constructor');
    }
    if(! is_a($connection, 'MySQLi') ){
      throw new InvalidArgumentException('Non mysqli connection object passed to JK_TokenStorageMySQL constructor');
    }
    $this->conn=$connection;
  }

  /** Cleans the user id for use in a query.
  *
  * If you need to use string user id's you should overide this method in a child class.
  *
  * @param int $user_id
  * @return string
  * */
  protected function cleanUserId($user_id){
    return $this->conn->real_escape_string($user_id);
  }

  /** Performs an database query.
  *
  * @param string $sql
  * @return object|bool
  * @throws Exception
  * @uses $conn
  * */
  private final function query($sql){
    $result=$this->conn->query($sql);
    if($result){
      return $result;
    }
    throw new Exception('DB error: '.$this->conn->error, $this->conn->errno);
    return FALSE;
  }

  public function storeRequestToken(JK_RequestToken $request_token, $user_id){
    if($request_token==NULL || (! is_a($request_token, 'JK_RequestToken')) ){
      throw new InvalidArgumentException('Invalid request token passed to storeRequestToken');
    }
    $user_id=cleanUserId($user_id);
    // remove any existing request tokens for this user.
    $this->query("DELETE FROM {$this->table} WHERE `type`='request' AND `user_id`=$user_id LIMIT 1");
    // calc expires
    $expires=time()+$request_token->ttl;
    // insert
    $this->query("INSERT INTO {$this->table} (`type`, `user_id`, `key`, `secret`, `expires`)
      VALUES('request', $user_id, '{$request_token->key}', '{$request_token->secret}', $expires)");
  }


  public function fetchRequestToken($key){
    $key=$this->conn->real_escape_string($key);
    $q=$this->query("SELECT `user_id,`, `key`, `secret`, `expires` FROM {$this->table}
      WHERE `type`='request' AND `key`='$key' LIMIT 1");
    if($q->num_rows==1){
      $row=$q->fetch_object();
      $q->free_result();
      // recalc ttl
      $ttl=$row->expires-time();
      // if token has expired, delete it!
      if($ttl<0){
        $this->query("DELETE FROM {$this->table} WHERE `type`='request' AND `key`='$key' LIMIT 1");
        return FALSE;
      }
      // return the object
      global $JK_PATH;
      require_once $JK_PATH.'requesttoken.class.php';
      return new JK_RequestToken($row->key, $row->secret, $ttl, $row->user_id);
    }
    return FALSE;
  }


  public function storeAccessToken(JK_UserAuthorisation $token){
    if($token==NULL || (! is_a($token, 'JK_UserAuthorisation')) ){
      throw new InvalidArgumentException('Invalid request token passed to storeAccessToken');
    }
    if($token->getUserId()==NULL){
      throw new InvalidArgumentException('$token has NULL user id - cannot be used in call to storeAccessToken');
    }
    // clean fields
    $user_id=$this->cleanUserId($token->getUserId());
    $key=$this->conn->real_escape_string($token->getKey());
    $secret=$this->conn->real_escape_string($token->getSecretKey());
    // insert
    $this->query("INSERT INTO {$this->table} (`type`, `user_id`, `key`, `secret`)
      VALUES ('access', $user_id, '$key', '$secret')
      ON DUPLICATE KEY UPDATE `key`='$key', `secret`='$secret'");
  }


  public function fetchAccessToken($user_id){
    $user_id=$this->cleanUserId($user_id);
    $q=$this->query("SELECT `key`, `secret` FROM {$this->table}
      WHERE `type`='access' AND `user_id`=$user_id LIMIT 1");
    if($q->num_rows==1){
      $row=$q->fetch_object();
      $q->free_result();
      return new JK_UserAuthorisation($row->key, $row->secret, $user_id);
    }
    $q->free_result();
    return FALSE;
  }

}
