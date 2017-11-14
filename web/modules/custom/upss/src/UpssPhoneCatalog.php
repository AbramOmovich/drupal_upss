<?php

namespace Drupal\upss;

use Drupal\Core\Link;
use Drupal\Core\Url;

class UpssPhoneCatalog {
  public const PAGE_LIMIT = 10;
  private const FOLDER = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
        'mock_data' . DIRECTORY_SEPARATOR . 'phones' . DIRECTORY_SEPARATOR;

  private const IMG_DIR = 'public://phone_images/';
  private $private_fields = [
    'key', 'name', 'extended_name', 'status', 'images', 'image', 'image_size',
    'micro_description', 'html_url', 'reviews', 'review_url', 'second', 'forum',
    'url'
  ];

  public function getPhones(int $page = NULL, bool $clean = FALSE) : array {
    $dir = opendir(self::FOLDER);
    $phones = [];

    $start = $end = $i = 0;
    if (!is_null($page)){
      $start = self::PAGE_LIMIT * $page;
      $end = self::PAGE_LIMIT * ($page + 1);
    }else {
      $end = PHP_INT_MAX;
    }

    while ((false !== ($file = readdir($dir))) && ($i < $end) ) {
      if ($file == '.' || $file == '..'){
        continue;
      }
      if ($i >= $start && $i < $end){
        $file = file_get_contents(self::FOLDER . $file);
        $phone = unserialize($file);
        $phone['image'] = $this->getPhoneImageUrl($phone);
        unset($phone['images']);

        if ($clean){
          foreach ($this->private_fields as $field){
            unset($phone[$field]);
          }
          $prices ['min'] = $phone['prices']['price_min']['amount'] . ' ' . $phone['prices']['price_min']['currency'];
          $prices ['min'] = $phone['prices']['price_max']['amount'] . ' ' . $phone['prices']['price_max']['currency'];
          $phone['prices'] = $prices;

          $this->prepareKeys($phone);
        }

        $phones []= $phone;
      }
      $i++;
    }

    return $phones;
  }

  private function prepareKeys(array &$array){
    foreach ($array as $key => &$value){
      if (preg_match('@\s@', $key)){
        $array[str_replace(' ', '_', $key)] = $value;
        unset($array[$key]);
      }

      if (is_array($value)){
        $this->prepareKeys($value);
      }
    }
  }

  public function getNumberOfPhones() : int {
    $dir = opendir(self::FOLDER);
    $amount = 0;

    while (false !== ($file = readdir($dir)) ) {
      if ($file == '.' || $file == '..'){
        continue;
      }
      $amount++;
    }

    return $amount;
  }

  public function getPhoneById(int $id){
    $file = self::FOLDER . $id . '.ser';
    $phone = NULL;

    if (file_exists($file)){
      $phone = file_get_contents($file);
      $phone = unserialize($phone);
      $phone['image'] = $this->getPhoneImageUrl($phone);
      unset($phone['images']);
    }

    return $phone;
  }

  private function getPhoneImageUrl(array $phone): string
  {
    return self::IMG_DIR . $phone['id'] . '.' . pathinfo($phone['images']['header'], PATHINFO_EXTENSION);
  }
}