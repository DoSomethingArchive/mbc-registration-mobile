<?php
/**
 * Class related to mobile services used by DoSomething.org.
 */

namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox_BaseConsumer;
use \Exception;

/**
 * MBC_RegistrationMobileConsumer: Used to process the mobileCommonsQueue
 * entries that match the campaign.signup.* and user.registration.* bindings. Support
 * for different mobile services by affiliate is based on message application_id
 * (affiliate country) resulting in in instantiation of the appropreate service class.
 */
class MBC_RegistrationMobile_Consumer extends MB_Toolbox_BaseConsumer
{
  const RETRY_SECONDS = 20;

  /**
   * Composed values processed from the original message.
   */
  protected $mobileMessage;

  /**
   * Initial method triggered by blocked call in mbc-registration-mobile.php.
   *
   * @param array $payload
   *   The contents of the queue entry message being processed.
   */
  public function consumeRegistrationMobileQueue($payload) {

    echo '------ mbc-registration-mobile - MBC_RegistrationMobile_Consumer->consumeRegistrationMobileQueue() - ' . date('j D M Y G:i:s T') . ' START ------', PHP_EOL . PHP_EOL;

    parent::consumeQueue($payload);

    try {

      if ($this->canProcess()) {

        $this->logConsumption(['mobile']);
        $this->setter($this->message);
        $this->process();
        $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Consumer: process', 1);

        // Ack in Service process() due to nested try/catch
      }
      else {
        echo '- failed canProcess(), removing from queue.', PHP_EOL;
        $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Consumer: skipping', 1);
        $this->messageBroker->sendAck($this->message['payload']);
      }
    }
    catch(Exception $e) {

      if (!(strpos($e->getMessage(), 'Connection timed out') === false)) {
        echo '** Connection timed out... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e-getMessage(), PHP_EOL;
        $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Consumer: Exception: Connection timed out', 1);
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
      }
      elseif (!(strpos($e->getMessage(), 'Failed to connect') === false)) {
        echo '** Failed to connect... waiting before retrying: ' . date('j D M Y G:i:s T') . ' - getMessage(): ' . $e->getMessage(), PHP_EOL;
        sleep(self::RETRY_SECONDS);
        $this->messageBroker->sendNack($this->message['payload']);
        echo '- Nack sent to requeue message: ' . date('j D M Y G:i:s T'), PHP_EOL . PHP_EOL;
        $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Consumer: Exception: Failed to connect', 1);
      }
      else {
        echo '- Not timeout or connection error, message to deadLetterQueue: ' . date('j D M Y G:i:s T'), PHP_EOL;
        echo '- Error message: ' . $e->getMessage(), PHP_EOL;
        $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Consumer: Exception: deadLetter', 1);
        parent::deadLetter($this->message, 'MBC_RegistrationMobile_Consumer->consumeRegistrationMobileQueue() Error', $e->getMessage());
      }
    }

    // @todo: Throttle the number of consumers running. Based on the number of messages
    // waiting to be processed start / stop consumers. Make "reactive"!
    $queueStatus = parent::queueStatus('transactionalQueue');

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
    elseif (!(isset($this->message['user_country']))) {
      echo '** WARNING: user_country not set.', PHP_EOL;
    }

    if (!isset($this->message['mobile'])) {
      echo '** canProcess(): mobile number was not defined.', PHP_EOL;
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
    if (isset($message['campaign_country'])) {
      $this->mobileMessage['campaign_country'] = $message['campaign_country'];
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
   * Method to process mobile number.
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
        echo '** process(): Error sending mobile number: ' . $this->mobileMessage['mobile'] . ' to mobile ' . $mobileService->mobileServiceName . ' service for user signup.', PHP_EOL;
        throw $e;
      }

    }
    else {
      echo 'Service canProcess() failed, removing from queue.', PHP_EOL;
      $this->messageBroker->sendAck($this->mobileMessage['payload']);
    }

    // Cleanup for next message
    unset($this->mobileMessage);
  }

  /**
   * logConsumption(): Extend to log the status of processing a specific message
   * element as well as the user_country and country.
   *
   * @param string $targetName
   */
  protected function logConsumption($targetNames = null) {

    echo '** Consuming ';
    $targetNameFound = false;
    foreach ($targetNames as $targetName) {
      if (isset($this->message[$targetName])) {
        if ($targetNameFound) {
           echo ', ';
        }
        echo $targetName . ': ' . $this->message[$targetName];
        $targetNameFound = true;
      }
    }
    if ($targetNameFound) {
      if (isset($this->message['user_country'])) {
        echo ' from: ' .  $this->message['user_country'] . ' doing: ' . $this->message['activity'], PHP_EOL;
      } else {
        echo ', user_country not defined.', PHP_EOL;
      }
    }
    else {
      echo 'xx Target property not found in message.', PHP_EOL;
    }
  }

}
