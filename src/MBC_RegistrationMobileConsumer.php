<?php
namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use DoSomething\MBC_RegistrationMobile\MBC_RegistrationMobile;

/*
 * MBC_RegistrationMobileConsumer.class.in: Used to process the mobileCommonsQueue
 * entries that match the campaign.signup.* and user.registration.* bindings. Support
 * for different mobile services by affiliate is based on message application_id
 * (affiliate country) resulting in in instantiation of the appropreate service class.
 */
class MBC_RegistrationMobileConsumer extends MB_Toolbox_BaseConsumer
{

  /**
   * Values submitted in the potential mobile activity.
   */
  protected $mobileSubmission;

  /**
   * Initial method triggered by blocked call in mbc-registration-mobile.php. The $payload is the
   * contents of the message being processed from the queue.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeRegistrationMobileQueue($payload) {

    echo '- mbc-registration-mobile - MBC_RegistrationMobileConsumer->consumeRegistrationMobileQueue() START', PHP_EOL;

    parent::consumeQueue($payload);
    $this->setter($this->message);
    
    if ($this->canProcess()) {
      
      // Instantiation of service provider based on affiliate
      if ($this->mobileSubmission['application_id'] == 'US') {
        $mobileService = new MBC_RegistrationMobile_MobileCommons($this->messageBroker,  $this->statHat,  $this->toolbox, $this->settings);
      }
      elseif  ($this->mobileSubmission['application_id'] == 'CA') {
        $mobileService  = new MBC_RegistrationMobile_MobileCommons($this->messageBroker,  $this->statHat,  $this->toolbox, $this->settings);
      }
      
      if ($mobileService->canProcess($this->mobileSubmission)) {
        $mobileService->setter($this->mobileSubmission);
        $mobileService->process();
        
        // Log processing of mobile user
        // $ip->log();
      }

      // Destructor
      unset($mobileService);
      
    }

    echo '- mbc-registration-mobile - MBC_RegistrationMobileConsumer->consumeRegistrationMobileQueue() END', PHP_EOL;
  }

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the message being processed.
   */
  protected function setter($message) {

    $this->mobileSubmission['application_id'] = $message['application_id'];

    if (isset($message['mobile'])) {
      $this->mobileSubmission['mobile'] = $message['mobile'];
    }
    if (isset($message['opt_in_path_id'])) {
      $this->mobileSubmission['service_path_id'] = $message['opt_in_path_id'];
    }

    // Optional user details
    if (isset($message['email'])) {
      $this->mobileSubmission['email'] = $message['email'];
    }
    if (isset($message['merge_vars']['FNAME'])) {
      $this->mobileSubmission['first_name'] = $message['merge_vars']['FNAME'];
    }
    elseif ($payloadDetails['first_name']) {
      $this->mobileSubmission['first_name'] = $payloadDetails['first_name'];
    }
    if (isset($message['merge_vars']['LNAME'])) {
      $this->mobileSubmission['last_name'] = $message['merge_vars']['LNAME'];
    }

    if (isset($message['address1'])) {
      $this->mobileSubmission['address1'] = $message['address1'];
    }
    if (isset($message['address2'])) {
      $this->mobileSubmission['address2'] = $message['address2'];
    }
    if (isset($message['city'])) {
      $this->mobileSubmission['city'] = $message['city'];
    }
    if (isset($message['state'])) {
      $this->mobileSubmission['state'] = $message['state'];
    }
    if (isset($message['country'])) {
      $this->mobileSubmission['country'] = $message['country'];
    }
    if (isset($message['zip'])) {
      $this->mobileSubmission['postal_code'] = $message['zip'];
    }

  }
  
  /**
   * Method to determine if message can be processed. Tests based on available service details and
   * requirements of the current services.
   *
   * @retun boolean
   */
  protected function canProcess() {
    
    if ($this->mobileSubmission['application_id'] != 'US' && $this->mobileSubmission['application_id'] == 'CA') {
      echo '** Unsupported affiliate country: ' . $this->mobileSubmission['application_id'] . ', ' . $this->mobileSubmission['phone_number'] . ' not submitted to a mobile service.', PHP_EOL;
      return FALSE;
    }
    
    if (!isset($this->mobileSubmission['mobile'])) {
      echo '** Phone number not found in message, skip processing.', PHP_EOL;
      return FALSE;
    }
    if (!isset($this->mobileSubmission['service_path_id'])) {
      echo '** service_path_id (opt in) not found in message, skip processing.', PHP_EOL;
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Method to process image.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  protected function process() {
  }

}