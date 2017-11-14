<?php

namespace Drupal\upss;

class UpssPhoneCatalog {
  public const PAGE_LIMIT = 10;
  private const FOLDER = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
        'mock_data' . DIRECTORY_SEPARATOR . 'phones' . DIRECTORY_SEPARATOR;

  private const IMG_DIR = 'public://phone_images/';

  public function getPhones(int $page = NULL) : array {
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
        $phones []= $phone;
      }
      $i++;
    }

    return $phones;
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
    }

    return $phone;
  }

  private function getPhoneImageUrl(array $phone): string
  {
    return self::IMG_DIR . $phone['id'] . '.' . pathinfo($phone['images']['header'], PATHINFO_EXTENSION);
  }
}