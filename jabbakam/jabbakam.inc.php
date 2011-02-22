<?php
/** Jabbakam API client library file.
*
* This is a convienience file that loads in all the Jabbakam class files.
*
* Example Usage:
* <code>
* require_once 'jabbakam/jabbakam.inc.php';
* </code>
*
* @package jabbakam
* @version 1.0
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version 2.0
* */

/** Load in the core API file.*/
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'core.php'; # this will automatically load in the OAuth and config files, and set $JK_PATH
include_once $JK_PATH.'camera.class.php';
include_once $JK_PATH.'item.class.php'; # contains JK_Item and JK_Frame
include_once $JK_PATH.'folder.class.php';
include_once $JK_PATH.'account.class.php';

include_once $JK_PATH.'requesttoken.class.php';

include_once $JK_PATH.'tokenstorage.interface.php';
include_once $JK_PATH.'tokenstoragemysql.class.php';
