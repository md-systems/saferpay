<?php

/**
 * @file
 * Contains \Drupal\payment_saferpay_test\Form\SaferpayTestForm.
 */

namespace Drupal\payment_saferpay_test\Form;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\payment\Entity\Payment;
use Symfony\Component\HttpFoundation\Request;

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

    $form_elements = array(
      'success_link' => $request->query->get('SUCCESSLINK'),
      'fail_link' => $request->query->get('FAILLINK'),
      'back_link' => $request->query->get('BACKLINK'),
      'notify_url' => $request->query->get('NOTIFYURL'),
      'status' => 'success',
    );

    $DATA= array(
      '@MSGTYPE' => '',
      '@TOKEN' => '',
      '@VTVERIFY' => '',
      '@KEYID' => '',
      '@ID' => '',
      '@ACCOUNTID' => $request->query->get('ACCOUNTID'),
      '@PROVIDERID' => '',
      '@PROVIDERNAME' => '',
      '@PAYMENTMETHOD' => '',
      '@ORDERID' => '',
      '@AMOUNT' => $request->query->get('AMOUNT'),
      '@CURRENCY' => $request->query->get('CURRENCY'),
      '@IP' => '',
      '@IPCOUNTRY' => '',
      '@CCCOUNTRY' => '',
      '@MPI_LIABILTYSHIFT' => '',
      '@MPI_TX_CAVV' => '',
      '@MPI_XID' => '',
      '@ECI' => '',
      '@CAVV' => '',
      '@XID' => '',
    );

    foreach ($form_elements as $key => $value) {
      $form[$key] = array(
        '#type' => 'hidden',
        '#value' => $value,
      );
    }

    // Don't generate the route, use the submitted url.
    $response_url_key = \Drupal::state()->get('saferpay.return_url_key') ?: 'success';

    $response_url = $form_elements[$response_url_key . '_link'];

    $data_string = SafeMarkup::format('<IDP MSGTYPE="@MSGTYPE" TOKEN="@TOKEN" VTVERIFY="@VTVERIFY" KEYID="@KEYID" ID="@ID" ACCOUNTID="@ACCOUNTID" PROVIDERID="@PROVIDERID" PROVIDERNAME="@PROVIDERNAME" PAYMENTMETHOD="@PAYMENTMETHOD" ORDERID="@ORDERID" AMOUNT="@AMOUNT" CURRENCY="@CURRENCY" IP="@IP" IPCOUNTRY="@IPCOUNTRY" CCCOUNTRY="@CCCOUNTRY" MPI_LIABILTYSHIFT="@MPI_LIABILTYSHIFT" MPI_TX_CAVV="@MPI_TX_CAVV" MPI_XID="@MPI_XID" ECI="@ECI" CAVV="@CAVV" XID="@XID" >',
      $DATA
    );

    $response_url .= '?DATA=' . urlencode($data_string);

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
