<?php
namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;

/**
 * MBC_RegistrationMobileConsumer.class.in: Used to process the mobileCommonsQueue
 * entries that match the campaign.signup.* and user.registration.* bindings. Support
 * for different mobile services by affiliate is based on message application_id
 * (affiliate country) resulting in in instantiation of the appropreate service class.
 */
class MBC_RegistrationMobile_Consumer extends MB_Toolbox_BaseConsumer
{

  /**
   * Values submitted in potential mobile activity message.
   */
  protected $mobileMessage;

  /**
   * Initial method triggered by blocked call in mbc-registration-mobile.php. The $payload is the
   * contents of the message being processed from the queue.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  public function consumeRegistrationMobileQueue($payload) {

    echo '- mbc-registration-mobile - MBC_RegistrationMobile_Consumer->consumeRegistrationMobileQueue() START', PHP_EOL;

    parent::consumeQueue($payload);
    $this->setter($this->message);
    
    if (self::canProcess($this->mobileMessage)) {
      
      $mobileServiceDirector = new MBC_RegistrationMobile_ServiceDirector($this->mobileMessage);
      $mobileService = $mobileServiceDirector->getService();
      
      if ($mobileService->canProcess($this->mobileMessage)) {
        $mobileService->setter($this->mobileMessage);
        $mobileService->process();
        
        // Log processing of mobile user
        // $ip->log();
      }

      // Destructor
      unset($mobileService);
      
    }
    else {
      // ack maessage
    }

    unset($this->mobileMessage);
    echo '- mbc-registration-mobile - MBC_RegistrationMobile_Consumer->consumeRegistrationMobileQueue() END', PHP_EOL;
  }

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the message being processed.
   */
  protected function setter($message) {

    $this->mobileMessage['original'] = $message['original'];
    $this->mobileMessage['payload'] = $message['payload'];

    if (isset($message['application_id'])) {
      $this->mobileMessage['application_id'] = $message['application_id'];
    }

    // Set by origin of where user data was collected - typically Message
    // Broker user import but could also be external producers
    if (isset($message['source'])) {
      $this->mobileMessage['source'] = $message['source'];
    }
    if (isset($message['mobile'])) {
      $this->mobileMessage['mobile'] = $message['mobile'];
    }
    elseif (isset($message['mobile_number'])) {
      $this->mobileMessage['mobile'] = $message['mobile_number'];
    }
    elseif (isset($message['phone_number'])) {
      $this->mobileMessage['mobile'] = $message['phone_number'];
    }
    if (isset($message['mc_opt_in_path_id'])) {
      $this->mobileMessage['service_path_id'] = $message['mc_opt_in_path_id'];
    }

    // Optional user details
    if (isset($message['email'])) {
      $this->mobileMessage['email'] = $message['email'];
    }
    if (isset($message['merge_vars']['FNAME'])) {
      $this->mobileMessage['first_name'] = $message['merge_vars']['FNAME'];
    }
    elseif (isset($message['first_name'])) {
      $this->mobileMessage['first_name'] = $message['first_name'];
    }
    if (isset($message['merge_vars']['LNAME'])) {
      $this->mobileMessage['last_name'] = $message['merge_vars']['LNAME'];
    }
    elseif (isset($message['last_name'])) {
      $this->mobileMessage['last_name'] = $message['last_name'];
    }
    if (isset($message['birthdate']) && (is_int($message['birthdate']) || ctype_digit($message['birthdate']))) {
      $this->mobileMessage['birthdate'] = date('Y-m-d', $message['birthdate']);
    }
    if (isset($message['birthdate_timestamp'])) {
      $this->mobileMessage['birthdate'] = date('Y-m-d', $message['birthdate_timestamp']);
    }
    if (isset($payloadDetails['birthdate_timestamp'])) {
      $this->mobileMessage['BirthYear'] = date('Y', $message['birthdate_timestamp']);
    }

    if (isset($message['address1'])) {
      $this->mobileMessage['address1'] = $message['address1'];
    }
    if (isset($message['address2'])) {
      $this->mobileMessage['address2'] = $message['address2'];
    }
    if (isset($message['city'])) {
      $this->mobileMessage['city'] = $message['city'];
    }
    if (isset($message['state'])) {
      $this->mobileMessage['state'] = $message['state'];
    }
    if (isset($message['country'])) {
      $this->mobileMessage['country'] = $message['country'];
    }
    if (isset($message['zip'])) {
      $this->mobileMessage['postal_code'] = $message['zip'];
    }

  }
  
  /**
   * Method to determine if message can / should be processed. Conditions based on business
   * logic for submitted mobile numbers and related message values.
   *
   * @retun boolean
   */
  protected function canProcess() {
    
    if (!isset($this->message['application_id'])) {
      echo '** application_id not set: ' . print_r($this->mobileSubmission, TRUE), PHP_EOL;
      return FALSE;
    }

    $supportedApps = ['US', 'CA', 'CGG', 'AGG'];
    if (!in_array($this->message['application_id'], $supportedApps)) {
      echo '** Unsupported application: ' . $this->mobileSubmission['application_id'] . ', ' .
        $this->message['phone_number'] . ' was not submitted to a mobile service.', PHP_EOL;
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