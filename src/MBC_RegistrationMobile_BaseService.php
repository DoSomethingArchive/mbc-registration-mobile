<?php
/**
 * Base abstract class to provide a template for active class to extend
 * from. Structure based on factory pattern.
 */

namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Configuration;


/*
 * MBC_UserAPICampaignActivity.class.in: Used to process the transactionalQueue
 * entries that match the campaign.*.* binding.
 */
abstract class MBC_RegistrationMobile_BaseService
{

  /**
   * Singleton instance of MB_Configuration application settings and service objects
   *
   * @var object
   */
  protected $mbConfig;

  /**
   * Message Broker connection to RabbitMQ
   *
   * @var object
   */
  protected $messageBroker;

  /**
   * Message Broker connection to RabbitMQ for Dead Letter messages.
   *
   * @var object
   */
  protected $messageBroker_deadLetter;

  /**
   * StatHat object for logging of activity
   *
   * @var object
   */
  protected $statHat;

  /**
   * Message Broker Toolbox - collection of utility methods used by many of the
   * Message Broker producer and consumer applications.
   *
   * @var object
   */
  protected $toolbox;

  /**
   * Value of message from queue to be consumed / processed.
   *
   * @var array
   */
  protected $message;

  /**
   * Connection to mobile service to send message details to.
   */
  protected $mobileServiceObject;

  /**
   * The name of the service.
   *
   * @var string
   */
  public $mobileServiceName;

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *   The message to process by the service from the connected queue.
   */
  public function __construct($message) {

    $this->mbConfig = MB_Configuration::getInstance();
    $this->messageBroker = $this->mbConfig->getProperty('messageBroker');
    $this->messageBroker_deadLetter = $this->mbConfig->getProperty('messageBroker_deadLetter');
    $this->statHat = $this->mbConfig->getProperty('statHat');
    $this->toolbox = $this->mbConfig->getProperty('mbToolbox');

    $this->message = $message;
    $targetCountry = $this->targetCountryRules($message);
    $this->mobileServiceObject = $this->connectServiceObject($targetCountry);
  }

  /**
   * Log payload with RabbitMQ objects removed for clarity.
   */
  public function reportErrorPayload() {

    $errorPayload = $this->message;
    unset($errorPayload['payload']);
    unset($errorPayload['original']);
    echo '-> message: ' . print_r($errorPayload, TRUE), PHP_EOL;
  }

  /**
   * deadLetter() - send message and related error to queue. Allows processing queues to be unblocked
   * and log problem messages with details of the error resulting from the message.
   */
  public function deadLetter($message, $location, $error) {

    $message['incidentDate'] = date(DATE_RFC2822);
    $message['location'] = $location;
    $message['error'] = $error;
    $message = json_encode($message);
    $this->messageBroker_deadLetter->publish($message, 'deadLetter');
  }

  /**
   * Collection of rules to follow to define which mobile service to submit message request.
   *
   * @todo: Move to specific service class as rules depend on the service.
   */
  private function targetCountryRules($message) {

    if (isset($message['campaign_country']) && $message['campaign_country'] != 'global') {
      return $message['campaign_country'];
    }
    if (isset($message['user_country'])) {
      return $message['user_country'];
    }
    if (isset($message['application_id'])) {
      return $message['application_id'];
    }

  }

  /**
   * Method to determine if message can be processed. Tests based on requirements of the service.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   *
   * @retun boolean
   */
  abstract public function canProcess($message);

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  abstract public function setter($message);

  /**
   * Process message from consumed queue.
   */
  abstract public function process();

}
