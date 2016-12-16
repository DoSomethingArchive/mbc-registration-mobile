<?php
/**
 * Service class specific to the Mobile Commons SMS service.
 * https://www.mobilecommons.com
 */
namespace DoSomething\MBC_RegistrationMobile;

use DoSomething\Gateway\Gambit;
use DoSomething\StatHat\Client as StatHat;
use DoSomething\MB_Toolbox\MB_Toolbox;
use DoSomething\MB_Toolbox\MB_Configuration;
use DoSomething\MB_Toolbox\MB_MobileCommons;
use \Exception;

/*
 * MBC_RegistrationMobile_Service_MobileCommons: Used to process the mobileCommonsQueue
 * entries for the Mobile Commons service.
 */
class  MBC_RegistrationMobile_Service_MobileCommons extends MBC_RegistrationMobile_BaseService
{

  /**
   * Constructor for MBC_BaseConsumer - all consumer applications should extend this base class.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function __construct($message) {

    parent::__construct($message);
    $this->mobileServiceName = 'Mobile Commons';
    $this->mbMobileCommons = $this->mbConfig->getProperty('mbMobileCommons');
    $this->gambit = $this->mbConfig->getProperty('gambit');
  }

  /**
   * Method to determine if message can be processed. Tests based on requirements of the service.
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   *
   * @retun boolean
   */
  public function canProcess($message) {

    // Mobile Commons, current supplier for US and CA requirements
    if (!isset($message['mobile'])) {
      echo '** Service_MobileCommons canProcess(): mobile not set. Mobile Commons requires a mobile number for processing.', PHP_EOL;
      parent::reportErrorPayload();
      return FALSE;
    }
    // Validate phone number based on the North American Numbering Plan
    // https://en.wikipedia.org/wiki/North_American_Numbering_Plan
    $regex = "/^(\d[\s-]?)?[\(\[\s-]{0,2}?\d{3}[\)\]\s-]{0,2}?\d{3}[\s-]?\d{4}$/i";
    if (!(preg_match( $regex, $message['mobile']))) {
      echo '** Service_MobileCommons canProcess(): Invalid phone number based on  North American Numbering Plan standard: ' .  $message['mobile'], PHP_EOL;
      return FALSE;
    }

    // Ignore activities.
    if (!empty($message['original']['activity'])) {
      $ignoreActivities = ['campaign_signup_single'];
      if (in_array($message['original']['activity'], $ignoreActivities)) {
        echo 'Ignore activity ' . $message['original']['activity'] . '.' . PHP_EOL;
        return FALSE;
      }
    }

    // Skip messages with explicitly disabled transactionals.
    if (isset($message['original']['transactionals']) && $message['original']['transactionals'] === false) {
      echo '- canProcess(), transactionals disabled.', PHP_EOL;
      return false;
    }

    return TRUE;
  }

  /**
   * Sets values for processing based on contents of message from consumed queue.
   *
   * Based on Mobilke Commons API: profile_update:
   * https://mobilecommons.zendesk.com/hc/en-us/articles/202052534-REST-API#ProfileUpdate
   *
   * @param array $message
   *  The payload of the unseralized message being processed.
   */
  public function setter($message) {

    $this->message['phone_number'] = $message['mobile'];
    unset($this->message['mobile']);
    if (isset($message['service_path_id'])) {
      $this->message['opt_in_path_id'] = $message['service_path_id'];
      unset($this->message['service_path_id']);
    }

    if (isset($message['postal_code'])) {
      $this->message['postal_code'] = $message['postal_code'];
    }
    if (isset($message['zip'])) {
      $this->message['postal_code'] = $message['zip'];
    }
    if (isset($message['first_name'])) {
      $this->message['first_name'] = $message['first_name'];
    }
    if (isset($message['last_name'])) {
      $this->message['last_name'] = $message['last_name'];
    }
    if (isset($message['street1'])) {
      $this->message['street1'] = $message['street1'];
    }
    elseif (isset($message['address1'])) {
      $this->message['street1'] = $message['address1'];
    }
    if (isset($message['street2'])) {
      $this->message['street2'] = $message['street2'];
    }
    elseif (isset($message['address2'])) {
      $this->message['street1'] = $message['address2'];
    }
    if (isset($message['city'])) {
      $this->message['city'] = $message['city'];
    }
    if (isset($message['state'])) {
      $this->message['state'] = $message['state'];
    }
    elseif (isset($message['province'])) {
      $this->message['state'] = $message['province'];
    }
    if (isset($message['country'])) {
      $this->message['country'] = $message['country'];
    }

    // Additional custom 'Date of Birth' field.
    if (!empty($message['birthdate'])) {
      $this->message['Date of Birth'] = $message['birthdate'];
    }

    // Additional fields for Lose Your V-Card campaign (2016).
    // https://www.dosomething.org/us/campaigns/lose-your-v-card
    // https://trello.com/c/tZMqE6kc/110-lose-your-v-card-signup-email-sms-data-import
    if (!empty($message['northstar_id'])) {
      $baseUrl = 'https://www.dosomething.org/us/campaigns/lose-your-v-card';
      $this->message['vcard_share_url_full']  = $baseUrl;
      $this->message['vcard_share_url_full'] .= '?source=user/';
      $this->message['vcard_share_url_full'] .= $message['northstar_id'];

      // Wrapping URL in parentesses tells Liquid to automatically shorten it.
      $this->message['vcard_share_url_full'] =
        '((' . $this->message['vcard_share_url_full'] . '))';
    }

    // CGG - custom profile fields
    // Used in https://secure.mcommons.com/campaigns/151777/opt_in_paths/219619
    if (strtoupper($message['application_id']) == 'CGG' && isset($message['original']['candidate_name'])) {
      $this->message['cgg2016_1st_vote'] = $message['original']['candidate_name'];
    }

    // AfterSchool user import - custom profile fields
    if (isset($message['original']['hs_name'])) {
      $this->message['hs_name'] = $message['original']['hs_name'];
      $this->message['school_name'] = $message['original']['hs_name'];
    }
    if (isset($message['original']['school_name'])) {
      $this->message['school_name'] = $message['original']['school_name'];
    }
    if (isset($message['original']['afterschool_optin'])) {
      $this->message['afterschool_optin'] = $message['original']['afterschool_optin'];
    }

  }

  /**
   * Process message from consumed queue.
   */
  public function process() {
    if ($this->shouldBeProcessedOnGambit()) {
      $this->processOnGambit();
    } elseif (!empty($this->message['opt_in_path_id'])) {
      $this->processOnMobileCommons();
    } else {
      $error = '** Service_MobileCommons process(): Can\'t dispatch message to '
        . $this->message['phone_number'] . ' , Neither Mobile Commons Campaign'
        . ' nor Gambit CampaignBot Campaign is available.';
      echo $error . PHP_EOL;
      parent::reportErrorPayload();
      $this->messageBroker->sendNack($this->message['payload'], false, false);
      return false;
    }
    return true;
  }

  /**
   * connectService: instantiate object for mobile service based on the
   * country code.
   *
   * @param string $affiliate
   *   The country code of the site that generated the message and thus the mobile service
   *   that needs to be connected to.
   *
   * @return object $mobileServiceObject
   *   An object of a mobile service.
   */
  public function connectServiceObject($affiliate) {

    $mobileCommonsConfig = $this->mbConfig->getProperty('mobileCommons_config');

    // @todo: trap undefined $affiliate values.
    $config = array(
      'username' => $mobileCommonsConfig[$affiliate]['username'],
      'password' => $mobileCommonsConfig[$affiliate]['password'],
      'company_key' => $mobileCommonsConfig[$affiliate]['company_key'],
    );
    echo '-> connectServiceObject company_key: ' . $mobileCommonsConfig[$affiliate]['company_key'], PHP_EOL;

    try {
      $mobileServiceObject = new \MobileCommons($config);
      if (!(is_object($mobileServiceObject))) {
        throw new Exception('connectServiceObject(): Connection to Mobile Commons failed.');
      }
    }
    catch (Exception $e) {
      $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Service_MobileCommons: object connection error', 1);
      throw new Exception($e->getMessage());
    }

    return $mobileServiceObject;
  }

  /**
   * Determines whether processing should be done on Gambit.
   */
  private function shouldBeProcessedOnGambit() {
    $original = &$this->message['original'];

    // Ignore other activities than signup.
    $allowedActivities  = [
      'campaign_signup',
      // 'user_welcome-niche',
    ];
    if (empty($original['activity']) || !in_array($original['activity'], $allowedActivities)) {
      return false;
    }

    // Only with existing campaign id and signup id.
    if (empty($original['event_id']) || empty($original['signup_id'])) {
      return false;
    }

    $campaign_id = (int) $original['event_id'];

    // Only if enabled on Gambit.
    // Todo: cache.
    $gambitCampaign = false;
    try {
      $gambitCampaign = $this->gambit->getCampaign($campaign_id);
    } catch (Exception $e) {
      echo 'Can\'t access Gambit: ' . $e->getMessage();
      return false;
    }

    if (empty($gambitCampaign)) {
      echo '**  Gambit * Incorrect campaign.';
      return false;
    }

    // If Campaignbot is not enabled for the campaign:
    if ($gambitCampaign->campaignbot != true) {
      echo '** Gambit * Campaignbot is not enabled for campaign id '
        . $campaign_id . ', ignoring.' . PHP_EOL;
      return false;
    }

    // Ignore sources.
    if (!empty($original['source'])) {
      $ignoredSources = [
        // Ignore sms signup, those has aleady been processed on Gambit.
        'sms-mobilecommons',
      ];
      if (in_array($original['source'], $ignoredSources)) {
        echo '** Gambit * Ignore source: ' . $original['source'] . '.' . PHP_EOL;
        return false;
      }
    }

    // Todo: check id.
    return true;
  }

  /**
   * Gambit processing.
   */
  private function processOnGambit() {
    $payload = $this->message['payload'];

    $signup_id = $this->message['original']['signup_id'];
    $signup_source = !empty($this->message['source'])
      ? $this->message['source'] : Gambit::SIGNUP_SOURCE_FALLBACK;

    echo '-> Processing signup on Gambit: sid = ' . $signup_id
      . ', source = ' . $signup_source . PHP_EOL;

    try {
      $result = $this->gambit->createSignup($signup_id, $signup_source);
      if ($result) {
        $this->messageBroker->sendAck($payload);
      } else {
        $error = '-> Gambit unknown error.' . PHP_EOL;
        echo $error;
        parent::deadLetter($this->message, 'processOnGambit', $error);
        $this->messageBroker->sendNack($payload, false, false);
      }
    } catch (Exception $e) {
      echo '-> Gambit error: ' . $e->getMessage() . PHP_EOL;
      parent::deadLetter($this->message, 'processOnGambit', $e);
      $this->messageBroker->sendNack($payload, false, false);
    }
  }

  /**
   * Mobile commons processing.
   */
  private function processOnMobileCommons() {
    $payload = $this->message['payload'];
    unset($this->message['payload']);
    unset($this->message['original']);

    try {

      $status = (array)$this->mobileServiceObject->profiles_update($this->message);
      if (isset($status['error'])) {
        echo '- Error - ' . $status['error']->attributes()->{'message'} , PHP_EOL;
        echo '  Submitted: ' . print_r($this->message, TRUE), PHP_EOL;
        parent::deadLetter($this->message, 'MBC_RegistrationMobile_Service_MobileCommons->process()->mobileServiceObject->profiles_update Error', $status['error']->attributes()->{'message'});
        $this->messageBroker->sendNack($payload, false, false);
        $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Service_MobileCommons: profiles_update error: ' . $status['error']->attributes()->{'message'}, 1);
        throw new Exception($status['error']->attributes()->{'message'});
      }
      else {
        $this->messageBroker->sendAck($payload);
        $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Service_MobileCommons: profiles_update success', 1);
      }

      echo '-> MBC_RegistrationMobile_Service_MobileCommons->process: ' . $this->message['phone_number'] . ' -------', PHP_EOL;
    }
    catch (Exception $e) {
      $this->statHat->ezCount('mbc-registration-mobile: MBC_RegistrationMobile_Service_MobileCommons: profiles_update error', 1);
      throw new Exception($e->getMessage());
    }
  }

}
