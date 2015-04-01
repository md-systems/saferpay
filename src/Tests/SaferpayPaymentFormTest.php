<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay\Tests\SaferpayPaymentFormTest.
 */

namespace Drupal\payment_saferpay\Tests;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\currency\Entity\Currency;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\NodeTypeInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Token integration.
 *
 * @group payment_saferpay
 */
class SaferpayPaymentFormTest extends WebTestBase {

  public static $modules = array(
    'payment_saferpay',
    'payment',
    'payment_form',
    'payment_saferpay_test',
    // @todo: Check if you need these
    'node',
    'field_ui',
    'config'
  );

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var object
   */
  protected $admin_user;


  /**
   * Generic node used for testing.
   */
  protected $node;

  /**
   * @var $fieldName
   */
  protected $fieldName;


  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a field name
    $this->fieldName = strtolower($this->randomMachineName());

    // Create article content type
    $node_type = $this->drupalCreateContentType(array(
      'type' => 'article',
      'name' => 'Article'
    ));

    $config_importer = \Drupal::service('currency.config_importer');
    $config_importer->importCurrency('CHF');

    $this->addPaymentFormField($node_type);

    // Create article node
    $title = $this->randomMachineName();

    // Create node with payment plugin configuration
    $this->node = $this->drupalCreateNode(array(
      'type' => 'article',
      $this->fieldName => array(
        'plugin_configuration' => array(
          'amount' => '123',
          'currency_code' => 'CHF',
          'name' => 'payment_basic',
          'payment_id' => NULL,
          'quantity' => '2',
          'description' => 'Payment description',
        ),
        'plugin_id' => 'payment_basic',
      ),
      'title' => $title,
    ));

    // Create user with correct permission.
    $this->admin_user = $this->drupalCreateUser(array(
      'payment.payment_method_configuration.view.any',
      'payment.payment_method_configuration.update.any',
      'access content',
      'access administration pages',
      'access user profiles',
      'payment.payment.view.any'
    ));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Tests succesfull Saferpay payment.
   */
  function testSaferpaySuccessPayment() {

    // Modifies the saferpay configuration for testing purposes.
    $payment_config = \Drupal::configFactory()->getEditable('payment_saferpay.settings')->set('payment_link', $GLOBALS['base_root']);
    $payment_config->save();

    // Retrieve plugin configuration of created node.

    $saferpay_configuration = array(
      'plugin_form[account_id]' => '99867-94913159',
      'plugin_form[message][value]' => 'Saferpay',
      'plugin_form[spPassword]' => 'XAjc3Kna',
    );
    $this->drupalPostForm('admin/config/services/payment/method/configuration/payment_saferpay_payment_form', $saferpay_configuration, t('Save'));

    // Create saferpay payment.
    $this->drupalPostForm('node/' . $this->node->id(), NULL, t('Pay'));

    // Retrieve plugin configuration of created node
    $plugin_configuration = $this->node->{$this->fieldName}->plugin_configuration;

    // Array of Saferpay payment method configuration.
    $saferpay_payment_method_configuration = entity_load('payment_method_configuration', 'payment_saferpay_payment_form')->getPluginConfiguration();

    $calculated_amount = $this->calculateAmount($plugin_configuration['amount'], $plugin_configuration['quantity'], $plugin_configuration['currency_code']);
    $this->assertText('AMOUNT' . $calculated_amount);

    // Assert AccountID.
    $this->assertEqual($saferpay_payment_method_configuration['account_id'], '99867-94913159');
    $this->assertEqual($saferpay_payment_method_configuration['spPassword'], 'XAjc3Kna');

    $this->assertEqual($payment_config->get('payment_link'), $GLOBALS['base_root']);
    $this->assertEqual($saferpay_payment_method_configuration['settle_option'], 1);

    $this->drupalPostForm(NULL, array(), t('Submit'));

    //Check out the payment overview page.
    $this->drupalGet('admin/content/payment');

    // Check for correct currency code and payment amount.
    $this->assertText('CHF 246.00');

    // Check for correct Payment Method.
    $this->assertText('Saferpay');

    $payment = entity_load('payment', 1);
    $payment_method = $payment->getPaymentMethod();
    if (!$payment_method) {
      throw new \Exception('No payment method');
    }

    $this->assertNoText('Failed');

    // Check for detailed payment information.
    $this->drupalGet('payment/1');
    $this->assertNoText('Failed');
    $this->assertText('Payment description');
    $this->assertText('CHF 123.00');
    $this->assertText('CHF 246.00');
    $this->assertText('Completed');
  }
  /**
   * Tests failed Saferpay payment.
   */
  function testSaferpayFailedPayment() {
    // Modifies the saferpay configuration for testing purposes.
    $payment_config = \Drupal::configFactory()->getEditable('payment_saferpay.settings')->set('payment_link', $GLOBALS['base_root']);
    $payment_config->save();

    // Create saferpay payment
    \Drupal::state()->set('saferpay.return_url_key', 'fail');
    $this->drupalPostForm('node/' . $this->node->id(), array(), t('Pay'));

    // Finish and save payment
    $this->drupalPostForm(NULL, array(), t('Submit'));

    // Check out the payment overview page
    $this->drupalGet('admin/content/payment');
    $this->assertText('Failed');
    $this->assertNoText('Success');

    // Check for detailed payment information
    $this->drupalGet('payment/1');
    $this->assertText('Failed');
    $this->assertNoText('Success');
  }
  /**
   * Tests succesfull Saferpay payment with wrong signature.
   */
  function testSaferpayWrongSignature() {
    // Modifies the saferpay configuration for testing purposes.
    $payment_config = \Drupal::configFactory()->getEditable('payment_saferpay.settings')->set('payment_link', $GLOBALS['base_root']);
    \Drupal::state()->set('saferpay.signature', 'AAA');
    $payment_config->save();

    // Create saferpay payment
    $this->drupalPostForm('node/' . $this->node->id(), array(), t('Pay'));

    // Finish and save payment
    $this->drupalPostForm(NULL, array(), t('Submit'));

    // Check out the payment overview page
    $this->drupalGet('admin/content/payment');
    $this->assertText('Failed');
    $this->assertNoText('Success');

    // Check for detailed payment information
    $this->drupalGet('payment/1');
    $this->assertText('Failed');
    $this->assertNoText('Success');
  }

  /**
   * Calculates the total amount
   *
   * @param $amount
   *  Base amount
   * @param $quantity
   *  Quantity
   * @param $currency_code
   *  Currency code
   * @return int
   *  Returns the total amount
   */
  function calculateAmount($amount, $quantity, $currency_code) {
    $base_amount = $amount * $quantity;
    $currency = Currency::load($currency_code);
    return intval($base_amount * $currency->getSubunits());
  }

  /**
   * Adds the payment field to the node
   *
   * @param NodeTypeInterface $type
   *   Node type interface type
   *
   * @param string $label
   *   Field label
   *
   * @return \Drupal\Core\Entity\EntityInterface|static
   */
  function addPaymentFormField(NodeTypeInterface $type, $label = 'Payment Label') {
    $field_storage = entity_create('field_storage_config', array(
      'field_name' => $this->fieldName,
      'entity_type' => 'node',
      'type' => 'payment_form',
    ));
    $field_storage->save();

    $instance = entity_create('field_config', array(
      'field_storage' => $field_storage,
      'bundle' => $type->id(),
      'label' => $label,
      'settings' => array('currency_code' => 'CHF'),
    ));
    $instance->save();

    // Assign display settings for the 'default' and 'teaser' view modes.
    entity_get_display('node', $type->id(), 'default')
      ->setComponent($this->fieldName, array(
        'label' => 'hidden',
        'type' => 'text_default',
      ))
      ->save();

    return $instance;
  }
}

