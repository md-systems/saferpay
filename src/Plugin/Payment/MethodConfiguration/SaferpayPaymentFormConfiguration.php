<?php

/**
 * @file
 * Contains \Drupal\saferpay\Plugin\Payment\MethodConfiguration\SaferpayPaymentFormConfiguration.
 */

namespace Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\payment\Plugin\Payment\MethodConfiguration\PaymentMethodConfigurationBase;
use Drupal\payment\Plugin\Payment\Status\PaymentStatusManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the configuration for the Saferpay PaymentForm payment method plugin.
 *
 * @PaymentMethodConfiguration(
 *   description = @Translation("Saferpay Payment Form."),
 *   id = "payment_saferpay_payment_form",
 *   label = @Translation("Saferpay Payment Form")
 * )
 */
class SaferpayPaymentFormConfiguration extends PaymentMethodConfigurationBase implements ContainerFactoryPluginInterface {

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
      'payment_link' => 'https://www.saferpay.com/hosting/CreatePayInit.asp',
      'authorization_link' => 'https://www.saferpay.com/hosting/VerifyPayConfirm.asp',
      'settlement_link' => 'https://www.saferpay.com/hosting/PayCompleteV2.asp',
      'settle_option' => TRUE,
    );
  }

  /**
   * Sets the Saferpay account id.
   *
   * @param string $account_id
   *   Account id.
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayPaymentFormConfiguration
   *   The configuration object for the Saferpay Payment Form payment method plugin.
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
   * Sets the Saferpay Payment Link.
   *
   * @param string $payment_link
   *   Generation of a payment link.
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayPaymentFormConfiguration
   *   The configuration object for the Saferpay Payment Form payment method plugin.
   */
  public function setPaymentLink($payment_link) {
    $this->configuration['payment_link'] = $payment_link;

    return $this;
  }

  /**
   * Gets the Saferpay payment link.
   *
   * @return string
   *   Generation of a payment link.
   */
  public function getPaymentLink() {
    return $this->configuration['payment_link'];
  }

  /**
   * Sets the Saferpay Authorization Link.
   *
   * @param string $authorization_link
   *   Verifying an authorization response.
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayPaymentFormConfiguration
   *   The configuration object for the Saferpay Payment Form payment method plugin.
   */
  public function setAuthorizationLink($authorization_link) {
    $this->configuration['authorization_link'] = $authorization_link;

    return $this;
  }

  /**
   * Gets the Saferpay Authorization link.
   *
   * @return string
   *   Verifying an authorization response.
   */
  public function getAuthorizationLink() {
    return $this->configuration['authorization_link'];
  }

  /**
   * Sets the Saferpay Settlement Link.
   *
   * @param string $settlement_link
   *   Settlement of a payment.
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayPaymentFormConfiguration
   *   The configuration object for the Saferpay Payment Form payment method plugin.
   */
  public function setSettlementLink($settlement_link) {
    $this->configuration['settlement_link'] = $settlement_link;

    return $this;
  }

  /**
   * Gets the Saferpay Settlement link.
   *
   * @return string
   *   Settlement of a payment.
   */
  public function getSettlementLink() {
    return $this->configuration['settlement_link'];
  }

  /**
   * Sets the Saferpay Settle Option.
   *
   * @param string $settle_option
   *   Settle Option, Yes or No
   *
   * @return \Drupal\payment_saferpay\Plugin\Payment\MethodConfiguration\SaferpayPaymentFormConfiguration
   *   The configuration object for the Saferpay Payment Form payment method plugin.
   */
  public function setSettleOption($settle_option) {
    $this->configuration['settle_option'] = $settle_option;

    return $this;
  }

  /**
   * Gets the Saferpay Settlement link.
   *
   * @return string
   *   Settlement of a payment.
   */
  public function getSettleOption() {
    return $this->configuration['settle_option'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['#element_validate'][] = array($this, 'formElementsValidate');

    $form['account_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Account ID'),
      '#default_value' => $this->getAccountId(),
    );

    $form['payment_link'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Payment Link'),
      '#description' => $this->t('Generation of a payment link.'),
      '#default_value' => $this->getPaymentLink(),
    );

    $form['authorization_link'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Authorization Link'),
      '#description' => $this->t('Verifying an authorization response.'),
      '#default_value' => $this->getAuthorizationLink(),
    );

    $form['settlement_link'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Settlement Link'),
      '#description' => $this->t('Settlement of a payment'),
      '#default_value' => $this->getSettlementLink(),
    );

    $form['settle_option'] = array(
      '#type' => 'select',
      '#options' => array(TRUE => 'Yes', FALSE => 'No'),
      '#title' => $this->t('Settle Payment directly'),
      '#description' => $this->t('PAy the settle the payment directly after it is approved.'),
      '#default_value' => $this->getSettleOption(),
    );

    return $form;
  }

  /**
   * Implements form validate callback for self::formElements().
   */
  public function formElementsValidate(array $element, FormStateInterface $form_state, array $form) {
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    $this->setAccountId($values['account_id'])
      ->setPaymentLink($values['payment_link'])
      ->setAuthorizationLink($values['authorization_link'])
      ->setSettlementLink($values['settlement_link']);
  }

}
