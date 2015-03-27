<?php
/**
 * @file
 * Contains \Drupal\payment_saferpay\Controller\SaferpayResponseController
 */

namespace Drupal\payment_saferpay\Controller;

use Drupal\Component\Utility\Crypt;
use Drupal\contact\Entity\Message;
use Drupal\payment\Entity\Payment;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment_saferpay\SaferpayException;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\payment\Payment as PaymentServiceWrapper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Saferpay Response Controller
 *
 * @package Drupal\payment_saferpay\Controller
 */
class SaferpayResponseController {

  /**
   * Page callback for processing the Saferpay MPI response.
   *
   * @param \Drupal\payment\Entity\Payment $payment
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function processMPIResponse(Payment $payment) {
    $data = simplexml_load_string($_GET['DATA']);
    if ($data['RESULT'] != 0) {
      drupal_set_message(t('Payment failed: @message', array('@message' => $data['MESSAGE'])));
    }

    $settings = PaymentServiceWrapper::methodConfigurationManager()->createInstance('payment_saferpay_business')->getConfiguration();
    $payment_url = \Drupal::urlGenerator()->generateFromRoute('payment.payment.view', array('payment' => $payment->id()), array('absolute' => TRUE));

    /** @var \Drupal\payment_saferpay\SaferPaybusiness $saferpay */
    $saferpay = \Drupal::service('payment_saferpay.business');
    $saferpay->setPayment($payment);
    $saferpay->setSettings($settings);

    // To prevent double execution, check if we already have a payment, also make
    // sure we're not running into this twice.
    if (!$saferpay->hasSessionData('mpi_session_id') && \Drupal::lock()->acquire('payment_saferpay_' . $payment->id())) {

      $saferpay->setSessionData('mpi_session_id', (string)$data['MPI_SESSIONID']);
      try {
        $saferpay->pay();
      }
      catch (SaferpayException $e) {
        drupal_set_message($e->getMessage(), 'error');
      }

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

  /**
   * Page callback for processing the Saferpay SCD response.
   *
   * @param \Drupal\payment\Entity\Payment $payment
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function processSCDResponse(Payment $payment) {
    $scd_response = simplexml_load_string($_GET['DATA']);
    if ($scd_response['RESULT'] != 0) {
      drupal_set_message(t('Credit card verification failed: @error.', array('@error' => $scd_response['DESCRIPTION'])), 'error');
      \Drupal::logger(t('Credit card verification failed: @error'),array('@error' => $scd_response['DESCRIPTION']))->warning('SaferpayResponseController.php');
      return new RedirectResponse('payment/' . $payment->id());
    }

    $payment_url = \Drupal::urlGenerator()->generateFromRoute('payment.payment.view', array('payment' => $payment->id()), array('absolute' => TRUE));
    $settings = PaymentServiceWrapper::methodConfigurationManager()->createInstance('payment_saferpay_business')->getConfiguration();
    $settings['cvc'] = $_GET['CVC'];

    // Copied from from Saferpay SCD documentation.
    $card_types = array(
      21699 => 'Lastschrift',
      19265 => 'American Express',
      19269 => 'MasterCard',
      19274 => 'J.C.B.',
      19286 => 'Visa',
      99072 => 'Saferpay Testkarte'
    );

    /** @var \Drupal\payment_saferpay\SaferPaybusiness $saferpay */
    $saferpay = \Drupal::service('payment_saferpay.business');
    $saferpay->setPayment($payment);
    $saferpay->setSettings($settings);

    // Store the card information in order.
//    $card_info[$payment->id()]['card_ref_id'] = (string)$scd_response['CARDREFID'];
//    $card_info[$payment->id()]['card_holder'] = (string)$_GET['CardHolder'];
//    $card_info[$payment->id()]['card_number'] = substr((string)$data['CARDMASK'], -4);
//    $card_info[$payment->id()]['expiry_month'] = (string)$data['EXPIRYMONTH'];
//    $card_info[$payment->id()]['expiry_year'] = '20' . (string)$data['EXPIRYYEAR'];
//    $card_info[$payment->id()]['card_type'] = $card_types[(string)$data['CARDTYPE']];

    $saferpay->setSessionData('card_ref_id', (string)$scd_response['CARDREFID']);
    $saferpay->setSessionData('expiry_month', (string)$scd_response['EXPIRYMONTH']);
    $saferpay->setSessionData('expiry_year', (string)$scd_response['EXPIRYYEAR']);

    if (empty($config['use_mpi'])) {
      // Authorize and optionally settle the order immediately.
      $saferpay->pay();
      return new RedirectResponse($payment_url);
    }

    try {
      $mpi_response = $saferpay->verifyEnrollment($scd_response);

      // Redirect to 3-D secure, if necessary.
      if ((int)$mpi_response['RESULT'] == 0 && (int)$mpi_response['ECI'] == PAYMENT_SAFERPAY_ECI_3D_AUTHENTICATION) {
        return new RedirectResponse($mpi_response['MPI_PA_LINK']);
      }
      else {
        // Check if there is no liability shift and if such payments are allowed.
        if (((int) $mpi_response['RESULT'] != 0 || $mpi_response['ECI'] == PAYMENT_SAFERPAY_ECI_NO_LIABILITYSHIFT) && !empty($payment_method['settings']['require_liablityshift'])) {
          drupal_set_message(t('Payments from credit cards without 3-D Secure support are not accepted.'), 'error');
        }
        // Authorize and optionally settle the order immediately.
        $saferpay->setSessionData('mpi_session_id', (string)$scd_response['MPI_SESSIONID']);
        $saferpay->pay();
      }
    }
    catch (SaferpayException $e) {
      drupal_set_message($e->getMessage(), 'error');
    }

    return new RedirectResponse($payment_url);
  }

  /**
   * URL to which the customer is to be forwarded to via browser redirect
   * after the successful reservation. Saferpay appends the
   * confirmation message (PayConfirm) by GET to this URL.
   *
   * @param Request $request
   *   Request
   * @param PaymentInterface $payment
   *   The Payment Entity type.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function processSuccessResponse(Request $request, PaymentInterface $payment) {
    $plugin_definition = $payment->getPaymentMethod()->getPluginDefinition();
    $signature = Crypt::hashBase64($request->get('DATA'));
    $pay_confirm_data = array('DATA' => $request->get('DATA'), 'SIGNATURE' => $signature, 'ACCOUNTID' => $plugin_definition['account_id']);

     // Save the successful payment.
    $this->savePayment($payment, 'payment_success');
    $payment_config = \Drupal::configFactory()->getEditable('payment_saferpay.settings');

    // Verify with the payment provider that the payment is legitimate.
    $verify_url = $payment_config->get('payment_link') . $payment_config->get('verify_pay_confirm');
    $verify_pay_confirm = \Drupal::httpClient()->get($verify_url, array('query' => $pay_confirm_data));
    $verify_pay_confirm_callback = (string) $verify_pay_confirm->getBody();
    debug($verify_pay_confirm_callback);
    // If the verification failed, return with an error.
    if (!(substr($verify_pay_confirm_callback, 0, 2) == 'OK')) {
      \Drupal::logger(t('Payment verification failed: @error'),array('@error' => $verify_pay_confirm_callback))->warning('SaferpayResponseController.php');
      drupal_set_message(t('Payment verification failed: @error.', array('@error' => $verify_pay_confirm_callback)), 'error');
      return $this->savePayment($payment, 'payment_failed');
    }

    // Otherwise Settle and return.
    if ($plugin_definition['settle_option']) {

      // Test Configuration see PaymentPage setup SaferPay.
      $settle_payment = \Drupal::httpClient()->get($payment_config->get('payment_link') . \Drupal::urlGenerator()->generateFromRoute('saferpay_test.pay_complete'));
      $settle_payment_callback = (string) $settle_payment->getBody();

      if (!($settle_payment_callback == 'OK')) {
        \Drupal::logger(t('Payment settlement failed: @error'),array('@error' => $settle_payment_callback))->warning('SaferpayResponseController.php');
        drupal_set_message(t('Payment settlement failed: @error.', array('@error' => $settle_payment_callback)), 'error');

      }
    }

    return $this->savePayment($payment, 'payment_success');
  }

  /**
   * URL to which the customer is to be forwarded to via browser redirect if the authorization attempt failed.
   *
   * @param Request $request
   *   Request
   * @param PaymentInterface $payment
   *   The Payment Entity type.
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function processFailResponse(Request $request, PaymentInterface $payment) {
    drupal_set_message('Payment failed');
    \Drupal::logger('Payment settlement failed')->warning('SaferpayResponseController.php');
    return $this->savePayment($payment, 'payment_failed');
  }

  /**
   * URL to which the customer is to be forwarded to via browser redirect if he aborts the transaction.
   *
   * @param Request $request
   *   Request
   * @param PaymentInterface $payment
   *   The Payment Entity type.
   */
  public function processBackResponse(Request $request, PaymentInterface $payment) {
    $this->savePayment($payment, 'payment_cancelled');
    drupal_set_message('Payment cancelled');
    \Drupal::logger('Payment cancelled')->alert('SaferpayResponseController.php');
  }

  /**
   * Fully qualified URL which in case of successful authorization is called
   * directly by the saferpay server transmitting the confirmation message (PayConfirm)
   * by POST. Only standard ports (http port 80, https port 443) are allowed.
   *
   * Other ports will not work. We recommend to implement NOTIFYURL in order to ensure the reception of
   * the confirmation message independently from possible errors or problems on customer or browser side.
   *
   * To facilitate the correlation between request and response it has proven to be useful
   * to add a shop session ID as GET parameter to the NOTIFYURL.
   *
   * @param Request $request
   *   Request
   * @param PaymentInterface $payment
   *   The Payment Entity type.
   */
  public function processNotifyResponse(Request $request, PaymentInterface $payment) {
    $this->savePayment($payment, 'payment_config');

    // @todo: Logger & drupal_set_message payment config.
  }

  /**
   * Saves success/cancelled/failed payment.
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   *  Payment Interface
   * @param string $status
   *  Payment status to set
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function savePayment(PaymentInterface $payment, $status = 'payment_failed') {
    $payment->setPaymentStatus(\Drupal::service('plugin.manager.payment.status')
      ->createInstance($status));
    $payment->save();
    return new RedirectResponse($payment->getPaymentType()->getResumeContextResponse()->getRedirectUrl()->toString());
  }

}
