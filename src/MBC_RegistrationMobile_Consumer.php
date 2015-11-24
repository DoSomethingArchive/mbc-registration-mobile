<?php
namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use \Exception;

/**
 * MBC_RegistrationMobileConsumer.class.in: Used to process the mobileCommonsQueue
 * entries that match the campaign.signup.* and user.registration.* bindings. Support
 * for different mobile services by affiliate is based on message application_id
 * (affiliate country) resulting in in instantiation of the appropreate service class.
 */
class MBC_RegistrationMobile_Consumer extends MB_Toolbox_BaseConsumer
{

  /**
   *
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

    echo '------ mbc-registration-mobile - MBC_RegistrationMobile_Consumer->consumeRegistrationMobileQueue() - ' . date('j D M Y G:i:s T') . ' START ------', PHP_EOL . PHP_EOL;

    parent::consumeQueue($payload);

    if (isset($this->message['mobile'])) {
      echo '** Consuming: ' . $this->message['mobile'];
      if (isset($this->message['user_country'])) {
        echo ' from: ' .  $this->message['user_country'], PHP_EOL;
      } else {
        echo ', user_country not defined.', PHP_EOL;
      }
    }
    else {
      echo 'xx Skipping, mobile not defined.', PHP_EOL;
    }

    if ($this->canProcess()) {

      try {

        $this->setter($this->message);
        $this->process();
        $this->messageBroker->sendAck($this->message['payload']);
      }
      catch(Exception $e) {
        echo 'Error sending mobile number: ' . $this->message['mobile'] . ' to mobile service for user signup. Error: ' . $e->getMessage();
      }

    }
    else {
      echo '- ' . $this->message['mobile'] . ' failed canProcess(), removing from queue.', PHP_EOL;
      $this->messageBroker->sendAck($this->message['payload']);
    }

    // @todo: Throttle the number of consumers running. Based on the number of messages
    // waiting to be processed start / stop consumers. Make "reactive"!
    $queueMessages = parent::queueStatus('transactionalQueue');
    echo '- queueMessages ready: ' . $queueMessages['ready'], PHP_EOL;
    echo '- queueMessages unacked: ' . $queueMessages['unacked'], PHP_EOL;

    echo  PHP_EOL . '------ mbc-registration-mobile - MBC_RegistrationMobile_Consumer->consumeRegistrationMobileQueue() - ' . date('j D M Y G:i:s T') . ' END ------', PHP_EOL . PHP_EOL;
  }

  /**
   * Method to determine if message can / should be processed. Conditions based on business
   * logic for submitted mobile numbers and related message values.
   *
   * @retun boolean
   */
  protected function canProcess() {

    if (!isset($this->message['application_id'])) {
      echo '** canProcess(): application_id not set.', PHP_EOL;
      parent::reportErrorPayload();
      return FALSE;
    }

    $supportedApps = ['US', 'CA', 'CGG', 'AGG', 'MUI'];
    if (!in_array($this->message['application_id'], $supportedApps)) {
      echo '** canProcess(): Unsupported application: ' . $this->message['application_id'], PHP_EOL;
      parent::reportErrorPayload();
      return FALSE;
    }

    $supportedCountries = ['US', 'CA', 'MX', 'BR'];
    if (isset($this->message['user_country']) && !in_array($this->message['user_country'], $supportedCountries)) {
      echo '** canProcess(): Unsupported user_country: ' . $this->message['user_country'], PHP_EOL;
      return FALSE;
    }
    else {
      echo '** WARNING: user_country not set.', PHP_EOL;
    }

    if (!isset($this->message['mobile'])) {
      echo '** canProcess(): mobile number was not submitted.', PHP_EOL;
      parent::reportErrorPayload();
      return FALSE;
    }

    return TRUE;
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
    if (isset($message['user_country'])) {
      $this->mobileMessage['user_country'] = $message['user_country'];
    }

    // @todo: application_id needs to be defined in mbc-user-import
    // MUI - Machine User Import
    // https://github.com/DoSomething/mbc-user-import/issues/44
    if (!(isset($message['application_id'])) &&
        ($this->message['source'] == 'niche' || $this->message['source'] == 'att-ichannel' || $this->message['source'] == 'hercampus' || $this->message['source'] == 'teenlife')) {
      echo '** application_id not set BUT source is from mbc-user-import. Setting application_id to MUI, should be addressed future fix in  mbc-user-import.', PHP_EOL;
      $this->message['application_id'] = 'MUI';
      $this->mobileMessage['application_id'] = 'MUI';
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
    elseif (isset($message['mobile_opt_in_path_id'])) {
      $this->mobileMessage['service_path_id'] = $message['mobile_opt_in_path_id'];
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
   * Method to process image.
   *
   * @param array $payload
   *   The contents of the queue entry
   */
  protected function process() {

    $mobileServiceDirector = new MBC_RegistrationMobile_ServiceDirector($this->mobileMessage);
    $mobileService = $mobileServiceDirector->getService();

    if ($mobileService->canProcess($this->mobileMessage)) {

      try {
        $mobileService->setter($this->mobileMessage);
        $mobileService->process();
      }
      catch(Exception $e) {
        echo 'Error sending mobile number: ' . $this->message['mobile'] . ' to mobile service for user signup. Error: ' . $e->getMessage();

        // Trow Exception to fall back to next try/catch

      }

    }

  }

}
