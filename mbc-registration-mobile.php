<?php
/**
 * mbc-user-digest.php
 *
 * A consumer to process entries in the registrationMobileQueue via the
 * transactionalExchange. The mbp-registration-mobile application produces user
 * entries in Mobile Commons based the contents of the queue.
 */

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings common to the Message Broker system
// symlinks in the project directory point to the actual location of the files
require __DIR__ . '/mb-secure-config.inc';
require __DIR__ . '/mb-config.inc';

require __DIR__ . '/MBC_RegistrationMobile.class.inc';

// Settings
$credentials = array(
  'host' =>  getenv("RABBITMQ_HOST"),
  'port' => getenv("RABBITMQ_PORT"),
  'username' => getenv("RABBITMQ_USERNAME"),
  'password' => getenv("RABBITMQ_PASSWORD"),
  'vhost' => getenv("RABBITMQ_VHOST"),
);
$config = array(
  'exchange' => array(
    'name' => getenv("MB_TRANSACTIONAL_EXCHANGE"),
    'type' => getenv("MB_TRANSACTIONAL_EXCHANGE_TYPE"),
    'passive' => getenv("MB_TRANSACTIONAL_EXCHANGE_PASSIVE"),
    'durable' => getenv("MB_TRANSACTIONAL_EXCHANGE_DURABLE"),
    'auto_delete' => getenv("MB_TRANSACTIONAL_EXCHANGE_AUTO_DELETE"),
  ),
  'queue' => array(
    array(
      'name' => getenv("MB_MOBILE_COMMONS_QUEUE"),
      'passive' => getenv("MB_MOBILE_COMMONS_QUEUE_PASSIVE"),
      'durable' => getenv("MB_MOBILE_COMMONS_QUEUE_DURABLE"),
      'exclusive' => getenv("MB_MOBILE_COMMONS_QUEUE_EXCLUSIVE"),
      'auto_delete' => getenv("MB_MOBILE_COMMONS_QUEUE_AUTO_DELETE"),
      'bindingKey' => getenv("MB_MOBILE_COMMONS_QUEUE_TOPIC_MB_TRANSACTIONAL_EXCHANGE_PATTERN"),
    ),
  ),
  /* Needed for implimenting ack backs
  'consume' => array(
    'consumer_tag' => getenv("MB_TRANSACTIONAL_CONSUME_TAG"),
    'no_local' => getenv("MB_TRANSACTIONAL_CONSUME_NO_LOCAL"),
    'no_ack' => getenv("MB_TRANSACTIONAL_CONSUME_NO_ACK"),
    'exclusive' => getenv("MB_TRANSACTIONAL_CONSUME_EXCLUSIVE"),
    'nowait' => getenv("MB_TRANSACTIONAL_CONSUME_NOWAIT"),
  ),
  */
);
$settings = array(
  'stathat_ez_key' => getenv("STATHAT_EZKEY"),
);

echo '------- mbc-registration-mobile START: ' . date('D M j G:i:s T Y') . ' -------', "\n";


// Kick off
$mb = new MessageBroker($credentials, $config);
$mb->consumeMessage(array(new MBC_RegistrationMobile(), 'consumeRegistrationMobileQueue'));

echo '------- mbc-registration-mobile END: ' . date('D M j G:i:s T Y') . ' -------', "\n";
