<?php

namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MBC_RegistrationMobile\MBC_RegistrationMobile_BaseService;

/*
 * MBC_RegistrationMobile_Service_MobileCommons: Used to process the mobileCommonsQueue
 * entries for the Mobile Commons service.
 */
class  MBC_RegistrationMobile_Service extends MBC_RegistrationMobile_BaseService
{

  /**
   * Connection to mobile service to send message details to.
   */
  protected $mobileService;

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function __construct($message) {

    parent::__construct($message);
    $this->mobileService = $this->connectService($message);
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
    return $this->mobileService->canProcess($message);
  }

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function setter($message) {
    $this->mobileService->setter($message);
  }

  /**
   * Process message from consumed queue.
   */
  public function process() {
    $this->mobileService->process();
  }
  
  /**
   * connectService: instantiate object for mobile service based on the
   * country code.
   *
   * @parm string $message
   *   Two letter country code based on the "application_id" value in
   *   the payload of the message being processed.
   *
   * @return object $mobileServiceObject
   *   An obect of a mobile service.
   */
  protected function connectService($message) {
    
    $bla = FALSE;
if ($bla) {
  $bla = TRUE;
}

    switch ($message['application_id']) {

      case 'US':
      case 'CA':
        $mobileService = new MBC_RegistrationMobile_Service_MobileCommons($message);

        break;
      
      case 'CGG':
      case 'AGG':
        $mobileService = new MBC_RegistrationMobile_Service_MobileCommons($message);

        break;

      default:
        $mobileService = NULL;

    }

    return $mobileService;
  }

}
