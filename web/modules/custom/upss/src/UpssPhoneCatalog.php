<?php

namespace Drupal\upss;

class UpssPhoneCatalog {
  public const PAGE_LIMIT = 10;
  private const FOLDER = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR .
        'mock_data' . DIRECTORY_SEPARATOR . 'phones' . DIRECTORY_SEPARATOR;

  function getPhones(int $page = NULL) : array {
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
        $phones []= $phone;
      }
      $i++;
    }

    return $phones;
  }

  function getNumberOfPhones() : int {
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
}