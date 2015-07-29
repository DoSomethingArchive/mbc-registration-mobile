<?php
/**
 * mbc-user-digest.php
 *
 * A consumer to process entries in the registrationMobileQueue via the
 * transactionalExchange. The mbp-registration-mobile application produces user
 * entries in Mobile Commons based the contents of the queue.
 */

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';
use DoSomething\MBC_RegistrationMobile\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-registration-mobile.config.inc';

// Create objects for injection into MBC_ImageProcessor
$mb = new MessageBroker($credentials, $config);
$sh = new StatHat([
  'ez_key' => $settings['stathat_ez_key'],
  'debug' => $settings['stathat_disable_tracking']
]);
$tb = new MB_Toolbox($settings);


// Kick off - block, wait for messages in queue
echo '------- mbc-registration-mobile START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
$mb->consumeMessage(array(new MBC_RegistrationMobileConsumer($mb, $sh, $tb, $settings), 'consumeRegistrationMobileQueue'));
echo '------- mbc-registration-mobile END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
