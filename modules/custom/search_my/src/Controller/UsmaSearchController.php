<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\insight_market_access\RequestCache;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Description of UsmaSearchController
 *
 * @author sakreddy
 */
class UsmaSearchController extends ControllerBase {

  private $pageLimit = 10;


  public function render() {
    if (\Drupal::request()->isXmlHttpRequest()) {
      return $this->search();
    }

      // Generating search log
    $queryParams = \Drupal::request()->query->get('query');
    \Drupal::service('insights_service.user')->insertLastSearchitem($queryParams);

    $layoutPluginManager = \Drupal::service('plugin.manager.core.layout');
    $layoutInstance = $layoutPluginManager->createInstance('usma_search', []);
    $BlockPluginManager = \Drupal::service('plugin.manager.block');
    $regions = [
        'content' => $this->search(),
        'sidebar_left' => \Drupal::formBuilder()->getForm('Drupal\insight_search\Form\UsmaSearchFilterForm'),
        'sidebar_right' => $BlockPluginManager->createInstance('usma_thought_leadership_block',[])->build(),
    ];
    return $layoutInstance->build($regions);
  }

  //put your code here
  public function search() {
    $settings = \Drupal\Core\Site\Settings::get('macci_solr_config', null);
    if (is_null($settings)) {
      throw new \Exception('USMA Search Endpoint Missing from settings.php');
    }
    $config = array(
        'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
        'endpoint' => array(
            'localhost' => $settings
        )
    );
    $request = \Drupal::request();
    $client = new \Solarium\Client($config);
    $query = $client->createSelect();
    $query->setDocumentClass('Drupal\insight_search\SolrDocument');
    if ($request->get('category') === 'favourites') {
      $responseFav = \Drupal\insight_market_access\RequestCache::userFavourites();
      $favCollection = new \Doctrine\Common\Collections\ArrayCollection($responseFav['fav']);
      $geo = $favCollection->filter(function($item) {
                return $item['type'] === 'geography';
              })->map(function($it) {
                return $it['tid'];
              })->toArray();
      $provider = $favCollection->filter(function($item) {
                return $item['type'] === 'providers';
              })->map(function($item) {
                return $item['tid'];
              })->toArray();
      $payer = $favCollection->filter(function($item) {
                return $item['type'] === 'payers';
              })->map(function($item) {
                return $item['tid'];
              })->toArray();
    } else {

      $geo = $request->get('geo');
      $provider = $request->get('providers');
      $payer = $request->get('payers');
    }
    $ownership = $request->get('ownership');
    $type = $request->get('type');
    $qStr = $request->get('query');
    $sort = $request->get('sort', 'date');
    $sortFieldsMap = \Drupal\insight_search\SolrDocHelper::getSortFields();

    $content = new \stdClass();
    $content->paged = false;

    // get a select query instance

    //@todo Since MACC-I donot support free text search, the below 0 condition needs to be removed while implementing it
    if ($qStr && 0) {
      $queryString = ' tf_title:"' . $qStr . '"';
      $queryString .= ' tnsyn_body_field:"' . $qStr . '"';
      $queryString .= ' {!parent which=ss_type:report}tf_title:"' . $qStr . '"';
      $queryString .= ' {!parent which=ss_type:report}tnsyn_body_field:"' . $qStr . '"';
      $query->setQuery($queryString);
    } else {
      $query->setQuery('{!parent which=ss_type:report}');
    }

    $filterString = [];
//    $query->createFilterQuery('children')->setQuery('{!parent which=ss_type:report}ss_type:(text figure table folder)');
    if ($geo) {
      $filterString[] = 'itm_field_report_state:(' . implode(' ', $geo) . ')';
      $filterString[] = 'itm_field_report_metro_area:(' . implode(' ', $geo) . ')';
//      $query->createFilterQuery('geography')->setQuery('itm_field_report_geography:(' . implode(' ', $geo) . ')');
    }
    if ($provider) {
      $filterString[] = 'itm_field_report_providers:(' . implode(' ', $provider) . ')';
//      $query->createFilterQuery('provider')->setQuery('itm_field_report_providers:(' . implode(' ', $provider) . ')');
    }
    if ($payer) {
      $filterString[] = 'itm_field_report_payers:(' . implode(' ', $payer) . ')';
//      $query->createFilterQuery('payers')->setQuery('itm_field_report_payers:(' . implode(' ', $payer) . ')');
    }
    if ($type) {
      $filterString[] = 'itm_field_report_product_type:(' . implode(' ', $type) . ')';
//      $query->createFilterQuery('type')->setQuery('itm_field_report_product_type:(' . implode(' ', $type) . ')');
    }
    $skuResults =  current(RequestCache::skuReportsByEmail()??[])['insight_hli'];
    $filterQueryString = [];
    $skuProducts  = array_column($skuResults??[], 'Report_SKU');
    if($ownership['owned'] && !isset($ownership['not_owned'])){
      $filterQueryString[] = 'ss_field_report_product_code:(' . implode(' ', $skuProducts) . ')';
    } elseif ($ownership['not_owned'] && !isset($ownership['owned']) && !empty($skuProducts)) {
      $filterQueryString[] = '-(ss_field_report_product_code:(' . implode(' ', $skuProducts) . '))';
    }

    if(!empty($filterString)){
      $filterQueryString[] =  implode($filterString, ' OR ');
    }
    if(!empty($filterQueryString)){
      $query->createFilterQuery('filters')->setQuery(implode($filterQueryString, ' AND '));
    }

//// set start and rows param (comparable to SQL limit) using fluent interface
    if ($page = $request->get('page', null)) {
      $query->setStart($page * $this->pageLimit)->setRows($this->pageLimit);
    } else {
      $query->setStart(0)->setRows($this->pageLimit);
    }

//
//// set fields to fetch (this overrides the default setting 'all fields')
//    $query->setFields(array('*,[child parentFilter=ss_type:report]'));
    $fields = \Drupal\insight_search\SolrDocHelper::getFieldsList();
    $query->setFields($fields);
    //Add sort
    if ($sort) {
      $query->addSort($sortFieldsMap[$sort]['name'], $sortFieldsMap[$sort]['order']);
    }
    $facetSet = $query->getFacetSet();

    //@todo include when geo banner is to be shown
    if ($geo && count($geo) == 1) {
      $geoSugessionQuery = $client->createSelect();
      $facetSet = $geoSugessionQuery->getFacetSet();
      $facetSet->createFacetField('geo')->setField('its_field_report_product_type');
      $geoSugessionQuery->setFields(array('ss_product_type_name', 'its_field_report_product_type'));
      $geoSugessionQuery->setRows(0);
      $geoSugessionQuery->createFilterQuery('geo')->setQuery('itm_field_report_state:(' . reset($geo) . ')');
      $tags = \Drupal\insight_market_access\RequestCache::taxonomies();
      //      var_dump($tags,$geo);die;
      $content->facetState = $tags['states'][reset($geo)];
      $content->selectedGeo = reset($geo);
      $prods = [];
      foreach ($tags['products'] as $key => $item) {
        $prods[$key] = [
            'id' => $item['parent']['id'],
            'name' => $item['parent']['name'],
        ];
        if (isset($item['childrens'])) {
          foreach ($item['childrens'] as $child) {
            $prods[$child['id']] = $child;
          }
        }
      }
    }
    try {
      $resultset = $client->select($query);
      if (!empty($geoSugessionQuery)) {
        $geoSuggestionResult = $client->select($geoSugessionQuery);
      }
    } catch (\Solarium\Exception\HttpException $e) {
      $ping = $client->createPing();
      try {
        $result = $client->ping($ping);
      } catch (\Solarium\Exception\HttpException $xx) {
        throw new BadRequestHttpException('Unable to connect to solr');
      }
    }
    //@todo remove when top banner is to be shown.
    if (!empty($geoSugessionQuery)) {
      $facet = $geoSuggestionResult->getFacetSet()->getFacet('geo') ? $geoSuggestionResult->getFacetSet()->getFacet('geo')->getValues() : null;
      $facets = [];
      foreach ($facet as $type => $count) {
        if (!empty($facet) && !isset($facets[$type]) && $facet[$type]) {
          $facets[$type] = [
              'type_id' => $type,
              'type_name' => $prods[$type]['name'],
              'count' => $count
          ];
        }
      }
      $content->facets = $facets;
    }
    $build = [];
    $content->count = 0;
    //get value from config
    $config = \Drupal::config('insight_market_access.settings');
    $noOfDays = $config->get('no_of_days');
    $contentToShow = $config->get('content_to_show');

    if ($resultset) {
      foreach ($resultset->getDocuments() as $document) {
        //Adding facet info to build.

        $productCode = $document->getFields()['ss_field_report_product_code'];
        $updatedTimestamp = $document->getFields()['ds_updated_timestamp'];
        $updatedTimestamp = strtotime($updatedTimestamp);
        $updatedTimestamp = strtotime('+'.$noOfDays. 'day', $updatedTimestamp);
        $currentdatetime = date("Y-m-d h:i:s");
        $currentdatetime = strtotime($currentdatetime);
        if ($updatedTimestamp > $currentdatetime) {
          $newContentTagFlag = 1;
        }else{
          $newContentTagFlag = 0;
        }
        $IsPresentInSKU = array_filter($skuResults??[], function ($e) use (&$productCode) {
          return $e['Report_SKU'] == $productCode;
        });
        $build[] = [
          '#theme' => 'usma_search_item',
          '#content' => $document,
          '#contentToShow' => $contentToShow,
          '#newContentTagFlag' => $newContentTagFlag,
          '#ownership' => !empty($IsPresentInSKU) ? 'owned' : 'not-owned' ];
      }
      $content->count = $resultset->getNumFound();
    }
//    echo '<pre>';
//    dump($facets);
//    die;
    //Build data to render engine

    $content->items = $build;
    $content->sort = $sort;
    $content->query = $qStr;
    $content->sortOptions = array_map(function($item) {
      return $item['title'];
    }, $sortFieldsMap);


    if ($request->get('page', null)) {
      $content->paged = true;
    }
    return [
        '#theme' => 'usma_search',
        '#content' => $content,
        //'#attached' => ['drupalSettings' => ['totalItems' =>  $resultset->getNumFound() ]],
        '#attached' => ['drupalSettings' => ['totalItems' => !empty($resultset) ? $resultset->getNumFound() : 0 ]],
    ];
  }

}
