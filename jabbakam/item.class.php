<?php
/** Item and frame classes.
*
* @package jabbakam
* @version 1.0
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version 2.0
*/


/** An Jababkam item, which is either a video clip or a snapshot.
*
* @package jabbakam
* */
class JK_Item extends JK_Object{

  /** @var int*/
  public $id=0;

  /** @var int*/
  public $camera_id=0;

  /** @var string*/
  public $label='';

  /** @var string yyyy-mm-dd hh:mm:ss format, UTC time.*/
  public $start_datetime='';

  /** @var string.*/
  public $thumbnail_url='';

  /** @var bool */
  public $is_snapshot=FALSE;

  /** @var bool */
  public $is_new=FALSE;

  /** @var bool */
  public $flag=FALSE;

  /** @var string
  */
  public $note=NULL;

  /** @var int
  * */
  public $folder_id=NULL;

  /** @var int ?
  * */
  private $shared=NULL;

  /** @var string yyyy-mm-dd hh:mm:ss format, UTC time.
  * */
  public $end_datetime=NULL;


  /** @var array Of JK_Frame objects.
  * Initially NULL, call frames() to populate it.
  * @see frames() */
  public $frames=NULL;

  /** @internal */
  protected $_name='item';

  /** @internal */
  protected static $NAME='item';

  private $deleted=FALSE;

  /** Constructor.
  *
  * @param int $id
  * */
  public function __construct($id){
    $this->id=(int) $id;
  }

  /** Fetches a list of items, similar to that of the inbox view of the Jabbakam Website.
  *
  * @param int $page Optional - Which 'page' of items to fetch. Default is 1.
  * @param int $qty Optional How many items to retreive per 'page'. Default is 10. Min 1 Max 20.
  * @param int $camera_id Optional Filter the items retreived for a specific camera. Default is NULL, which retreives items for all the user's cameras.
  * @param string $datetime Optional.  A datetime in yyyy-mm-dd hh:mm:ss format (GMT).  Only retreive items after this date/time.
  * @param bool $datetime_reverse Optional . Default FALSE. If passing $datetime and this is TRUE, then only items before this date/time will be retreived.
  *
  * @return array of Item objects
  *
  * @throws InvalidArgumentException If $page, $qty or $camera_id are invalid
  * @throws Exception
  * @throws JK_Exception
  * */
  public static function getList($page=1, $qty=10, $camera_id=NULL, $datetime=NULL, $datetime_reverse=FALSE){
    if($page<1){
      throw new InvalidArgumentException('$page must be at least 1 in call to getList()');
    }
    if($qty<1 || $qty>20){
      throw new InvalidArgumentException ("Invalid qty of $qty in call to getList()");
    }
    if($camera_id!=NULL && $camera_id<1){
      throw new InvalidArgumentException('invalid camera_id in call to getList()');
    }

    $result=array();
    $params=array('page'=>$page, 'qty'=>$qty);
    if($camera_id!=NULL){
      $params['camera_id']=$camera_id;
    }
    if($datetime!=NULL){
      $params['datetime']=$datetime;
      $params['datetime_reverse']=self::bool2str($datetime_reverse);
    }
    $xml=Jabbakam::call(self::$NAME, 'get_list', $params);
    #print_r($xml);
    foreach($xml->items->item as $i) :
      $id=(int) $i->attributes()->item_id;
      $item=new JK_Item($id);
      $item->camera_id=(int) $i->camera_id;
      $item->label=(string) $i->label;
      $item->start_datetime=(string) $i->start_datetime;
      $item->thumbnail_url=(string) $i->thumbnail_url;
      $item->is_snapshot=self::attrib2bool($i->is_snapshot);
      $item->is_new=self::attrib2bool($i->is_new);
      $item->flag=self::attrib2bool($i->flag);
      $result[]=$item;
    endforeach;
    return $result;
  }

  /** Retrieves detailed information for this item.
  *
  * @return object This Item
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public function details(){
    if($this->deleted){
      throw new Exception('This item has been deleted');
      return FALSE;
    }
    $xml=Jabbakam::call(self::$NAME, 'details', array('item_id'=>$this->id) );
    $this->note=(string) $xml->item->note;
    $this->folder_id=(int) $xml->item->folder_id;
    $this->shared=(int) $xml->item->shared;
    $this->end_datetime=(string) $xml->item->end_datetime;

    $this->camera_id=(int) $xml->item->camera_id;
    $this->label=(string) $xml->item->label;
    $this->start_datetime=(string) $xml->item->start_datetime;
    $this->thumbnail_url=(string) $xml->item->thumbnail_url;
    $this->is_snapshot=self::attrib2bool($xml->item->is_snapshot);
    $this->is_new=self::attrib2bool($xml->item->is_new);
    $this->flag=self::attrib2bool($xml->item->flag);
    return $this;
  }

  /** Deletes this item.
  *
  * @return int|bool 13100 If Item was deleted, 13101 if Item moved to trash, FALSE if delete failed.
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public function delete(){
    if($this->deleted){
      throw new Exception('This item has already been deleted');
      return FALSE;
    }
    $code=$this->responseCode($this->call('delete', array('item_id'=>$this->id), TRUE));
    if($code==13101 || $code==13100){
      $this->deleted=TRUE;
      return $code;
    }
    return FALSE;
  }

  /** Fetches the image frame(s) for this item.
  *
  * The frames will be store in this objects frames property.
  *
  * @param bool $thumbnail Optional - If a thumbnail_url is desired as part of the frame information.  Default is FALSE
  * @return array of JK_Frame objects
  * @see JK_Frame
  * @see $frames
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public function frames($thumbnail=FALSE){
    if($this->deleted){
      throw new Exception('This item has been deleted');
      return NULL;
    }
    $this->frames=array();
    $params=array('item_id'=>$this->id);
    if($thumbnail){
      $params['thumbnail']='true';
    }
    $result=array();
    $xml=$this->call('frames', $params);
    foreach($xml->clip->images->image as $i) :
      $tn=NULL;
      if($i->thumbnail_url){
        $tn=(string) $i->thumbnail_url;
      }
      $result[]=new JK_Frame($this->id, (string) $i->identifier, (string) $i->url, (string) $i->time->datetime, (int) $i->time->milliseconds, $tn);
    endforeach;
    $this->frames=$result;
    $this->is_new=FALSE;
    return $result;
  }

  /** Updates an items label and/or note .
  *
  * @param string $label The new label for the item.  Max length is 64 characters. Default is NULL, which leaves the label unchanged.
  * @param string $note The new note for the item.  Max length is 1024 characters.  Default is NULL, which leaves the note unchanged.
  * @return bool TRUE on success
  *
  * @throws InvalidArgumentException If both $label and $note are NULL
  * @throws Exception
  * @throws JK_Exception
  * */
  public function update($label=NULL, $note=NULL){
    if($this->deleted){
      throw new Exception('This item has been deleted');
      return FALSE;
    }
    if($label==NULL && $note==NULL){
      throw new InvalidArgumentException('Both label and note are NULL in call to update()');
      return FALSE;
    }
    if($label==NULL) {
      $label=$this->label;
    }
    else{
      self::chomp($label, 64);
    }
    if($note==NULL) {
      $note=$this->note;
    }
    else {
      self::chomp($note, 1024);
    }
    $code=$this->responseCode($this->call('update', array('item_id'=>$this->id, 'label'=>$label, 'note'=>$note), TRUE));
    if($code==13000){
      $this->label=$label;
      $this->note=$note;
      $this->is_new=FALSE;
      return TRUE;
    }
    return FALSE;
  }

  /** Sets whether this item is flagged or not.
  *
  * @param bool $flag The new flag status. Default is TRUE.
  * @return bool TRUE on success.
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public function setFlag($flag=TRUE){
    if($this->deleted){
      throw new Exception('This item has been deleted');
      return FALSE;
    }
    $code=$this->responseCode($this->call('set_flag', array('item_id'=>$this->id, 'flagged'=>self::bool2str($flag)), TRUE));
    if($code==13001){
      $this->flag=$flag;
      $this->is_new=FALSE;
      return TRUE;
    }
    return FALSE;
  }

  /** Moves the item to a folder
  *
  * @param int $folder_id
  * @return bool TRUE on success
  *
  * @see copy()
  *
  * @throws Exception
  * @throws JK_Exception Including if folder does not exist or does not belong to the user, or the item is already in the folder.
  * */
  public function move($folder_id){
    if($this->deleted){
      throw new Exception('This item has been deleted');
      return FALSE;
    }
    if($folder_id==$this->folder_id){
      throw new JK_Exception('Item is already in the folder in call to move()', 3003);
      return FALSE;
    }
    $this->is_new=FALSE;
    return $this->responseCode($this->call('move', array('item_id'=>$this->id, 'folder_id'=>$folder_id), TRUE))==13002;
  }

  /** Copies the item to a folder
  *
  * @param int $folder_id
  * @return object|bool The new JK_Item object that is the result of the copy, or FALSE on failure.
  *
  * @see move()
  *
  * @throws Exception
  * @throws JK_Exception Including if folder does not exist or does not belong to the user, or the item is already in the folder.
  * */
  public function copy($folder_id){
    if($this->deleted){
      throw new Exception('This item has been deleted');
      return FALSE;
    }
    if($folder_id==$this->folder_id){
      throw new JK_Exception('Item is already in the folder in call to copy()', 3003);
      return FALSE;
    }
    $xml=$this->call('copy', array('item_id'=>$this->id, 'folder_id'=>$folder_id), TRUE);
    if(! $xml){
      return FALSE;
    }
    $code=$this->responseCode($xml);
    if($code!=13003){
      return FALSE;
    }
    // get the new item id
    $id=$xml->response->item_id;
    if(! $id){ # shouldn't happen
      return FALSE;
    }
    $id=(int) $id;
    $result=new JK_Item($id);
    $result->label=$this->label;
    $result->start_datetime=$this->start_datetime;
    $result->is_snapshot=$this->is_snapshot;
    $result->is_new=FALSE;
    $result->flag=FALSE;
    // It's currently safe to copy the thumbnail_url
    $result->thumbnail_url=$this->thumbnail_url;
    return $result;
  }

}

/** Class that represents a single frame image that is part of an item.
*
* @package jabbakam
* @see JK_Item::frames()
* */
class JK_Frame extends JK_Object{

  /** @var int */
  public $item_id=0;

  /** @var string */
  public $indentifier='';

  /** @var string Full URL to the JPEG image for this frame.*/
  public $URL='';

  /** @var string yyyy-mm-dd hh:mm:ss format UTC time.*/
  public $datetime='';

  /** @var int */
  public $milliseconds =0;

  /** @var string Optional URL of a thumbnail image for this frame.*/
  public $thumbnail_url=NULL;

  /** @internal */
  protected $_name='item';

  public function __construct($item_id, $identifier, $URL, $datetime, $milliseconds=0, $thumbnail_url=NULL){
    $this->item_id=$item_id;
    $this->identifier=$identifier;
    $this->URL=$URL;
    $this->datetime=$datetime;
    $this->milliseconds=$milliseconds;
    if($thumbnail_url!=NULL){
      $this->thumbnail_url=$thumbnail_url;
    }
  }

  /** Creates a snapshot item (of 1 frame) from a frame.
  *
  * @param int $folder_id The folder to save the snapshot in.
  * @return object|bool  A JK_Item or FALSE on failure
  *
  * @throws Exception
  * @throws JK_Exception Including if folder does not exist or does not belong to the user.
  * */
  public function saveSnapshot($folder_id){
    $xml=$this->call('save_snapshot', array('item_id'=>$this->item_id, 'folder_id'=>$folder_id, 'indentifier'=>$this->identifier), TRUE);
    $code=$this->responseCode($xml);
    if($code==13200){
      $id=$xml->response->item_id;
      if(! $id){
        return FALSE;
      }
      $id=(int) $id;
      $result=new JK_Item($id);
      $result->is_snapshot=TRUE;
      $result->is_new=FALSE;
      $result->folder_id=$folder_id;
      $result->frames=array($this); # @todo check this
      $result->details(); # need to load in the relevant details such as camera_id etc.
      return $result;
    }
    return FALSE;
  }
}
