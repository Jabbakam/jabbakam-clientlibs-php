<?php
/** Camera class.
*
* @package jabbakam
* @version 1.0
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version 2.0
*/


/** A camera in the jabbakam system.
*
* @package jabbakam
* */
class JK_Camera extends JK_Object{

  /** @var int */
  public $id=0;

  /** @var string */
  public $label='';

  /** @var float */
  public $latitude=0.0;

  /** @var float */
  public $longitude=0.0;

  /** @var string Set after to details() has been called
  * @see details() */
  public $public_label=NULL;

  /** @var string Set after to details() has been called
  * @see details() */
  public $description=NULL;

  /** @var string Set after to details() has been called
  * @see details() */
  public $address1=NULL;

  /** @var string Set after to details() has been called
  * @see details() */
  public $address2=NULL;

  /** @var string Set after to details() has been called
  * @see details() */
  public $address3=NULL;

  /** @var string Set after to details() has been called
  * @see details() */
  public $county=NULL;

  /** @var string Set after to details() has been called
  * @see details() */
  public $country=NULL;

  /** @var string Set after to details() has been called
  * @see details() */
  public $postcode=NULL;

  /** @var bool Set after to details() has been called
  * @see details() */
  public $alerts=NULL;

  /** @var bool Set after to details() has been called
  * @see details() */
  public $record_video=NULL;

  /** @var bool Set after to details() has been called
  * @see details() */
  public $is_hidden=NULL;

  /** @internal */
  protected $name='camera';
  /** @internal */
  protected static $NAME='camera';

  /** @var bool whether details() has been called on this object.
  * @see details()
  * @see update()
  * */
  private $have_details=FALSE;

  /** @var bool whether this object has been deleted.*/
  private $deleted=FALSE;

  /** Constructor.
  *
  * @param int $id
  * */
  public function __construct($id){
    $this->id=(int) $id;
  }

  /** Fetches a list of the user's cameras.
  *
  * Note that the returned camera objects will only have id, label, longitude and latitiude set.
  * For the other properties, you need to call details().
  *
  * @return array Of JK_Camera objects, which may be empty.
  * @see details()
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public static final function getList(){
    $result=array();
    $xml=Jabbakam::call(self::$NAME, 'get_list');
    foreach($xml->cameras->camera as $c) :
      $id=(int) $c->attributes()->camera_id;
      $camera=new JK_Camera($id);
      $camera->label=(string) $c->label;
      $camera->latitude=(float) $c->location->latitude;
      $camera->longitude=(float) $c->location->longitude;
      $result[]=$camera;
    endforeach;
    return $result;
  }

  /** Retrieves the details for a camera.
  *
  * @param JK_Camera &$camera An instance of JK_Camera
  * @return object The $camera with the secondary properties set.
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public static final function detailsFor(JK_Camera & $camera){
    if($camera==NULL || (! is_a($camera, 'JK_Camera')) ){
      throw new InvalidArgumentException('Invalid $camera passed to detailsFor');
    }
    $xml=Jabbakam::call(self::$NAME, 'details', array('camera_id'=>$camera->id) );
    $camera->public_label=(string) $xml->camera->public_label;
    $camera->address1=(string) $xml->camera->address1;
    $camera->address2=(string) $xml->camera->address2;
    $camera->address3=(string) $xml->camera->address3;
    $camera->country=(string) $xml->camera->country;
    $camera->postcode=(string) $xml->camera->postcode;
    $camera->alerts=self::attrib2bool($xml->camera->alerts);
    $camera->record_video=self::attrib2bool($xml->camera->record_video);
    $camera->is_hidden=self::attrib2bool($xml->camera->is_hidden);
    $camera->have_details=TRUE;
    return $camera;
  }

  /** Retrieves the details of this camera object.
  *
  * @return object This camera object.
  *
  * @throws Exception
  * @throws JK_Exception
  *
  * @uses detailsFor()
  * */
  public function details(){
    if($this->deleted){
      throw new Exception('This camera has been deleted');
      return NULL;
    }
    self::detailsFor($this);
    return $this;
  }

  /** Updates the camera details.
  *
  * The camera details must have been previously fetched with a call to details() before attempting to update.
  *
  * @see details()
  * @return bool TRUE on success
  *
  * @throws Exception If details() has not been already called.
  * @throws JK_Exception
  *  */
  public function update(){
    if($this->deleted){
      throw new Exception('This camera has been deleted');
      return FALSE;
    }
    if(! $this->have_details){
      throw new Exception('You must call details() on a camera object before calling update()');
      return FALSE;
    }
    self::chomp($this->label);
    self::chomp($this->public_label);
    self::chomp($this->description, 512);
    self::chomp($this->address1);
    self::chomp($this->address2);
    self::chomp($this->address3);
    self::chomp($this->county, 50);
    self::chomp($this->postcode, 20);
    self::chomp($this->country, 50);
    $this->latitude=(float) $this->latitude;
    $this->longitude=(float) $this->longitude;
    $data=array('camera_id'=>$this->id,
      'label'=>$this->label,
      'description'=>$this->description,
      'address1'=>$this->address1,
      'address2'=>$this->address2,
      'address3'=>$this->address3,
      'county'=>$this->county,
      'postcode'=>$this->postcode,
      'country'=>$this->country,
      'longitude'=>$this->longitude,
      'latitude'=>$this->latitude,
      'alerts'=>self::bool2str($this->alerts),
      'record_video'=>self::bool2str($this->record_video),
      'is_hidden'=>self::bool2str($this->is_hidden)
    );

    if($this->responseCode($this->call('update', $data, TRUE))==12000){
      return TRUE;
    }
    return FALSE;
  }

  /** Searches the user's cameras.
  *
  * @param string $keywords
  * @return array of Camera objects, which may be empty.
  *
  * @throws InvalidArgumentException If $keywords is NULL or empty.
  * @throws Exception
  * @throws JK_Exception
  * */
  public static function search($keywords){
    if($keywords==NULL){
      throw new InvalidArgumentException('NULL $keywords passed to search()');
    }
    $keywords=trim($keywords);
    if(! $keywords){
      throw new InvalidArgumentException('empty $keywords in call to search()');
    }
    self::chomp($keywords, 255);
    $result=array();
    $xml=Jabbakam::call(self::$NAME, 'search', array('keywords'=>$keywords) );
    foreach($xml->cameras->camera as $c) :
      $id=(int) $c->attributes()->camera_id;
      $camera=new JK_Camera($id);
      $camera->label=(string) $c->label;
      $camera->latitude=(float) $c->location->latitude;
      $camera->longitude=(float) $c->location->longitude;
      $result[]=$camera;
    endforeach;
    return $result;
  }

  /** Deletes the camera.
  *
  * @return bool TRUE on success
  *
  * @throws Exception If details() has not been already called.
  * @throws JK_Exception
  * */
  public function delete(){
    if($this->deleted){
      throw new Exception('This camera has already been deleted');
      return FALSE;
    }
    $code=$this->responseCode($this->call('delete', array('camera_id'=>$this->id), TRUE));
    if($code==12100){
      $this->deleted=TRUE;
      return TRUE;
    }
    return FALSE;
  }


}
