<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayBusinessConfiguration.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration for the Saferpay Business payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("Saferpay Business."),
 *   id = "payment_saferpay_business",
 *   label = @Translation("Saferpay Business")
 * )
 */
class SaferpayBusinessConfiguration extends PaymentMethodConfigurationBase implements ContainerFactoryPluginInterface {

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
   * @param \Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface $payment_status_manager
   *   The payment status manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   A string containing the English string to translate.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Interface for classes that manage a set of enabled modules.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PaymentStatusManagerInterface $payment_status_manager, TranslationInterface $string_translation, ModuleHandlerInterface $module_handler) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $string_translation, $module_handler);
    $this->paymentStatusManager = $payment_status_manager;

  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.payment.status'),
      $container->get('string_translation'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return parent::defaultConfiguration() + array(
      'account_id' => '99867-94913159',
      'password' => 'XAjc3Kna',
      'status' => 'payment_success',
    );
  }

  /**
   * Sets the final payment status.
   *
   * @param string $status
   *   The plugin ID of the payment status to set.
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayBusinessConfiguration
   *   The configuration object for the Saferpay Business payment method plugin.
   */
  public function setStatus($status) {
    $this->configuration['status'] = $status;

    return $this;
  }

  /**
   * Gets the final payment status.
   *
   * @return string
   *   The plugin ID of the payment status to set.
   */
  public function getStatus() {
    return $this->configuration['status'];
  }

  /**
   * Sets the Saferpay account id.
   *
   * @param string $account_id
   *   Account id.
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayBusinessConfiguration
   *   The configuration object for the Saferpay Business payment method plugin.
   */
  public function setAccountId($account_id) {
    $this->configuration['account_id'] = $account_id;

    return $this;
  }

  /**
   * Gets the Saferpay account id.
   *
   * @return string
   *   The account id.
   */
  public function getAccountId() {
    return $this->configuration['account_id'];
  }

  /**
   * Sets the Saferpay password.
   *
   * @param string $password
   *   The password.
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayBusinessConfiguration
   *   The configuration object for the Saferpay Business payment method plugin.
   */
  public function setPassword($password) {
    $this->configuration['account_id'] = $password;

    return $this;
  }

  /**
   * Gets the Saferpay password.
   *
   * @return string
   *   The password.
   */
  public function getPassword() {
    return $this->configuration['password'];
  }

  /**
   * {@inheritdoc}
   */
  public function formElements(array $form, array &$form_state) {
    $elements = parent::formElements($form, $form_state);
    $elements['#element_validate'][] = array($this, 'formElementsValidate');

    $elements['account_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Account ID'),
      '#default_value' => $this->getAccountId(),
    );
    $elements['password'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('spPassword'),
      '#description' => $this->t('Only required for the test account.'),
      '#default_value' => $this->getPassword(),
    );
    $elements['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Final payment status'),
      '#description' => $this->t('The status to set payments to after being processed by this payment method.'),
      '#default_value' => $this->getStatus(),
      '#options' => $this->paymentStatusManager->options(),
    );

    return $elements;
  }

  /**
   * Implements form validate callback for self::formElements().
   */
  public function formElementsValidate(array $element, array &$form_state, array $form) {
    $values = NestedArray::getValue($form_state['values'], $element['#parents']);
    $this->setStatus($values['status'])
      ->setAccountId($values['account_id'])
      ->setPassword($values['password']);
  }

}
