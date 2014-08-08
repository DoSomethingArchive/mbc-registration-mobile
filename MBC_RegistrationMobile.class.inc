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
    
    $config = array(
      'username' => getenv("MOBILE_COMMONS_USER"),
      'password' => getenv("MOBILE_COMMONS_PASSWORD")
    );
    $MobileCommons = new MobileCommons($config);
    
    $args = array(
      /*
      phone_number (Required)
      email
      postal_code
      first_name
      last_name
      street1
      street2
      city
      state
      country
      Custom Column (Any custom column name)
      opt_in_path_id: This will subscribe the user to this campaign and send the message flow in this opt-in path. The value should be your opt-in path key.
      */
    );

    $profileStatus = $MobileCommons->profiles_update($args);
    
    echo '------- MBC_RegistrationMobile END #' . $payload->delivery_info['delivery_tag'] . ' - result: ' .  $result . ' - ' . date('D M j G:i:s T Y') . ' -------', "\n";
  }

}
