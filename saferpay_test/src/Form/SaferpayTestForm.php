<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Form\SaferpayTestForm.
 */

namespace Drupal\payment_saferpay_test\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Profiler\Profile;

/**
 * Builds the form to delete a forum term.
 */
class SaferpayTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'saferpay_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    foreach ($request->query->all() as $key => $value) {
      drupal_set_message($key . $value);
    }

    // Set up Test Data.
    $DATA= array(
      '@MSGTYPE' => 'PayConfirm',
      '@TOKEN' => '(unused)',
      '@VTVERIFY' => '(obsolete)',
      '@KEYID' => '1-0',
      '@ID' => 'zzUIU8br3YGdvAx6t13QAC3vt0nA',
      '@ACCOUNTID' => $request->query->get('ACCOUNTID'),
      '@PROVIDERID' => '90',
      '@PROVIDERNAME' => 'Saferpay Test Card',
      '@PAYMENTMETHOD' => '6',
      '@ORDERID' => '84',
      '@AMOUNT' => $request->query->get('AMOUNT'),
      '@CURRENCY' => $request->query->get('CURRENCY'),
      '@IP' => '83.150.36.145',
      '@IPCOUNTRY' => 'IX',
      '@CCCOUNTRY' => 'CH',
      '@MPI_LIABILTYSHIFT' => 'no',
      '@MPI_TX_CAVV' => '',
      '@MPI_XID' => '',
      '@ECI' => '0',
      '@CAVV' => '',
      '@XID' => '',
    );

    // Construct the URL for the Submit button.
    $response_url_key = \Drupal::state()->get('saferpay.return_url_key') ?: 'success';
    switch($response_url_key){
      case 'success':
        $response_url = $request->query->get('SUCCESSLINK');
        break;
      case 'fail':
        $response_url = $request->query->get('FAILLINK');
        break;
      case 'back':
        $response_url = $request->query->get('BACKLINK');
        break;
      case 'notify_url':
        $response_url = $request->query->get('NOTIFYURL');
        break;
    }

  // Generate String from formatted XML String and Array of Test Data.
    $data_string = SafeMarkup::format('<IDP MSGTYPE="@MSGTYPE" TOKEN="@TOKEN" VTVERIFY="@VTVERIFY" KEYID="@KEYID" ID="@ID" ACCOUNTID="@ACCOUNTID" PROVIDERID="@PROVIDERID" PROVIDERNAME="@PROVIDERNAME" PAYMENTMETHOD="@PAYMENTMETHOD" ORDERID="@ORDERID" AMOUNT="@AMOUNT" CURRENCY="@CURRENCY" IP="@IP" IPCOUNTRY="@IPCOUNTRY" CCCOUNTRY="@CCCOUNTRY" MPI_LIABILTYSHIFT="@MPI_LIABILTYSHIFT" MPI_TX_CAVV="@MPI_TX_CAVV" MPI_XID="@MPI_XID" ECI="@ECI" CAVV="@CAVV" XID="@XID" />',
      $DATA
    );
    $signature = \Drupal::state()->get('saferpay.signature') ?: Crypt::hashBase64($data_string);

    $response_url .= '?DATA=' . urlencode($data_string) . '&SIGNATURE=' . $signature;
    $form['#action'] = $response_url;
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message("Validate Form");
  }

}
