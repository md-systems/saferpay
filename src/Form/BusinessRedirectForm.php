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
use Drupal\payment_saferpay\SaferpayException;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    /** @var \Drupal\payment_saferpay\SaferPaybusiness $saferpay */
    $saferpay = \Drupal::service('payment_saferpay.business');
    $saferpay->setPayment($payment);
    $saferpay->setSettings($config);

    try {
      $url = $saferpay->getTransactionUrl();
    }
    catch (SaferpayException $e) {
      drupal_set_message($e->getMessage(), 'error');
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
}
