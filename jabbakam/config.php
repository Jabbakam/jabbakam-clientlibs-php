<?php
/** Jabbakam library configuration file.
*
* You must change the settings in this file in order to use the Jabbakam API.
*
* @package jabbakam
* @copyright Jabbakam Ltd 2009
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version 2.0
*/
/** string Your developer key*/
define('JABBAKAM_KEY', 'your developer key goes here');

/** string Your developer secret key.*/
define('JABBAKAM_SECRET_KEY','your developer secret key goes here');

/**  string Full URL to your site's callback URL script.
 *  This is called after a user has granted your application access for the first time.*/
define('JABBAKAM_CALLBACK_URL', 'http://domain.tld/path/to/example/callback.php');
