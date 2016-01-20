<?php
/**
 * Service class specific to the Mobile Commons SMS service.
 * https://www.mobilecommons.com
 */
namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Configuration;
use \Exception;

/*
 * MBC_RegistrationMobile_Service_MobileCommons: Used to process the mobileCommonsQueue
 * entries for the Mobile Commons service.
 */
class  MBC_RegistrationMobile_Service_MobileCommons extends MBC_RegistrationMobile_BaseService
{

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function __construct($message) {

    parent::__construct();
    $this->mobileServiceName = 'Mobile Commons';
  }

  /**
   * Method to determine if message can be processed. Tests based on requirements of the service.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   *
   * @retun boolean
   */
  public function canProcess($message) {

    // Mobile Commons, current supplier for US and CA requirements
    if (!isset($message['mobile'])) {
      echo '** Service_MobileCommons canProcess(): mobile not set. Mobile Commons requires a mobile number for processing.', PHP_EOL;
      parent::reportErrorPayload();
      return FALSE;
    }
    // Validate phone number based on the North American Numbering Plan
    // https://en.wikipedia.org/wiki/North_American_Numbering_Plan
    $regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
    if (!(preg_match( $regex, $message['mobile']))) {
      echo '** Service_MobileCommons canProcess(): Invalid phone number based on  North American Numbering Plan standard: ' .  $message['mobile'], PHP_EOL;
      return FALSE;
    }
    if (!isset($message['service_path_id'])) {
      echo '** Service_MobileCommons canProcess(): service_path_id not set for mobile: ' . $message['mobile'] . '. Mobile Commons requires service_path_id (opt in) for processing.', PHP_EOL;
      parent::reportErrorPayload();
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function setter($message) {

    $this->message['phone_number'] = $message['mobile'];
    unset($this->message['mobile']);
    if (isset($message['service_path_id'])) {
      $this->message['opt_in_path_id'] = $message['service_path_id'];
      unset($this->message['service_path_id']);
    }

    // CGG
    if (strtoupper($message['application_id']) == 'CGG' && isset($message['original']['candidate_name'])) {
      $this->message['CGG2015_1st_vote'] = $message['original']['candidate_name'];
    }

  }

  /**
   * Process message from consumed queue.
   */
  public function process() {

    $payload = $this->message['payload'];
    unset($this->message['payload']);
    unset($this->message['original']);

    try {

      $status = (array)$this->mobileServiceObject->profiles_update($this->message);
      if (isset($status['error'])) {
        echo '- Error - ' . $status['error']->attributes()->{'message'} , PHP_EOL;
        echo '  Submitted: ' . print_r($this->message, TRUE), PHP_EOL;
        $this->messageBroker->sendNack($payload);
        $this->statHat->ezCount('MBC_RegistrationMobile_Service_MobileCommons: profiles_update error: ' . $status['error']->attributes()->{'message'});
        throw new Exception($status['error']->attributes()->{'message'});
      }
      else {
        $this->messageBroker->sendAck($payload);
        $this->statHat->ezCount('MBC_RegistrationMobile_Service_MobileCommons: profiles_update success');
      }

      echo '-> MBC_RegistrationMobile_Service_MobileCommons->process: ' . $this->message['phone_number'] . ' -------', PHP_EOL;
    }
    catch (Exception $e) {
      throw new Exception($e->getMessage());
      $this->statHat->ezCount('MBC_RegistrationMobile_Service_MobileCommons: profiles_update error');
    }

  }

  /**
   * connectService: instantiate object for mobile service based on the
   * country code.
   *
   * @param string $affiliate
   *   The country code of the site that generated the message and thus the mobile service
   *   that needs to be connected to.
   *
   * @return object $mobileServiceObject
   *   An object of a mobile service.
   */
  public function connectServiceObject($affiliate) {

    $mobileCommonsConfig = $this->mbConfig->getProperty('mobileCommons_config');

    // @todo: trap undefined $affiliate values.
    $config = array(
      'username' => $mobileCommonsConfig[$affiliate]['username'],
      'password' => $mobileCommonsConfig[$affiliate]['password'],
      'company_key' => $mobileCommonsConfig[$affiliate]['company_key'],
    );
    echo 'connectServiceObject company_key: ' . $mobileCommonsConfig[$affiliate]['company_key'], PHP_EOL;
    $mobileServiceObject = new \MobileCommons($config);

    return $mobileServiceObject;
  }

}
