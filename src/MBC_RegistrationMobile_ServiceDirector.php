<?php

namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Configuration;

/*
 * MBC_RegistrationMobile_Service_MobileCommons: Used to process the mobileCommonsQueue
 * entries for the Mobile Commons service.
 */
class  MBC_RegistrationMobile_ServiceDirector
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

    $this->mbConfig = MB_Configuration::getInstance();
    $this->statHat = $this->mbConfig->getProperty('statHat');
    
    $this->mobileService = $this->serviceFactory($message);
  }
  
  /**
   * serviceFactory: instantiate object for mobile service based on the
   * country code. Different service providers can be supported by instantiating an object specific to the message application. Currently only Mobile Commons is
   * supported.
   *
   * @parm string $message
   *   Two letter country code based on the "application_id" value in
   *   the payload of the message being processed.
   *
   * @return object $mobileServiceObject
   *   An obect of a mobile service.
   */
  public function serviceFactory($message) {

    echo '- MBC_RegistrationMobile_ServiceDirector->serviceFactory() application_id: ' . $message['application_id'], PHP_EOL;

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

  /**
   * getService: Provide object of SMS service provider.
   *
   * @return object $mobileServiceObject
   *   An obect of a mobile service.
   */
  public function getService() {
    return $this->mobileService;
  }

}
