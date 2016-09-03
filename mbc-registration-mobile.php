<?php
/**
 * mbc-user-digest.php
 *
 * A consumer to process entries in the registrationMobileQueue via the
 * transactionalExchange. The mbp-registration-mobile application produces user
 * entries in Mobile Commons based the contents of the queue.
 */

use DoSomething\MBC_RegistrationMobile\MBC_RegistrationMobile_Consumer;

date_default_timezone_set('America/New_York');
define('CONFIG_PATH',  __DIR__ . '/messagebroker-config');
// The number of messages for the consumer to reserve with each callback
// See consumeMwessage for further details.
// Necessary for parallel processing when more than one consumer is running on the same queue.
define('QOS_SIZE', 1);

// Manage enviroment setting
if (isset($_GET['environment']) && allowedEnviroment($_GET['environment'])) {
    define('ENVIRONMENT', $_GET['environment']);
} elseif (isset($argv[1])&& allowedEnviroment($argv[1])) {
    define('ENVIRONMENT', $argv[1]);
} elseif ($env = loadConfig() && defined('ENVIRONMENT')) {
    echo 'environment.php exists, ENVIRONMENT defined as: ' . ENVIRONMENT, PHP_EOL;
} elseif (allowedEnviroment('local')) {
    define('ENVIRONMENT', 'local');
}

// Load up the Composer autoload magic
require_once __DIR__ . '/vendor/autoload.php';

// Load configuration settings specific to this application
require_once __DIR__ . '/mbc-registration-mobile.config.inc';

// Kick off - block, wait for messages in queue
echo '------- mbc-registration-mobile START - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;
$mb = $mbConfig->getProperty('messageBroker');
$mb->consume(array(new MBC_RegistrationMobile_Consumer(), 'consumeRegistrationMobileQueue'), QOS_SIZE);
echo '------- mbc-registration-mobile END - ' . date('j D M Y G:i:s T') . ' -------', PHP_EOL;

/**
 * Test if environment setting is a supported value.
 *
 * @param string $setting Requested enviroment setting.
 *
 * @return boolean
 */
function allowedEnviroment($setting)
{

    $allowedEnviroments = [
        'local',
        'dev',
        'prod'
    ];

    if (in_array($setting, $allowedEnviroments)) {
        return true;
    }

    return false;
}

/**
 * Gather configuration settings for current application environment.
 *
 * @return boolean
 */
function loadConfig() {

    // Check that environment config file exists
    if (!file_exists('environment.php')) {
        return false;
    }
    include('./environment.php');

    return true;
}
