<?php

namespace Drupal\payment_saferpay;

class SaferPaybusiness {

  function __construct() {

  }

  public function init() {

  }

  public function pay() {
    $transaction = $this->authorizePayment($payment, $config);
    if ($transaction !== FALSE) {
      $complete_response = $this->settlePayment($payment, $transaction, $config);
      if ($complete_response !== TRUE) {
        // Display error and redirect back.
        drupal_set_message(t('An error occured while settling the payment: @error.', array('@error' => implode(', ', $this->error))), 'error');
      }
    }
    else {
      drupal_set_message(t('An error occured while processing the payment: @error.', array('@error' => implode(', ', $this->error))), 'error');
    }
  }

  public function verifyEnrollment() {
    $data = array();

    // Generic arguments.
    $data['MSGTYPE'] = 'VerifyEnrollment';
    $data['ACCOUNTID'] = $config['account_id'];
    if (!empty($config['password'])) {
      $data['spPassword'] = $config['password'];
    }
    // @todo - here we need some key based on which the enrollment will be verified.
    $data['MPI_PA_BACKLINK'] = url('saferpay/business/mpi/' . $payment->id(), array('absolute' => TRUE));

    // Card reference.
    $data['CARDREFID'] = $scd_response['CARDREFID'];
    $data['EXP'] = $scd_response['EXPIRYMONTH'] . $scd_response['EXPIRYYEAR'];

    // Payment amount.
    $data['AMOUNT'] = round($payment->getAmount() * 100);
    $data['CURRENCY'] = $payment->getCurrencyCode();

    $url = url('https://www.saferpay.com/hosting/VerifyEnrollment.asp', array('external' => TRUE, 'query' => $data));

    $return = payment_saferpay_process_url($url);
    list($code, $response) = explode(':', $return, 2);
    if ($code == 'OK') {
      return simplexml_load_string($response);
    }
    else {
      $this->error[] = $response;
    }
    return FALSE;
  }

  /**
   * Authorizes a payment.
   *
   * @param $order
   *   The order object. Needs to have PAYMENT_SAFERPAY_card_ref_id defined in
   *   $order->data and optionally PAYMENT_SAFERPAY_mpi_session_id.
   * @param $settings
   *   The payment method settings.
   * @param $method_id
   *   The payment method id.
   *
   * @return
   *   The transaction object if the authorization succeeded, FALSE
   *   otherwise. The error can be fetched from
   *   PAYMENT_SAFERPAY_business_error() in that case.
   */
  protected function authorizePayment(Payment $payment, $config) {
    $data = array();

    // Generic arguments.
    $data['MSGTYPE'] = 'VerifyEnrollment';
    $data['ACCOUNTID'] = $config['account_id'];
    if (!empty($config['password'])) {
      $data['spPassword'] = $config['password'];
    }

    $card_info = \Drupal::state()->get('payment_saferpay_card_info');
    $data['CARDREFID'] = $card_info[$payment->id()]['card_ref_id'];
    // Set the MPI_SESSIONID if existing.

    if (!empty($config['mpi_session_id'])) {
      $data['MPI_SESSIONID'] = $config['mpi_session_id'];
    }

    // If the CVC number is present in the session, use it and then remove it.
    if (!empty($config['cvc'])) {
      $data['CVC'] = $config['cvc'];
    }

    // Order data.
    $data['AMOUNT'] = round($payment->getAmount() * 100);
    $data['CURRENCY'] = $payment->getCurrencyCode();

    // @todo - what should be the identifier?
    $payment_identifier = $payment->uuid();
    $data['ORDERID'] = htmlentities($payment_identifier, ENT_QUOTES, "UTF-8");

    $url = url('https://www.saferpay.com/hosting/execute.asp', array('external' => TRUE, 'query' => $data));

    $return = payment_saferpay_process_url($url);
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
        $this->error[] = $idp['AUTHMESSAGE'];
      }
    }
    else {
      $this->error[] = $idp_string;
    }
    return FALSE;
  }

  /**
   * Verifies 3-D secure enrollment.
   *
   * @param $transaction
   *   Commerce payment transaction object to be settled.
   * @param $config
   *   The payment method settings.
   *
   * @return \SimpleXMLElement
   *   TRUE if the settlement succeeded, FALSE otherwise. The error can be fetched
   *   from PAYMENT_SAFERPAY_business_error() in that case.
   */
  protected function settlePayment(Payment $payment, $transaction, $config) {
    $data = array();

    // Generic arguments.
    $data['ACCOUNTID'] = $config['account_id'];
    if (!empty($config['password'])) {
      $data['spPassword'] = $config['password'];
    }
    $data['ACTION'] = 'Settlement';

    $data['ID'] = $transaction['remote_id'];

    $url = url('https://www.saferpay.com/hosting/paycompletev2.asp', array('external' => TRUE, 'query' => $data));

    $return = payment_saferpay_process_url($url);
    list($code, $response_string) = explode(':', $return, 2);
    if ($code == 'OK') {
      $response = simplexml_load_string($response_string);
      if ((int) $response['RESULT'] == 0) {
        $payment->execute();
        // @todo - saving some more info?
//        $transaction->remote_message = (string) $response['MESSAGE'];
//        $transaction->payload[REQUEST_TIME][] = $response_string;
        return TRUE;
      }
      else {
        $this->error[] = $response['MESSAGE'] . $response['AUTHMESSAGE'];
        $payment->setStatus(PaymentServiceWrapper::statusManager()->createInstance('payment_failure'));
        $payment->save();
        // @todo - saving some more info?
//        $transaction->remote_message = (string) $response['MESSAGE'];
//        $transaction->payload[REQUEST_TIME][] = $response_string;
      }
    }
    else {
      $this->error[] = $response_string;
    }
    return FALSE;
  }
}
