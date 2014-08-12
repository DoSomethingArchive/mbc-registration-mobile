<?php

use DoSomething\MBStatTracker\StatHat;

/**
 * MBC_UserRegistration class - functionality related to the Message Broker
 * consumer mbc-registration-mobile.
 *
 * See:
 * - https://github.com/dosomething/mobilecommons-php
 * - https://mobilecommons.zendesk.com/hc/en-us/articles/202052534-REST-API#ProfileUpdate
 */
class MBC_RegistrationMobile
{
  /**
   * Collect a batch of user registrations for submission to Mobile Commons from
   * the related RabbitMQ queue.
   *
   * @return array $payload
   *   Transactional queue entry details
   */
  public function consumeRegistrationMobileQueue($payload) {

    echo '------- MBC_RegistrationMobile START #' . $payload->delivery_info['delivery_tag'] . ' - ' . date('D M j G:i:s T Y') . ' -------', "\n";

    $payloadDetails = unserialize($payload->body);
    $deliveryTag = $payload->delivery_info['delivery_tag'];
    
    $config = array(
      'username' => getenv("MOBILE_COMMONS_USER"),
      'password' => getenv("MOBILE_COMMONS_PASSWORD"),
    );
    $MobileCommons = new MobileCommons($config);
    
    // https://secure.mcommons.com/campaigns/5091/opt_in_paths/170071
    $args = array(
      'phone_number' => $payloadDetails['mobile'],
      'opt_in_path_id' => 170071
    );

    if (isset($payloadDetails['email'])) {
      $args['email'] = $payloadDetails['email'];
    }
    if (isset($payloadDetails['zip'])) {
      $args['postal_code'] = $payloadDetails['zip'];
    }
    if (isset($payloadDetails['merge_vars']['FNAME'])) {
      $args['first_name'] = $payloadDetails['merge_vars']['FNAME'];
    }
    if (isset($payloadDetails['merge_vars']['LNAME'])) {
      $args['last_name'] = $payloadDetails['merge_vars']['LNAME'];
    }
    if (isset($payloadDetails['address1'])) {
      $args['street1'] = $payloadDetails['address1'];
    }
    if (isset($payloadDetails['address2'])) {
      $args['street2'] = $payloadDetails['address2'];
    }
    if (isset($payloadDetails['city'])) {
      $args['city'] = $payloadDetails['city'];
    }
    if (isset($payloadDetails['state'])) {
      $args['state'] = $payloadDetails['state'];
    }

    $profileStatus = $MobileCommons->profiles_update($args);
    
    /* Need for ack backs
    if (isset($profileStatus->profile)) {
      $this->channel->basic_ack($deliveryTag);
    }
    */

    echo '------- MBC_RegistrationMobile END #' . $payload->delivery_info['delivery_tag'] . ' - result: ' .  $result . ' - ' . date('D M j G:i:s T Y') . ' -------', "\n";
  }

}
