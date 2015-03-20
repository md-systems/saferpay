<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\Method\SaferpayPaymentFormMethod.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\Method;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drupal\currency\Entity\Currency;
use Drupal\payment\PaymentExecutionResult;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManager;
use Drupal\payment\Response\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Saferpay Payment Form payment method.
 *
 * @PaymentMethod(
 *   id = "payment_saferpay_payment_form",
 *   label = @Translation("Saferpay Payment Form"),
 *   deriver = "\Drupal\payment_saferpay\Plugin\Payment\Method\SaferpayPaymentFormDeriver"
 * )
 */
class SaferpayPaymentFormMethod extends PaymentMethodBase implements ContainerFactoryPluginInterface, ConfigurablePluginInterface {

  /**
   * @var \Drupal\payment\Response\Response
   */
  protected $response;

  /**
   * Performs the actual payment execution.
   *
   */
  protected function doExecutePayment() {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this->getPayment();

    $generator = \Drupal::urlGenerator();

    $payment_config = \Drupal::config('payment_saferpay.settings');

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = Currency::load($payment->getCurrencyCode());

    // @todo: Make a correct configurable payment description.
    $payment_data = array(
      'ACCOUNTID' => $this->pluginDefinition['account_id'],
      'AMOUNT' => intval($payment->getAmount() * $currency->getSubunits()),
      'CURRENCY' => $payment->getCurrencyCode(),
      'DESCRIPTION' => 'Payment Description',
      'ORDERID' => $payment->id(),
      'SUCCESSLINK' => $generator->generateFromRoute('payment_saferpay.response_success', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'FAILLINK' => $generator->generateFromRoute('payment_saferpay.response_fail', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'BACKLINK' => $generator->generateFromRoute('payment_saferpay.response_back', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'NOTIFYURL' => $generator->generateFromRoute('payment_saferpay.response_notify', array('payment' => $payment->id()), array('absolute' => TRUE)),
    );

    $payment->save();

    $payment_link = $payment_config->get('payment_link') . $payment_config->get('create_pay_init');
    $saferpay_callback = \Drupal::httpClient()->get($payment_link, array('query' => $payment_data));
    $saferpay_redirect_url = (string) $saferpay_callback->getBody();

    $this->response = new Response(Url::fromUri($payment_link, array(
      'absolute' => TRUE,
      'query' => $payment_data,
    )));
  }

  public function getPaymentExecutionResult(){
  return new PaymentExecutionResult($this->response);
}

  /**
   * {@inheritdoc}
   */
  protected function getSupportedCurrencies() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function doCapturePaymentAccess(AccountInterface $account) {
    // TODO: Implement doCapturePaymentAccess() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doCapturePayment() {
    // TODO: Implement doCapturePayment() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doRefundPaymentAccess(AccountInterface $account) {
    // TODO: Implement doRefundPaymentAccess() method.
  }

  /**
   * {@inheritdoc}
   */
  protected function doRefundPayment() {
    // TODO: Implement doRefundPayment() method.
  }

}
