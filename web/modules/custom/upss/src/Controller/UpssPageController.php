<?php

namespace Drupal\upss\Controller;

use Drupal\Console\Bootstrap\Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\upss\Form\PreferenceForm;
use Drupal\upss\Form\SendToUpssForm;

class UpssPageController extends ControllerBase {

  public function set_preferences(){
    $output = ['#markup' => ''];

    //get last results
    $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
    $response = $tempstore->get('response');

    //if page changed we need to resend preferences
    $page = pager_find_page();
    if (isset($response['page']) && $response['page'] != $page){
      $upss = \Drupal::service('upss.upss');
      $preferences['page'] = $page;
      $preferences['preferences'] = $response['preferences'];
      $preferences['entities_id'] = $response['entities_id'];
      $response = $upss->sendPreferences($preferences);
    }

    //build preferences form
    $initial_preferences = $tempstore->get('initial_preferences_names');
    if (isset($response['preferences'])){
      $form = \Drupal::formBuilder()->getForm(PreferenceForm::class, $response, $initial_preferences);
      $output ['form']= $form;
    }

    //draw table with objects
    if (isset($response['objects'])){
      $objects = $response['objects'];

      $output[] = [ '#type' => 'pager' ];
      pager_default_initialize($response['total'], $response['per_page']);

      $output[] = [
        '#type' => 'html_tag',
        '#tag' => 'hr',
      ];
      $output['list'] = [
        '#theme' => 'item_list',
        '#list_type' => 'ul',
        '#items' => [],
      ];

      foreach ($objects as $object => $properties){
        $output['list']['#items'][$object] = [
          '#type' => 'table',
          '#header' => [
            $this->t('Property'),
            $this->t('Value')
          ],
          '#rows' => [],
        ];

        foreach ($properties as $property => $value){
          if (is_array($value) && !empty($value)){
            $output['list']['#items'][$object]['#rows'][] = [
              [
                'width' => '20%', 'data' => $property, 'rowspan' => count($value),
              ], array_keys($value)[0] . ' | ' . array_shift($value)
            ];

            foreach ($value as $sub_property => $sub_value){
              $output['list']['#items'][$object]['#rows'][] = [
                $sub_property . ' | ' . $sub_value
              ];
            }

          } else{
            $output['list']['#items'][$object]['#rows'][] = [
              [ 'width' => '20%', 'data' => $property], $value
            ];
          }
        }
      }

      $output[] = [ '#type' => 'pager' ];

    }

    return $output;
  }

  public function onliner(){
    $phoneCatalog = \Drupal::service('upss.phone_catalog');
    $renderer = \Drupal::service('renderer');

    $output = [];
    $output['-1'] = \Drupal::formBuilder()->getForm(SendToUpssForm::class);
    $output[0] = [ '#type' => 'pager' ];
    $output[1] = [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => [],
    ];
    $page = pager_find_page();
    pager_default_initialize($phoneCatalog->getNumberOfPhones(), $phoneCatalog::PAGE_LIMIT);

    $phones = $phoneCatalog->getPhones($page);
    foreach ($phones as $phone){
      $item = [];
      $image = [
        '#theme' => 'image_style',
        '#uri' => $phone['image'],
        '#style_name' => 'thumbnail',
        '#alt' => $phone['name'],
      ];

      $item[] = [
        '#theme' => 'phone',
        '#phone' => $phone,
        '#image' => $renderer->render($image)
      ];

      $output[1]['#items'] []= $item;
    }

    $output[] = [ '#type' => 'pager' ];
    return $output;
  }
}