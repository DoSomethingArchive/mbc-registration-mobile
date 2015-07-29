<?php

namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

/*
 * MBC_RegistrationMobile_Service_MobileCommons: Used to process the mobileCommonsQueue
 * entries for the Mobile Commons service.
 */
class  MBC_RegistrationMobile_Service_MobileCommons extends MBC_RegistrationMobile_BaseService
{
  
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
   * Setting from external services - Mail chimp.
   *
   * @var array
   */
  protected $settings;
  
  /**
   * Values submitted in potential mobile activity.
   */
  protected $mobileSubmission;
  
  /**
   * Values submitted in potential mobile activity.
   */
  protected $opt_in_path_id;

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *  
   * @param object $statHat
   *   Track application activity by triggering counters in StatHat service.
   *
   * @param object $toolbox
   *   A collection of common tools for the Message Broker system.
   *   
   * @param array $settings
   *   Settings from internal and external services used by the application.
   */
  public function __construct(StatHat $statHat, MB_Toolbox $toolbox, $settings, $opt_in_path_id) {

    $this->messageBroker = $messageBroker;
    $this->statHat = $statHat;
    $this->toolbox = $toolbox;
    $this->settings = $settings;
    $this->opt_in_path_id = $opt_in_path_id;
  }
  
  /**
   * Method to determine if message can be processed. Tests based on requirements of the service.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   *
   * @retun boolean
   */
  private function canProcess($message) {

    // Mobile Commons, current supplier for US and CA requirements
    if (!isset($this->mobileSubmission['mobile'])) {
      echo '** Mobile Commons requires a mobile number for processing.', PHP_EOL;
      return FALSE;
    }
    if (!isset($this->mobileSubmission['service_path_id'])) {
      echo '** Mobile Commons requires service_path_id (opt in) for processing.', PHP_EOL;
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
  private function setter($message) {

    $this->mobileSubmission = $message;
  }

  /**
   * Process message from consumed queue.
   */
  private function process() {
    
  }

}
