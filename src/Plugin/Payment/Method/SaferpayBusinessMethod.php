<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\Method\SaferpayBusinessConfiguration.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\Method;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Utility\Token;
use Drupal\payment\Entity\PaymentInterface;
use Drupal\payment\Plugin\Payment\Method\PaymentMethodBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Saferpay Business payment method.
 *
 * @PaymentMethod(
 *   id = "payment_saferpay_business",
 *   label = @Translation("Saferpay Business")
 * )
 */
class SaferpayBusinessMethod extends PaymentMethodBase implements ContainerFactoryPluginInterface {

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
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, ModuleHandlerInterface $module_handler, EventDispatcherInterface $event_dispatcher, Token $token, PaymentStatusManager $payment_status_manager) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $module_handler, $event_dispatcher, $token);
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
      $container->get('module_handler'),
      $container->get('event_dispatcher'),
      $container->get('token'),
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
   *
   * @param \Drupal\payment\Entity\PaymentInterface $payment
   */
  protected function doExecutePayment(PaymentInterface $payment) {
    $payment->setStatus($this->paymentStatusManager->createInstance('finished'));
    $payment->save();
    $payment->getPaymentType()->resumeContext();
  }

  /**
   * Returns the supported currencies.
   *
   * @return array|true
   *   Keys are ISO 4217 currency codes. Values are arrays with two keys:
   *   - minimum (optional): The minimum amount in this currency that is
   *     supported.
   *   - maximum (optional): The maximum amount in this currency that is
   *     supported.
   *   Return TRUE to allow all currencies and amounts.
   */
  protected function getSupportedCurrencies() {
    return TRUE;
  }
}
