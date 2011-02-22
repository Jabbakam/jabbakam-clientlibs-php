<?php
/** Network class.
*
* @package jabbakam
* @version 1.0
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version 2.0
*/


/** Network class.
*
*
* @package jabbakam
* */
class JK_Network extends JK_Object{

  /** @var int*/
  public $id=0;


  /** Constructor.
  *
  * @param int $id
  * */
  public function __construct($id){
    $this->id=(int) $id;
  }

  /** fetches a list of network objects that the user belongs to.
  *
  * @return array List of JK_Network objects, may be empty.
  * @throws Exception
  * @throws JK_Exception
  * */
  public function getList(){

  }

}
