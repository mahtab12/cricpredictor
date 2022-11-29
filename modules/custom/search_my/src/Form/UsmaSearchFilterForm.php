<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search\Form;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Doctrine\Common\Collections\ArrayCollection;
use Drupal\insight_market_access\RequestCache;

/**
 * Description of UsmaSearchFilterForm
 *
 * @author sakreddy
 */
class UsmaSearchFilterForm extends \Drupal\Core\Form\FormBase {

  //put your code here
  public function getFormId(): string {
    return 'usma_search_filter_form';
  }

  public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state): array {
    $form['#method'] = 'get';
    $form['#attributes']['class'][] = 'search-filter-inner';
    $currentPath = \Drupal::routeMatch()->getRouteObject()->getPath();
    $form['#action'] = $currentPath;
//    $httpClient = \Drupal::service('insight_market_access.http_client');
    try {
//      $maccRequest = $httpClient->request('get', '/api/macctaxonomytags/all', []);
      $content = RequestCache::taxonomies();
      $responseFav = RequestCache::userFavourites();
//      var_dump($responseFav);die;
    } catch (ClientException $ex) {
      throw new BadRequestHttpException('Not found');
    }

    $request = \Drupal::request();
    $ownership = $request->get('ownership');

//    $form['#theme_wrappers'] = array(
//        'container' => array(
//            '#attributes' => array('class' => 'search-filter-inner'),
//    ));

    $category = $request->get('category', null);

    $link = \Drupal\Core\Link::createFromRoute('Reset all', 'insight_search.search', ['query' => $request->get('query')], ['attributes' => ['class' => ['pull-right', 'btn-clearall']]]);

    $form['title'] = [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $this->t('Filters @link', ['@link' => $link->toString()])
    ];
    $form['ownership'] = [
        '#type' => 'drg_checkboxes',
        '#title' => 'Ownership',
        '#name' => 'ownership',
        '#options' => ['owned' => ['id' => 'owned', 'name' => 'Owned'], 'not_owned' => ['id' => 'not_owned', 'name' => 'Not Owned']],
        '#default_value' => $ownership,
    ];
    $geo = $request->get('geo');
    $favCollection = new ArrayCollection($responseFav['fav'] ?? []);
    $favGeo = $favCollection->filter(function($item) {
              return $item['type'] === 'geography';
            })->map(function($it) {
              return $it['tid'];
            })->toArray();
    $geoData = $content['states'];
    foreach ($content['metroareas'] as $id => $state) {
//      $geoData[$id] = ['id' => $id, 'name' => $state['name']];
      if (isset($state['content'])) {
        foreach ($state['content'] as $metro) {
          $geoData[$metro['id']] = $metro;
        }
      }
    }
    $geoCollection = new ArrayCollection($geoData ?? []);
    $myFavGeos = $geoCollection->filter(function($it) use($favGeo) {
      return in_array($it['id'], $favGeo);
    });
    if ($category == 'favourites') {
      $geo = $myFavGeos->getKeys();
// var_dump($geo);die;
    }
    $form['geo'] = [
        '#type' => 'drg_geo_highchart',
        '#title' => 'Geography',
        '#name' => 'geo',
        '#options' => ['states' => $content['states'], 'metroareas' => $content['metroareas'], 'favorites' => $myFavGeos->toArray()],
        '#default_value' => $geo,
        '#open' => $category == 'geographies'
    ];
    $payer = $request->get('payers',[]);
    $provider = $request->get('providers',[]);
    $vals = array_merge($payer, $provider);

    $favAccounts = $favCollection->filter(function($item) {
              return $item['type'] === 'providers' || $item['type'] === 'payers';
            })->map(function($item) {
      return $item['tid'];
    });
    $myFavAccounts = $this->fromCollection(array_merge($content['payers'], $content['providers']), $favAccounts->toArray());
    if ($category == 'favourites') {
      $vals = $favAccounts->getKeys();
//      $vals = ['payers' => $favPayers, 'providers' => $favProviders];
//      var_dump($vals);die;
    }
    $form['accounts'] = [
        '#type' => 'drg_searchable_checkboxes',
        '#title' => 'Accounts',
        '#name' => 'accounts',
        '#options' => ['payers' => $content['payers'], 'providers' => $content['providers'], 'my_accounts' => $myFavAccounts],
        '#default_value' => $vals,
        '#icon_classes' => 'fa fa-briefcase fa-lg fa-fw',
        '#open' => $category == 'accounts'
    ];
    $prods = [];
    foreach ($content['products'] as $key => $item) {
      $prods[$key] = [
          'id' => $item['parent']['id'],
          'name' => $item['parent']['name'],
      ];
      if (isset($item['childrens'])) {
        $prods[$key]['childrens'] = $item['childrens'];
      }
    }
    $type = $request->get('type');
    $form['type'] = [
        '#type' => 'drg_checkboxes',
        '#title' => 'Product Type',
        '#name' => 'type',
        '#options' => $prods,
        '#default_value' => $type,
        '#icon_classes' => 'fa fa-book fa-lg fa-fw',
        '#open' => $category == 'products',
        '#empty_value' => 'All Product Types'
    ];
    $sort = $request->get('sort', 'date');
    $form['sort'] = [
        '#type' => 'hidden',
        '#value' => $sort
    ];
    $query = $request->get('query');
    $form['query'] = [
        '#type' => 'hidden',
        '#value' => $query
    ];
    return $form;
  }

  private function fromCollection($data = [], $fav = [], $onlyKeys = false) {
    $collection = new ArrayCollection($data ?? []);

    $favs = $collection->filter(function($item) use($fav) {
      return in_array($item['id'], $fav);
    });
    if ($onlyKeys) {
      return $favs->getKeys();
    }
    return $favs->toArray();
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
    //The GET request is being processed in controller.
  }

}
