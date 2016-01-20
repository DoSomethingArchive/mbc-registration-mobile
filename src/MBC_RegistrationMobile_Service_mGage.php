<?php
/**
 * Service class specific to the Mobile Commons SMS service.
 * https://www.mobilecommons.com
 */
namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MBC_RegistrationMobile\mGage;
use \Exception;

/*
 * MBC_RegistrationMobile_Service_MobileCommons: Used to process the mobileCommonsQueue
 * entries for the Mobile Commons service.
 */
class  MBC_RegistrationMobile_Service_mGage extends MBC_RegistrationMobile_BaseService
{

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function __construct($message) {

    parent::__construct($message);
    $this->mobileServiceName = 'mGage';
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
      echo '** canProcess(): mobile not set. mGage requires a mobile number for processing.', PHP_EOL;
      parent::reportErrorPayload();
      return FALSE;
    }
    if (!isset($message['service_path_id'])) {
      echo '** canProcess(): service_path_id not set for mobile: ' . $message['mobile'] . '. mGage requires service_path_id (opt in) for processing.', PHP_EOL;
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

    if ($message['user_country'] == 'BR' && strlen($message['mobile']) > 9) {
      $this->message['mobile'] = substr($message['mobile'], 0, 9);
    }
    else {
      $this->message['mobile'] = $message['mobile'];
    }

    if (isset($message['service_path_id'])) {
      $this->message['opt_in_path_id'] = $message['service_path_id'];
      unset($this->message['service_path_id']);
    }

    // AGG2015
    if (strtoupper($message['application_id']) == 'AGG' && isset($message['original']['candidate_name'])) {
      $this->message['AGG2015_1st_vote'] = $message['original']['candidate_name'];
      $this->message['AGG2015_1st_vote_id'] = $message['original']['candidate_id'];
    }

  }

  /**
   * Process message from consumed queue.
   */
  public function process() {

    try {

      $status = $this->mobileServiceObject->mobileOriginated($this->message);
      $this->messageBroker->sendAck($this->message['payload']);
      echo '-> MBC_RegistrationMobile_Service_mGage->process: ' . $this->message['mobile'] . ' -------', PHP_EOL;
    }
    catch (Exception $e) {
      echo '- MBC_RegistrationMobile_Service_mGage process() Exception:' . $e->getMessage(), PHP_EOL;
    }

  }

  /**
   * connectService: Currently only establisheds mGage specific configuration property. In the future a mGage class to instantiate will be used.
   *
   * @param string $userCountry
   *   The country code of the site that generated the message and thus the mobile service
   *   that needs to be connected to.
   */
  public function connectServiceObject($userCountry) {

    $communicateProConfig = $this->mbConfig->getProperty('communicatePro_config');

    // @todo: trap undefined $affiliate values.
    $config = array(
      'username' => $communicateProConfig['username'],
      'password' => $communicateProConfig['password'],
      'mGagePathID' => $this->message['service_path_id'],
    );
    echo '- connectServiceObject mGage: ' . $this->message['user_country'], PHP_EOL;
    $mobileServiceObject = new MB_mGage($config);

    return $mobileServiceObject;
  }

}
