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
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

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
   * The payment status manager.
   *
   * @var \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface
   */
  protected $paymentStatusManager;

  /**
   * Constructs a new class instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Utility\Token $token
   *   The token API.
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManager $payment_status_manager
   *   The payment status manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EventDispatcherInterface $event_dispatcher, Token $token, ModuleHandlerInterface $module_handler, PaymentStatusManager $payment_status_manager) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $event_dispatcher, $token, $module_handler);
    $this->paymentStatusManager = $payment_status_manager;

    $this->pluginDefinition['message_text'] = '';
    $this->pluginDefinition['message_text_format'] = '';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('event_dispatcher'),
      $container->get('token'),
      $container->get('module_handler'),
      $container->get('plugin.manager.payment.status')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function getOperations($plugin_id) {
    return array();
  }

  /**
   * Performs the actual payment execution.
   */
  protected function doExecutePayment() {
    /** @var \Drupal\payment\Entity\PaymentInterface $payment */
    $payment = $this->getPayment();

    $generator = \Drupal::urlGenerator();

    /** @var \Drupal\currency\Entity\CurrencyInterface $currency */
    $currency = Currency::load($payment->getCurrencyCode());

    // @todo: Make a correct configurable payment description.
    $payment_data = array(
      'ACCOUNTID' => $this->pluginDefinition['account_id'],
      'AMOUNT' => intval($payment->getamount() * $currency->getSubunits()),
      'CURRENCY' => $payment->getCurrencyCode(),
      'DESCRIPTION' => 'Payment Description',
      'ORDERID' => $payment->id(),
      'SUCCESSLINK' => $generator->generateFromRoute('payment_saferpay.response_success', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'FAILLINK' => $generator->generateFromRoute('payment_saferpay.response_fail', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'BACKLINK' => $generator->generateFromRoute('payment_saferpay.response_back', array('payment' => $payment->id()), array('absolute' => TRUE)),
      'NOTIFYURL' => $generator->generateFromRoute('payment_saferpay.response_notify', array('payment' => $payment->id()), array('absolute' => TRUE)),
    );

    if ($this->pluginDefinition['test_mode']) {
      $payment_data['ACCOUNTID'] = '99867-94913159';
      $payment_data['spPassword'] = 'XAjc3Kna';
    }


    $saferpay_callback = \Drupal::httpClient()->get($this->pluginDefinition['payment_link'], array('query' => $payment_data));
    $saferpay_redirect_url = (string) $saferpay_callback->getBody();

    $redirect_url = Url::fromUri($this->pluginDefinition['payment_link'], array(
      'absolute' => TRUE,
      'query' => $payment_data,
    ))->toString();

    $response = new RedirectResponse($saferpay_redirect_url);
    $listener = function (FilterResponseEvent $event) use ($response) {
      $event->setResponse($response);
      $event->stopPropagation();
    };
    $this->eventDispatcher->addListener(KernelEvents::RESPONSE, $listener, 999);

    $payment->save();

//    $payment = $this->getPayment();
//    $payment->setPaymentStatus($this->paymentStatusManager->createInstance('payment_success'));
//    $payment->save();
//    $payment->getPaymentType()->resumeContext();
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
