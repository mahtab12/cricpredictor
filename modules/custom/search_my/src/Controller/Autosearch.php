<?php

/**
 * @file
 * Contains \Drupal\custom_search\Controller\Autosearch.
 */

namespace Drupal\insight_search\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\insight_market_access\RequestCache;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


/**
 * AutoSearchController.
 */
class Autosearch extends ControllerBase
{

  function getSearchedTaxonomies()
  {
    $content =  RequestCache::taxonomies();
    $data = [];
    foreach ($content['states'] AS $result) {
      $data[$result['id']] = ['text' => $result['name'], 'category' => 'geo', 'type' => 'state', 'tid' => $result['id']];
    }
    foreach ($content['metroareas'] AS $result) {
      foreach ($result['content'] As $metros)
        $data[$metros['id']] = ['text' => $metros['name'] . ', ' . $result['name'], 'category' => 'geo', 'type' => 'Metro Area', 'tid' => $metros['id']];
    }
    foreach ($content['payers'] AS $result) {
      $data[$result['id']] = ['text' => $result['name'], 'category' => 'payers', 'type' => 'Health Plan', 'tid' => $result['id']];
    }
    foreach ($content['providers'] AS $result) {
      $data[$result['id']] = ['text' => $result['name'], 'category' => 'providers', 'type' => 'Provider', 'tid' => $result['id']];
    }
    echo Json::encode($data);exit;
  }
}
