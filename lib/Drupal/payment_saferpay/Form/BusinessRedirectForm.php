<?php
/**
 * @file
 *   Contains \Drupal\payment_saferpay\Form\BusinessRedirectForm.
 */

namespace Drupal\payment_saferpay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\payment\Payment as PaymentServiceWrapper;
use \Drupal\payment\Entity\Payment;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Sensor overview form controller.
 */
class BusinessRedirectForm extends FormBase {

  /**
   * Stores the payment manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Method\PaymentMethodManager
   */
  protected $paymentManager;

  protected $error = array();

  /**
   * @param \Drupal\payment\Plugin\Payment\Method\PaymentMethodManager $payment_manager
   *   The payment manager service.
   */
  public function __construct(PaymentMethodManager $payment_manager) {
    $this->paymentManager = $payment_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.payment.method')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'payment_saferpay_business';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {

    $config = PaymentServiceWrapper::methodConfigurationManager()->createInstance('payment_saferpay_business')->getConfiguration();
    $method = PaymentServiceWrapper::methodManager()->createInstance('payment_saferpay_business');

    if (!empty($config['password'])) {
      drupal_set_message(t('Saferpay Business has not been configured. The test account is used. Visit the <a href="!url">payment settings</a> to change this.', array('!url' => url('admin/commerce/config/payment-methods'))), 'warning');
    }

    /** @var \Drupal\payment\Entity\Payment $payment */
    $payment = entity_create('payment', array(
      'bundle' => 'payment_saferpay_business',
    ));
    $payment->setCurrencyCode('CHF');
    $payment->setPaymentMethod($method);

    $line_item = PaymentServiceWrapper::lineItemManager()->createInstance('payment_basic');
    $line_item->setName('saferpay_business');
    $line_item->setQuantity(1);
    $line_item->setAmount(1);
    $line_item->setCurrencyCode('CHF');
    $payment->setLineItem($line_item);

    $payment->save();

    $url = _payment_saferpay_business_initpay($config, $payment);
    if (empty($url)) {
      return array();
    }

    $form['#method'] = 'post';
    $form['#action'] = $url;

    // Default values.
    $default = array(
      'type' => '',
      'owner' => '',
      'number' => '',
      'start_month' => '',
      'start_year' => date('Y') - 5,
      'exp_month' => date('m'),
      'exp_year' => date('Y'),
      'issue' => '',
      'code' => '',
      'bank' => '',
    );

    // When the test account is used, add a default value for the test credit
    // card.
    if (empty($payment_method['settings']['account_id']) || $payment_method['settings']['account_id'] == '99867-94913159') {
      $default['owner'] = t('Test card');
      $default['number'] = '9451123100000111';
    }

    $form['CardHolder'] = array(
      '#type' => 'textfield',
      '#title' => t('Card owner'),
      '#default_value' => $default['owner'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 64,
      '#size' => 32,
      '#weight' => 1,
    );

    $form['sfpCardNumber'] = array(
      '#type' => 'textfield',
      '#title' => t('Card number'),
      '#default_value' => $default['number'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 19,
      '#size' => 20,
      '#weight' => 2,
    );

    $form['expiration'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Expiration'),
    );

    $form['expiration']['sfpCardExpiryMonth'] = array(
      '#type' => 'textfield',
      '#title' => t('month'),
      '#default_value' => strlen($default['exp_month']) == 1 ? '0' . $default['exp_month'] : $default['exp_month'],
      '#required' => TRUE,
    );

    $form['expiration']['sfpCardExpiryYear'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('year'),
      '#default_value' => $default['exp_year'],
    );

    $form['CVC'] = array(
      '#type' => 'textfield',
      '#title' => !empty($fields['code']) ? $fields['code'] : t('Security code'),
      '#default_value' => $default['code'],
      '#attributes' => array('autocomplete' => 'off'),
      '#required' => TRUE,
      '#maxlength' => 4,
      '#size' => 4,
      '#weight' => 5,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Proceed with payment'),
      '#weight' => 50,
    );

    return $form;
  }

  public function submitForm(array &$form, array &$form_state) {
    $method = $this->paymentManager->createInstance('payment_saferpay_business');
    $payment = entity_create('payment', array(
      'bundle' => 'payment_saferpay_business',
    ));
    $method->executePayment($payment);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }


  /**
   * Page callback for processing the Saferpay MPI response.
   *
   * @param $order
   *   The commerce order object.
   */
  public function processMPIResponse(Payment $payment) {
    $data = simplexml_load_string($_GET['DATA']);
    if ($data['RESULT'] != 0) {
      drupal_set_message(t('Payment failed: @message', array('@message' => $data['MESSAGE'])));
    }

    $config = PaymentServiceWrapper::methodConfigurationManager()->createInstance('payment_saferpay_business')->getConfiguration();
    $payment_url = \Drupal::urlGenerator()->generateFromRoute('payment.payment.view', array('payment' => $payment->id()), array('absolute' => TRUE));

    // To prevent double execution, check if we already have a payment, also make
    // sure we're not running into this twice.
    // @todo - we need somehow store the mpi session id.
//    if (!isset($order->data['commerce_saferpay_mpi_session_id']) && \Drupal::lock()->acquire('payment_saferpay_' . $payment->id())) {
    if (\Drupal::lock()->acquire('payment_saferpay_' . $payment->id())) {
      // Authorize and optionally settle the order.
//      $order->data['commerce_saferpay_mpi_session_id'] = (string)$data['MPI_SESSIONID'];
      $this->pay($payment, $config);
      \Drupal::lock()->release('payment_saferpay_' . $payment->id());
    }
    else {
      // Redirect to the current order checkout page, without error. If this
      // happened, then we already have a payment for this order and can ignore
      // the request.
      // @todo - is this correct location?
      return new RedirectResponse($payment_url);
    }

    return new RedirectResponse($payment_url);
  }

  public function processSCDResponse(Payment $payment) {
    $data = simplexml_load_string($_GET['DATA']);
    if ($data['RESULT'] != 0) {
      // @todo Add message.
      drupal_set_message(t('Credit card verification failed: @error.', array('@error' => $data['DESCRIPTION'])), 'error');
      return new RedirectResponse('payment/' . $payment->id());
    }

    $payment_url = \Drupal::urlGenerator()->generateFromRoute('payment.payment.view', array('payment' => $payment->id()), array('absolute' => TRUE));
    $config = PaymentServiceWrapper::methodConfigurationManager()->createInstance('payment_saferpay_business')->getConfiguration();
    $config['cvc'] = $_GET['CVC'];

    // Copied from from Saferpay SCD documentation.
    $card_types = array(
      21699 => 'Lastschrift',
      19265 => 'American Express',
      19269 => 'MasterCard',
      19274 => 'J.C.B.',
      19286 => 'Visa',
      99072 => 'Saferpay Testkarte'
    );

    // @todo - we need to store this info along with payment.
    $card_info = \Drupal::state()->get('payment_saferpay_card_info');

    // Store the card information in order.
    $card_info[$payment->id()]['card_ref_id'] = (string)$data['CARDREFID'];
    $card_info[$payment->id()]['card_holder'] = (string)$_GET['CardHolder'];
    $card_info[$payment->id()]['card_number'] = substr((string)$data['CARDMASK'], -4);
    $card_info[$payment->id()]['expiry_month'] = (string)$data['EXPIRYMONTH'];
    $card_info[$payment->id()]['expiry_year'] = '20' . (string)$data['EXPIRYYEAR'];
    $card_info[$payment->id()]['card_type'] = $card_types[(string)$data['CARDTYPE']];

    \Drupal::state()->set('payment_saferpay_card_info', $card_info);

    if (empty($config['use_mpi'])) {
      // Authorize and optionally settle the order immediately.
      $this->pay($payment, $config);
      return new RedirectResponse($payment_url);
    }

    $mpi_response = $this->verifyEnrollment($payment, $data, $config);
    if ($mpi_response !== FALSE) {
      // Redirect to 3-D secure, if necessary.
      if ((int)$mpi_response['RESULT'] == 0 && (int)$mpi_response['ECI'] == PAYMENT_SAFERPAY_ECI_3D_AUTHENTICATION) {
        return new RedirectResponse($mpi_response['MPI_PA_LINK']);
      }
      else {
        // Check if there is no liability shift and if such payments are allowed.
        if (((int) $mpi_response['RESULT'] != 0 || $mpi_response['ECI'] == PAYMENT_SAFERPAY_ECI_NO_LIABILITYSHIFT) && !empty($payment_method['settings']['require_liablityshift'])) {
          drupal_set_message(t('Payments from credit cards without 3-D Secure support are not accepted.'), 'error');
        }
        // Authorize and optionally settle the order immediatly.
        $config['mpi_session_id'] = (string)$data['MPI_SESSIONID'];
        $this->pay($payment, $config);
      }
    }
    else {
      drupal_set_message(t('Payment failed: @error.', array('@error' => implode(', ', $this->error))), 'error');
    }

    return new RedirectResponse($payment_url);
  }

  /**
   * Authorize and optionally settle the payment for an order object.
   *
   * This is a user interface function which will use drupal_goto() to redirect
   * the user, do not use this if there is no user interface involved.x
   *
   * @param $payment
   *   The order object. Needs to have PAYMENT_SAFERPAY_card_ref_id defined in
   *   $order->data and optionally PAYMENT_SAFERPAY_mpi_session_id.
   */
  protected function pay(Payment $payment, array $config) {
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

  /**
   * Verifies 3-D Secure enrollment.
   *
   * @param $payment
   *   The order object.
   * @param $scd_response
   *   The scd_response SimpleXML object containing the CARDREFID, EXPIRYMONTH
   *   and EXPIRYYEAR attributes.
   * @param $config
   *   The payment method settings.
   *
   * @return \SimpleXMLElement
   *   The response object if the verify enrollment call successed, FALSE
   *   otherwise. The error can be fetched from
   *   PAYMENT_SAFERPAY_business_error() in that case.
   */
  protected function verifyEnrollment(Payment $payment, $scd_response, $config) {
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

}
