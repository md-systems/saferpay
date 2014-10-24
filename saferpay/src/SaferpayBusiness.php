<?php

namespace Drupal\payment_saferpay;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\UrlGenerator;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\payment\Entity\Payment;
use GuzzleHttp\ClientInterface;

class SaferPaybusiness {

  use StringTranslationTrait;

  protected $settings = array();

  /**
   * @var \Drupal\Core\Routing\UrlGenerator
   */
  protected $urlGenerator;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Drupal\payment\Entity\Payment
   */
  protected $payment;

  function __construct(UrlGenerator $url_generator, LanguageManagerInterface $language_manager, ClientInterface $http_client) {
    $this->urlGenerator = $url_generator;
    $this->languageManager = $language_manager;
    $this->httpClient = $http_client;
  }

  public function setSettings($settings) {
    $this->settings = $settings;
  }

  public function getSetting($key) {
    if (!$this->hasSetting($key)) {
      throw new SaferpayException($this->t('Unknown setting @key requested', array('@key' => $key)));
    }

    return $this->settings[$key];
  }

  public function hasSetting($key) {
    return array_key_exists($key, $this->settings);
  }

  public function setPayment(Payment $payment) {
    $this->payment = $payment;
  }

  public function getPayment() {
    if (empty($this->payment)) {
      throw new SaferpayException($this->t('Payment requested while there is none set'));
    }
    return $this->payment;
  }

  public function setSessionData($key, $value) {
    $_SESSION['payment_saferpay'][$key] = $value;
  }

  public function getSessionData($key) {
    if (!$this->hasSessionData($key)) {
      throw new SaferpayException($this->t('Unknown session data @key requested', array('@key' => $key)));
    }
    return $_SESSION['payment_saferpay'][$key];
  }

  public function hasSessionData($key) {
    if (!array_key_exists('payment_saferpay', $_SESSION)) {
      $_SESSION['payment_saferpay'] = array();
    }
    return array_key_exists($key, $_SESSION['payment_saferpay']);
  }

  public function getTransactionUrl() {

    $data['CARDREFID'] = 'new';

    $data['FAILLINK'] = $this->urlGenerator->generateFromRoute('payment_saferpay.business_scd_payemnt',
      array('payment' => $this->getPayment()->id()), array('absolute' => TRUE, 'key' => $this->computeToken($this->getPayment()->uuid())));

    $data['SUCCESSLINK'] = $data['FAILLINK'];
    $data['BACKLINK'] = $data['FAILLINK'];
    $data['ACCOUNTID'] = $this->getSetting('account_id');

    if ($this->hasSetting('password')) {
      $data['spPassword'] = $this->getSetting('password');
    }

    // Saferpay only supports en, de, it and fr. For everything else, fall back
    // to en.
    $language = $this->languageManager->getCurrentLanguage()->id;
    $data['LANGID'] = in_array($language, array('en', 'de', 'fr', 'it')) ? $language : 'EN';

    $response = $this->saferpayRequest($this->urlGenerator->generateFromPath('https://www.saferpay.com/hosting/CreatePayInit.asp',
      array('external' => TRUE, 'query' => $data)));

    if (strpos($response, 'ERROR') !== FALSE) {
      throw new SaferpayException($this->t('An error occurred during payment: @error.', array('@error' => $response)));
    }

    return $response;
  }

  public function pay() {
    $transaction = $this->authorizePayment();
    if ($transaction !== FALSE) {
      $this->settlePayment($transaction);
    }
  }

  public function verifyEnrollment($scd_response) {
    $data = array();

    // Generic arguments.
    $data['MSGTYPE'] = 'VerifyEnrollment';
    $data['ACCOUNTID'] = $this->getSetting('account_id');
    if ($this->hasSetting('password')) {
      $data['spPassword'] = $this->getSetting('password');
    }

    $data['MPI_PA_BACKLINK'] = $this->urlGenerator->generateFromRoute('payment_saferpay.business_mpi_payemnt',
      array('payment' => $this->getPayment()->id()), array('absolute' => TRUE, 'query' => array('key' => $this->computeToken($this->getPayment()->uuid()))));

    // Card reference.
    $data['CARDREFID'] = $scd_response['CARDREFID'];
    $data['EXP'] = $scd_response['EXPIRYMONTH'] . $scd_response['EXPIRYYEAR'];

    // Payment amount.
    $data['AMOUNT'] = round($this->getPayment()->getAmount() * 100);
    $data['CURRENCY'] = $this->getPayment()->getCurrencyCode();

    $url = $this->urlGenerator->generateFromPath('https://www.saferpay.com/hosting/VerifyEnrollment.asp',
      array('external' => TRUE, 'query' => $data));

    $return = $this->saferpayRequest($url);
    list($code, $response) = explode(':', $return, 2);

    if ($code == 'OK') {
      return simplexml_load_string($response);
    }

    throw new SaferpayException($this->t('Failed to verify the enrolment: @error', array('@error' => $response)));
  }

  /**
   * Authorizes a payment.
   *
   * @return array
   *   The transaction object if the authorization succeeded, FALSE
   *   otherwise. The error can be fetched from
   *   PAYMENT_SAFERPAY_business_error() in that case.
   *
   * @throws \Drupal\payment_saferpay\SaferpayException
   *   If error occurs during the authorization process.
   */
  protected function authorizePayment() {
    $data = array();

    $data['MSGTYPE'] = 'VerifyEnrollment';
    $data['ACCOUNTID'] = $this->getSetting('account_id');
    if ($this->hasSetting('password')) {
      $data['spPassword'] = $this->getSetting('password');
    }

    $data['CARDREFID'] = $this->getSessionData('card_ref_id');

    if ($this->hasSessionData('mpi_session_id')) {
      $data['MPI_SESSIONID'] = $this->getSessionData('mpi_session_id');
    }

    // If the CVC number is present in the session, use it and then remove it.
    if (!empty($config['cvc'])) {
      $data['CVC'] = $config['cvc'];
    }

    // Order data.
    $data['AMOUNT'] = round($this->getPayment()->getAmount() * 100);
    $data['CURRENCY'] = $this->getPayment()->getCurrencyCode();
    $data['ORDERID'] = $this->computeToken($this->getPayment()->uuid());

    $url = $this->urlGenerator->generateFromPath('https://www.saferpay.com/hosting/execute.asp',
      array('external' => TRUE, 'query' => $data));

    $return = $this->saferpayRequest($url);
    list($code, $idp_string) = explode(':', $return, 2);
    if ($code == 'OK') {
      $idp = simplexml_load_string($idp_string);

      if ((int) $idp['RESULT'] == 0) {
        return array(
          'remote_id' => (string)$idp['ID'],
          'amount' => $data['AMOUNT'],
          'currency' => $data['CURRENCY'],
          'payload' => array(REQUEST_TIME => array($idp_string)),
        );
      }
      else {
        throw new SaferpayException($this->t('Saferpay responded with authentication error: @error',
          array('@error' => $idp['AUTHMESSAGE'])));
      }
    }
    else {
      throw new SaferpayException($this->t('Saferpay responded with an error: @error',
        array('@error' => $idp_string)));
    }
  }

  /**
   * Computes token for given value.
   *
   * @param mixed $value
   *   Value for which to compute a token.
   *
   * @return string
   *   The computed token.
   */
  protected function computeToken($value) {
    return Crypt::hmacBase64($value, \Drupal::service('private_key')->get() . Settings::getHashSalt());
  }

  /**
   * Verifies 3-D secure enrollment.
   *
   * @param $transaction
   *   The transaction received from saferpay service.
   *
   * @throws \Drupal\payment_saferpay\SaferpayException
   *   If error occurred during settling the payment.
   */
  protected function settlePayment($transaction) {
    $data = array();

    $data['ACTION'] = 'Settlement';
    $data['ID'] = $transaction['remote_id'];
    $data['ACCOUNTID'] = $this->getSetting('account_id');
    if ($this->hasSetting('password')) {
      $data['spPassword'] = $this->getSetting('password');
    }

    $url = $this->urlGenerator->generateFromPath('https://www.saferpay.com/hosting/paycompletev2.asp',
      array('external' => TRUE, 'query' => $data));

    $return = $this->saferpayRequest($url);
    list($code, $response_string) = explode(':', $return, 2);

    if ($code == 'OK') {
      $response = simplexml_load_string($response_string);
      if ((int) $response['RESULT'] == 0) {
        $this->getPayment()->execute();
        // @todo - saving some more info?
//        $transaction->remote_message = (string) $response['MESSAGE'];
//        $transaction->payload[REQUEST_TIME][] = $response_string;
      }
      else {
        throw new SaferpayException($this->t('Saferpay responded with following error: @error', array('@error' => $response['MESSAGE'] . $response['AUTHMESSAGE'])));
        // @todo - saving some more info?
//        $transaction->remote_message = (string) $response['MESSAGE'];
//        $transaction->payload[REQUEST_TIME][] = $response_string;
      }
    }
    else {
      throw new SaferpayException($this->t('Saferpay responded with following error: @error', array('@error' => $response_string)));
    }
  }

  protected function saferpayRequest($url) {
    $request = $this->httpClient->createRequest('POST', $url, [
      'verify' => FALSE,
    ]);
    $response = $this->httpClient->send($request);

    return $response;
  }
}
