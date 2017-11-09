<?php

namespace Drupal\upss\Form;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class UpssFileUploadForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'upss_file_upload_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();

    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Select format for input data'),
      '#options' => [
        'xml' => $this->t('XML'),
        'json' => $this->t('JSON'),
      ],
    ];

    $form['input_file'] = [
      '#title' => $this->t('Upload file'),
      '#type' => 'managed_file',
      '#upload_location' => 'private://upss_files/',
      '#upload_validators' => [
        'file_validate_extensions' => ['xml json'],
      ],
    ];

    $form['input_link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Or provide link for it'),
    ];

    $form['send'] = [
      '#type' => 'submit',
      '#value' => 'Send'
    ];


    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state){
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $file = array_values( $form['input_file']['#files'])[0];

    $data = file_get_contents($file->getFileUri());

    if ($data){
      $upss = \Drupal::service('upss.upss');
      $response = $upss->sendData($data, $form_state->getValue('format'));
      if ($response){
        $response = json_decode($response, TRUE);

        $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
        $tempstore->set('preferences', $response);

        return $form_state->setRedirect('upss.preferences');
      }
    }


  }
}