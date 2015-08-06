<?php
/**
 * Service class specific to the Mobile Commons SMS service.
 * https://www.mobilecommons.com
 */
namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBC_RegistrationMobile_Service_MobileCommons: Used to process the mobileCommonsQueue
 * entries for the Mobile Commons service.
 */
class  MBC_RegistrationMobile_Service_MobileCommons extends MBC_RegistrationMobile_BaseService
{

  /**
   * Singleton instance of MB_Configuration application settings and service objects
   *
   * @var object
   */
  protected $mbConfig;

  /**
   * Message Broker connection to RabbitMQ
   *
   * @var object
   */
  protected $messageBroker;
  
  /**
   * StatHat object for logging of activity
   *
   * @var object
   */
  protected $statHat;
  
  /**
   * Message Broker Toolbox - collection of utility methods used by many of the
   * Message Broker producer and consumer applications.
   *
   * @var object
   */
  protected $toolbox;

  /**
   * Connection to mobile service to send message details to.
   */
  protected $mobileServiceObject;

  /**
   * Value of message from queue to be consumed / processed.
   *
   * @var array
   */
  protected $message;
  
  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function __construct($message) {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->messageBroker = $this->mbConfig->getProperty('messageBroker');
    $this->statHat = $this->mbConfig->getProperty('statHat');
    $this->toolbox = $this->mbConfig->getProperty('mbToolbox');

    $this->mobileServiceObject = $this->connectServiceObject($message['application_id']);
    $this->message = $message;
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
      echo '** canProcess(): Mobile Commons requires a mobile number for processing.', PHP_EOL;
      return FALSE;
    }
    if (!isset($message['service_path_id'])) {
      echo '** canProcess(): Mobile Commons requires service_path_id (opt in) for processing.', PHP_EOL;
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

    if (strtoupper($message['application_id']) == 'CGG2014' && isset($message['original']['candidate_name'])) {
      $this->message['CGG2014_1st_vote'] = $message['original']['candidate_name'];
    }
    // AGG2015
    if (strtoupper($message['application_id']) == 'AGG' && isset($message['original']['candidate_name'])) {
      $this->message['AGG2015_1st_vote'] = $message['original']['candidate_name'];
      $this->message['AGG2015_1st_vote_id'] = $message['original']['candidate_id'];
      $this->message['AGG2015_1st_vote_gender'] = $message['original']['candidate_gender'];
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
        $this->statHat->ezCount('MBC_RegistrationMobile_Service_MobileCommons: profiles_update error: ' . $status['error']->attributes()->{'message'});
      }
      else {

        // todo: adjust exchange / queue settings to require ack
        // $this->messageBroker->sendAck($payload);
        $this->statHat->ezCount('MBC_RegistrationMobile_Service_MobileCommons: profiles_update success');
      }
      echo '-> MBC_RegistrationMobile_Service_MobileCommons->process: ' . $this->message['phone_number'] . ' -------', PHP_EOL;
    }
    catch (Exception $e) {
      trigger_error('mbc-registration-mobile ERROR - Failed to submit "profiles_update" to Mobile Commons API.', E_USER_WARNING);
      echo 'Excecption:' . print_r($e, TRUE), PHP_EOL;
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
   *   An obect of a mobile service.
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
