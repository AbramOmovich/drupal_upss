<?php

namespace Drupal\upss\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\upss\Form\PreferenceForm;
use Symfony\Component\HttpFoundation\Request;

class UpssPageController extends ControllerBase {

  public function set_preferences(){

    $form = array('#markup' => '');

    $tempstore = \Drupal::service('user.private_tempstore')->get('upss_storage');
    $params = $tempstore->get('preferences');

    if (isset($params['preferences'])){
      $form = \Drupal::formBuilder()->getForm(PreferenceForm::class, $params);
    }

    return $form;
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