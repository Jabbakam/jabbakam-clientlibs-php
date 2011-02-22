<?php
/** Folder class.
*
* @package jabbakam
* @version 1.0
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version  2.0
*/


/** Folder class.
*
* Folders in Jabbakam are hierachieal, being either at the top or root level with parent_id of 0,
* or a sub-folder, where parent_id innidcates the containing folder.
*
* @package jabbakam
* */
class JK_Folder extends JK_Object{

  /** @var int*/
  public $id=0;

  /** @var string*/
  public $name='';

  /** @var int The id number of this folders parent folder, or 0 if it is a top-level folder.*/
  public $parent_id=0;

  const NAME_MIN_LENGTH=3;
  const NAME_MAX_LENGTH=64;

  /** @ignore */
  protected $_name='folder';

  /** @ignore */
  protected static $NAME='folder';

  private $deleted=FALSE;

  /** Constructor.
  *
  * @param int $id
  * @param string $name
  * */
  public function __construct($id, $name='', $parent_id=0){
    $this->id=(int) $id;
    $this->name=(string) $name;
    $this->parent_id=$parent_id;
  }

  /** Fetches a list of the user's folders.
  *
  * @param int $folder_id Optional If passed, then the subfolders of $folder id will be returned.
  * The default is NULL, which will fetch the root folders for the user.
  * @return array of JK_Folder objects, which may be empty.
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public static function getList($folder_id=NULL){
    $result=array();
    if($folder_id==NULL){
      $params=NULL;
    }
    else{
      $params=array('folder_id'=>$folder_id);
    }
    $xml=Jabbakam::call(self::$NAME, 'get_list', $params);
    $parent_id=$folder_id==NULL ? 0 : $folder_id;
    foreach($xml->folders->folder as $f) :
      $id=(int) $f->attributes()->folder_id;
      $name=(string) $f;
      $result[]=new JK_Folder($id, $name, $parent_id);
    endforeach;
    return $result;
  }

  /** Creates a new folder for the user.
  *
  * @param string $name Name for the new folder. Min 3 charcters.  Max 64 characters.
  * @param int $parent_id The id of an exisiting folder that the new folder will be a child of.  Default is 0, which indicates the new folder will be
  * a top level folder.
  * @return object|bool A JK_Folder Instance, or FALSE on failure.
  *
  * @throws InvalidArgumentException
  * @throws Exception
  * @throws JK_Exception Including if the user already has a folder with the same name
  * */
  public static function create($name, $parent_id=0){
    if($name==NULL){
      throw new InvalidArgumentException('NULL $name passed to create()');
      return FALSE;
    }
    $name=trim($name);
    $l=strlen($name);
    if($l<self::NAME_MIN_LENGTH || $l>self::NAME_MAX_LENGTH){
      throw new InvalidArgumentException('$name is invalid length in call to create()');
    }
    if($parent_id<0){
      throw new InvalidArgumentException('Invalid $parent_id passed to create()');
      return FALSE;
    }

    $xml=Jabbakam::call(self::$NAME, 'create', array('name'=>$name, 'parent_id'=>$parent_id), TRUE);
    if(! $xml){
      return FALSE;
    }

    $f=$xml->folder;
    if(!$f) return FALSE;
    $id=$f->attributes()->folder_id;
    $name=(string) $f;

    return new JK_Folder($id, $name, $parent_id);
  }

  /** Deletes this folder.
  *
  * @return bool TRUE on success
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public function delete(){
    if($this->deleted){
      throw new Exception('Folder already deleted');
      return FALSE;
    }
    $code=$this->responseCode($this->call('delete', array('folder_id'=>$this->id), TRUE) );
    if($code==14100){
      $this->deleted=TRUE;
      return TRUE;
    }
    return FALSE;
  }

  /** Moves this folder, either to be a subfolder of an existing folder, or to the top-level.
  *
  * @param int $parent_id The id of an exisiting folder that the folder will be a child of.   0 indicates the folder will be moved to the top level.
  * @return object|bool This JK_Folder object, or FALSE on failure.
  *
  * @throws InvalidArgumentException
  * @throws Exception
  * @throws JK_Exception
  * */
  public function move($parent_id){
    if($parent_id<0){
      throw new InvalidArgumentException('Invalid $parent_id passed to move()');
      return FALSE;
    }
    $code=$this->responseCode($this->call('move', array('folder_id'=>$this->id, 'parent_id'=>$parent_id), TRUE) );
    if($code==14002){
      $this->parent_id=$parent_id;
      return $this;
    }
    return FALSE;
  }

  /** Renames a folder.
  *
  * @param string $new_name New name for the folder. Min 3 charcters.  Max 64 characters.
  * @return object|bool  This JK_Folder object, or FALSE on failure.
  *
  * @throws InvalidArgumentException
  * @throws Exception
  * @throws JK_Exception
  * */
  public function rename($new_name){
    if($new_name==NULL){
      throw new InvalidArgumentException('NULL $new_name passed to rename()');
      return FALSE;
    }
    $name=trim($new_name);
    $l=strlen($name);
    if($l<self::NAME_MIN_LENGTH || $l>self::NAME_MAX_LENGTH){
      throw new InvalidArgumentException('$new_name is invalid length in call to rename()');
    }
    $code=$this->responseCode($this->call('rename', array('folder_id'=>$this->id, 'name'=>$name), TRUE) );
    if($code==14000){
      $this->name=$name;
      return $this;
    }
    return FALSE;
  }
}
