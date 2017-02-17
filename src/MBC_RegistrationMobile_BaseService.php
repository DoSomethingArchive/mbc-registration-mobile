<?php
/**
 * Base abstract class to provide a template for active class to extend
 * from. Structure based on factory pattern.
 */

namespace DoSomething\MBC_RegistrationMobile;

use \Exception;
use PhpAmqpLib\Message\AMQPMessage;

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
    $this->mbToolbox = $this->mbConfig->getProperty('mbToolbox');

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
   *
   * @param String $message
   *   The error message that triggered sending message to deadLetter queue
   * @param String $location
   *   Where the event took place
   * @param String|Exception $error
   *   The error message related to sending the message.
   *
   * @return true
   */
  public function deadLetter($message, $location, $error)
  {
    // Prepare new message to save to deadLetterQueue.
    $deadLetter = [];

    // Store original payload.
    if (!empty($message['original'])) {
      $deadLetter['message'] = $message['original'];
    }

    // Collect error metadata.
    $deadLetter['metadata'] = [];
    $metadata = &$deadLetter['metadata'];

    // Save AMQP metadata if present.
    if (!empty($message['payload']) && $message['payload'] instanceof AMQPMessage) {
      $metadata['amqp'] = [];
      $metadata['amqp']['exchange']     = $message['payload']->get('exchange');
      $metadata['amqp']['routing_key']  = $message['payload']->get('routing_key');
      $metadata['amqp']['consumer_tag'] = $message['payload']->get('consumer_tag');
    }

    // Date and location.
    $metadata['error'] = [];
    $metadata['error']['date'] = date(DATE_RFC2822);
    $metadata['error']['locationText'] = $location;

    // Accept exceptions
    if ($error instanceof Exception) {
      // Log exception type.
      $metadata['error']['exception'] = get_class($error);

      // Message
      $metadata['error']['message'] = $error->getMessage();

      // Stpre exception code when it's set expilitly.
      if ($exceptionCode = $error->getCode()) {
      $metadata['error']['exceptionCode'] = $exceptionCode;
      }

      // Exception trace is different from normal trace.
      $metadata['error']['exceptionTrace'] = $error->getTraceAsString();
    } else {
      $metadata['error']['message'] = $error;
    }

    // Get backtrace as a string using output buffering,
    // it's safer than var_export().
    ob_start();
    debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $metadata['error']['trace'] = ob_get_clean();

    $deadLetterJson = json_encode($deadLetter);
    $this->messageBroker_deadLetter->publish($deadLetterJson, 'deadLetter');
    $this->statHat->ezCount('MB_Toolbox: MB_Toolbox_BaseConsumer: deadLetter', 1);

    return true;
  }

  /**
   * Collection of rules to follow to define which mobile service to submit message request.
   *
   * @todo: Move to specific service class as rules depend on the service.
   */
  private function targetCountryRules($message) {

    // Disabled as needs to be moved to each service class. When other services besides Mobile Commons
    // are supported for the "US" app this will need to be in place.
    /*
    if (isset($message['campaign_country']) && $message['campaign_country'] != 'global') {
      return $message['campaign_country'];
    }
    if (isset($message['user_country'])) {
      return $message['user_country'];
    }
    */
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
