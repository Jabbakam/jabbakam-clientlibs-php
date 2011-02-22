<?php
/** Account and user usage classes.
*
* @package jabbakam
* @version 1.0
* @copyright Jabbakam Ltd 2009-2010
* @link http://developers.jabbakam.com
* @license http://www.opensource.org/licenses/apache2.0.php Apache License Version 2.0
*/


/** The user account class.
*
* Account information is read-only.
*
* @package jabbakam
* */
class JK_Account extends JK_Object{

  /** @var string */
  public $display_name;

  /** @var string */
  public $first_name;

  /** @var string */
  public $last_name;

  /** @var string */
  public $email;

  /** @var string */
  public $address_1;

  /** @var string */
  public $address_2;

  /** @var string */
  public $address_3;

  /** @var string */
  public $postcode;

  /** @var string */
  public $county;

  /** @var string */
  public $country;

  /** @var string */
  public $mobile;

  /** @var string */
  public $day_phone;

  /** @var string */
  public $company;

  /** @var JK_UsageDetails Initially NULL
  * @see fetchUsageDetails() */
  public $usage_details=NULL;

  /** @ignore */
  protected $_name='account';

  /** @ignore */
  protected static $NAME='account';

  /** @see load() */
  protected function __construct(){

  }

  /** Retrieves the user's account details.
  *
  *
  * @return JK_Account
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public static function load(){
    $result=new JK_Account();
    $xml=Jabbakam::call(self::$NAME, 'details');
    $a=$xml->account;
    $result->display_name=(string) $a->display_name;
    $result->first_name=(string) $a->first_name;
    $result->last_name=(string) $a->last_name;
    $result->email=(string) $a->email;
    $result->address1=(string) $a->address_1;
    $result->address2=(string) $a->address_2;
    $result->address3=(string) $a->address_3;
    $result->county=(string) $a->county;
    $result->country=(string) $a->country;
    $result->postcode=(string) $a->postcode;
    $result->mobile=(string) $a->mobile;
    $result->day_phone=(string) $a->day_phone;
    $result->company=(string) $a->company;
    return $result;
  }

  /** Fetches the user's usage details.
  *
  * This object's $usage_details will be set to a JK_UsageDetails instance.
  *
  * @return JK_UsageDetails The usage details.
  *
  * @throws Exception
  * @throws JK_Exception
  * */
  public function fetchUsageDetails(){
    $xml=$this->call('usage_details');
    $result=new JK_UsageDetails();
    $u=$xml->usage;
    $result->credit_balance=(int) $u->credit_balance;
    $result->credits_used_month=(int) $u->credits_used_month;
    $result->bandwidth_used_month=(float) $u->bandwidth_used_month;
    $result->est_days_remaining=(int) $u->est_days_remaining;
    $result->diskspace_used=(float) $u->diskspace_used;
    $this->usage_details=$result;
    return $result;
  }


}

/* Convience class for holding user usage details.
*
* @see JK_Account::fetchUsageDetails()
* @see JK_Account::$usage_details
* @package jabbakam
* */
class JK_UsageDetails extends JK_Object{

  /** @var int */
  public $credit_balance;

  /** @var int */
  public $credits_used_month;

  /** @var float GB */
  public $bandwidth_used_month;

  /** @var int */
  public $est_days_remaining;

  /** @var float GB */
  public $diskspace_used;


  public function __construct(){

  }

}
