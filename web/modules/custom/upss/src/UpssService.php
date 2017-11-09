<?php

namespace Drupal\upss;

class UpssService {
  private const UPPS_HOST = 'https://abramomovich.000webhostapp.com/';

  public function sendData($data, string $format){

    $curl = curl_init(self::UPPS_HOST);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(
      [
        'data' => $data,
        'format' => $format,
      ]
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    return $response;
  }

  public function sendPreferences(array $preferences){
    $preferences = json_encode($preferences);
    $curl = curl_init(self::UPPS_HOST);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query(
      [
        'preferences' => $preferences,
      ]
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($curl);
    curl_close($curl);

    if ($response){
      $response = json_decode($response, TRUE);
    }

    return $response;
  }
}