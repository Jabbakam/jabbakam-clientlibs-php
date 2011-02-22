<?php
/** Token Storage Helper Interface.
*
* @package jabbakam
* @version 1.0
* @author Pete
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license 2.0 http://www.opensource.org/licenses/apache2.0.php Apache License Version
*/

/** Interface for helper classes that store Jabbakam OAuth tokens.
*
* @package jabbakam
* */
interface JK_TokenStorage{


  /** Store a request token.
  *
  * @param object $request_token An instance of JK_RequestToken
  * @param int|string $user_id Local id of the user that this request token is for.
  *
  * @see JK_RequestToken
  * @see JK_RequestToken::send()
  * @see Jabbakam::requestAuthorisation()
  * */
  public function storeRequestToken(JK_RequestToken $request_token, $user_id);

  /** Retreive a reqest token.
  *
  * @param string $key The OAuth key of the token.
  * @return object|bool A JK_RequestToken, or FALSE if not found.
  *
  * @see JK_RequestToken::$key
  * @see JK_UserAuthorisation::fromRequestToken()
  * @see jabbakam_callback.php
  * */
  public function fetchRequestToken($key);

  /** Stores an access authorisation token.
  *
  * @param object $token An instance of JK_UserAuthorisation.
  *
  * @see JK_UserAuthorisation
  * */
  public function storeAccessToken(JK_UserAuthorisation $token);


  /** Fetches the authorisation token for a user.
  *
  * @param int|string Local id of the user
  * @return object|bool JK_UserAuthorisation instance or FALSE if not found.
  *
  * @see JK_UserAuthorisation
  * */
  public function fetchAccessToken($user_id);

}
