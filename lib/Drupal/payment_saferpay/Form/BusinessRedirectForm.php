<?php
/**
 * @file
 *   Contains \Drupal\payment_saferpay\Form\BusinessRedirectForm.
 */

namespace Drupal\payment_saferpay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\monitoring\Sensor\SensorInfo;
use Drupal\monitoring\Sensor\SensorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sensor overview form controller.
 */
class BusinessRedirectForm extends FormBase {

  /**
   * Stores the sensor manager.
   *
   * @var \Drupal\monitoring\Sensor\SensorManager
   */
  protected $sensorManager;

  /**
   * Constructs a \Drupal\monitoring\Form\SensorSettingsForm object.
   *
   * @param \Drupal\monitoring\Sensor\SensorManager $sensor_manager
   *   The sensor manager service.
   */
  public function __construct(SensorManager $sensor_manager) {
    $this->sensorManager = $sensor_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_manager')
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

    if (empty($payment_method['settings']['account_id'])) {
      drupal_set_message(t('Saferpay Business has not been configured. The test account is used. Visit the <a href="!url">payment settings</a> to change this.', array('!url' => url('admin/commerce/config/payment-methods'))), 'warning');
    }

    $url = _payment_saferpay_business_initpay($payment_method['settings']);
    if (empty($url)) {
      return array();
    }

//    $form['#method'] = 'post';
//    $form['#action'] = $url;

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

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }
}
