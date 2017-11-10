<?php

namespace Drupal\upss\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\upss\Form\PreferenceForm;

class UpssPageController extends ControllerBase {

  public function set_preferences(){

    $output = ['#markup' => ''];

    $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
    $preferences = $tempstore->get('preferences');

    $initial_preferences = $tempstore->get('initial_preferences_names');
    if (isset($preferences['preferences'])){
      $form = \Drupal::formBuilder()->getForm(PreferenceForm::class, $preferences, $initial_preferences);
      $output ['form']= $form;
    }

    $objects = $tempstore->get('objects');
    if (isset($objects)){

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
              ['width' => '20%', 'data' => $property, 'rowspan' => count($value)], array_shift($value)
            ];

            foreach ($value as $sub_value){
              $output['list']['#items'][$object]['#rows'][] = [
                $sub_value
              ];
            }

          } else{
            $output['list']['#items'][$object]['#rows'][] = [
              [ 'width' => '20%', 'data' => $property], $value
            ];
          }
        }

      }


    }

    return $output;
  }

  public function onliner(){
    $phoneCatalog = \Drupal::service('upss.phone_catalog');

    $output = [];
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
      $item['#prefix'] = '<div>';
      $item[] = [
        '#theme' => 'imagecache_external',
        '#path' => 'https://www.drupal.org/files/druplicon.png',
        '#style_name' => 'thumbnail',
        '#alt' => 'Druplicon',
        //'#path' => 'http:' .  $phone['images']['header'],
      ];
      $item[] = [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => $phone['name'],
      ];
      $item[] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $phone['description'],
      ];

      $item['#suffix'] = '</div>';

      $output[1]['#items'] []= $item;

    }

    $output[] = [ '#type' => 'pager' ];
    return $output;
  }
}