<?php

namespace Drupal\upss\Form;

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

    $form['annotation'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $this->t('Available types are: json, xml'),
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
      $values = $form_state->getValues();
      if (empty($values['input_file'])){
          if (empty($values['input_link'])){
              $form_state->setError($form['input_file'], $this->t('File has not been uploaded or link provided'));
          }
      }
      $i = 0;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array|null $data
   *
   * @return FormStateInterface
   */
  public function submitForm(array &$form, FormStateInterface $form_state, array $data = NULL) {
    $link = '';

    if (is_null($data)){
      if (empty($form['input_file']['#files'])){
        $link = $form_state->getValue('input_link');
      }else {
        $file = array_values( $form['input_file']['#files'])[0];
        $link = $file->getFileUri();
      }

      $data = file_get_contents($link);
    }

    if ($data){
      $upss = \Drupal::service('upss.upss');
      $response = $upss->sendData($data);
      if ($response){

        $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
        $tempstore->set('response', $response);

        $initial_preferences = array_keys($response['preferences']);
        foreach ($initial_preferences as $index => $init_preference){
          $initial_preferences[$init_preference] = $init_preference;
          unset($initial_preferences[$index]);
        }

        $tempstore->set('initial_preferences_names', $initial_preferences);
        $tempstore->set('initial_preferences', $response['preferences']);

        return $form_state->setRedirect('upss.preferences');
      }
      else{
        drupal_set_message($this->t('Input data is invalid'), 'error');
      }
    }


  }
}