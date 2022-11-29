<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search\Controller;

use Drupal\Driver\Exception\Exception;
use Drupal\insight_search\Controller\BiopharmaSearchController;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\search_api_autocomplete\Suggestion\SuggestionFactory;

/**
 * Description of SearchController
 *
 * @author sakreddy
 */
class SearchController extends ControllerBase{

  //put your code here

  //const CURRENT_USER_SKU_BASE_URL = 'http://172.17.65.38/nodejs/api/get-sku';
  const CURRENT_USER_SKU_BASE_URL = 'http://172.17.65.79/sku/api/get-sku';
  
  public function searchCommon() {
    $account = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $user_current_business_unit = $account->get('field_last_visited_business_unit')->value;

//    die($user_current_business_unit);
    switch ($user_current_business_unit) {
      case "biopharma":
        $obj = new BiopharmaSearchController();
        return $obj->render();
        break;

      case "medtech":
        $user_current_business_unit;
        $obj = new BiopharmaSearchController();
        return $obj->render();
        break;
    }
  }

  /**
   * Search Feature Result
   * @param $keyword
   * @return array
   */
  public function searchFeatureResult($keyword){

    try {

      $settings = \Drupal\Core\Site\Settings::get('scms_solr_config',null);
      if(is_null($settings)){
        throw new \Exception('Search Endpoint Missing from settings.php');
      }
      $config = array(
        'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
        'endpoint' => array(
          'localhost' => $settings
        )
      );

      $user = $this->entityManager()->getStorage('user')->load($this->currentUser()->id());

      $business_unit = $user->get('field_last_visited_business_unit')->value;

      // set filter queries
      if($business_unit == 'biopharma') {
        $filterQueries[] = '(level:*.CSG AND (ss_report_type:(biopharma OR c-d )))';
      }
      else if($business_unit == 'medtech') {
        $filterQueries[] = '(level:*.CSG AND (ss_report_type:(medtech)))';
      }

      $filterQueries[] = '(-bs_field_abstract:true)';

      $client = new \Solarium\Client($config);
      $query = $client->createSelect();

      preg_match('/"([^"]+)"/', $keyword, $matches);
      $search_keyword = (!empty($matches) && count($matches) > 0) ? $keyword : '"'.$keyword.'"';
      $query->setQuery($search_keyword);

      // Set Field selection
      $query->setFields(['is_field_topic_nid,tf_field_topic_title,ss_field_topic_url,is_field_chapter_nid,tf_field_chapter_title,ss_field_chapter_url,ss_report_type,level,ss_url,ss_field_product_type_value']);

      //Set query filter
      $query->createFilterQuery('type')->setQuery(implode($filterQueries, ' OR '));

      // set sorting
      $query->setSorts(['is_field_chapter_order' => 'asc']);

      //Set edismax
      $edismax = $query->getEDisMax();
      $edismax->setQueryFields('topic_feature_result topic_feature_result_replace');

      //Set Grouping
      $groupComponent = $query->getGrouping();
      $groupComponent->addField('is_field_chapter_nid');
      $groupComponent->setMainResult(true);

      $resultset = $client->select($query);
      //    $debugResult = $resultset->getDebug();

      $result = [];
      foreach ($resultset->getDocuments() as $document) {
        $result[] = $document;
      }

      return $result;

    }catch (\Exception $e) {

    }
  }

  /**
   * TOC Search
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function tocSearch(){

    try {

      $json_post_data = \Drupal::request()->getContent();
      $post_data = \Drupal\Component\Serialization\Json::decode($json_post_data);
      $collection_config = ($post_data['bunit'] == 'macc') ? 'macci_solr_config' : 'scms_solr_config';
      $settings = \Drupal\Core\Site\Settings::get($collection_config);

      $config = array(
        'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
        'endpoint' => array(
          'localhost' => $settings
        )
      );

      $client = new \Solarium\Client($config);


      $search_keyword = $post_data['search_keyword'];
      $pieces = explode(" ", $search_keyword);
      $search_keyword_count = sizeof($pieces);
      $search_keyword_with_and = join(' AND ', $pieces);


      $content = new \stdClass();
      $content->paged = false;
      $query = $client->createSelect();


      $query->setQuery($search_keyword_with_and.'*');

      $edismax = $query->getEDisMax();
      $edismax->setQueryFields('tf_toc_search_prefix');

//      $query->addParam("stats",true);
//      $query->addParam("facet",true);
//      $query->addParam("facet.mincount",1);

      $query->addParam("json.facet","{\"report\":{type : terms,mincount:1,limit:-1,field:is_field_csg_nid,facet:{x:\"sum(termfreq('tf_toc_search_prefix','".$search_keyword."'))\"}},\"l1\":{type : terms,mincount:1,limit:-1,field:is_l1_nid,facet:{x:\"sum(termfreq('tf_toc_search_prefix','".$search_keyword."'))\"}},\"l2\":{type : terms,mincount:1,limit:-1,field:is_l2_nid,facet:{x:\"sum(termfreq('tf_toc_search_prefix','".$search_keyword."'))\"}},\"cs\":{type : terms,mincount:1,limit:-1,field:is_cs_nid,facet:{x:\"sum(termfreq('tf_toc_search_prefix','".$search_keyword."'))\"}}}");
      $query->setRows(0);
      
      $query->createFilterQuery('chapter_nid')->setQuery('is_field_csg_nid:('.implode(' OR ',$post_data['report_nids']).')');
      //$query->createFilterQuery('skip_overview')->setQuery('-level:1029743.CS');
      $query->createFilterQuery('level')->setQuery('-level:*.REPORT');
      $query->createFilterQuery('skip_overview1')->setQuery('-(level:*.CSG AND bs_field_csg_contains_abstract:true)');

      $resultset = $client->select($query);

      //      $debugResult = $resultset->getDebug();

      foreach ($resultset->getData()['facets'] as $facet) {
        foreach ($facet as $buckets) {
          foreach ($buckets as $nodeCount) {
            if($nodeCount['x'] > 0){
              $nodes[$nodeCount['val']] = $nodeCount['x'];
            }
          }
        }
      }

      //    dump($query);exit;

      return new JsonResponse($nodes);

    }catch (Exception $e) {

    }

  }
  
  function getUserEntitlements($busniess_unit) {   
    
        $currentAccount = \Drupal::currentUser();
        $mail = $currentAccount->getEmail();
        
        $client = \Drupal::httpClient();
        $request = $client->post(self::CURRENT_USER_SKU_BASE_URL, [
            'json' => [
                'username' => $mail,
                'BusinessUnit' => $busniess_unit,
            ]
        ]);
        
       // In case of any exception 

        $response = json_decode($request->getBody());
        
        if ($response->status == 'error') {
            return '0 OR 0';
        }
        //echo '<pre>';
        //print_r($response); die;
        if (isset($response) && !empty($response)) {
            $data = array();
                foreach ($response as $records_set) {
                foreach ($records_set as $dataset) { 
                    $i = 0;
                    foreach ($dataset as $key=>$chunk_data) {
                        //echo '<pre>'; print_r($chunk_data);die;
                        $exploded_data = explode(',',$chunk_data->rsku);
                        $data = array_merge($data, $exploded_data); 
                        $i++;
                    }
                
                }
                }
            
      $data = array_unique($data);
            if (isset($data) && count($data) > 0) {
                return implode(' OR ', $data);
            } else {
                return '0 OR 0';
            }
        }
    }

  /**
   * Insight Auto Suggestion
   */
  public function insightAutoSuggesstion() {
    
    try {

      $request = \Drupal::service('request_stack')->getCurrentRequest();
      $request_query = $request->get('q');
      $request_bunit = $request->get('bunit');

      if(empty($request_bunit)) {
        $request_bunit = 'biopharma';
      }

      switch ($request_bunit) {
        case "biopharma":
          $bunit_filter = 'ss_report_type:(ss_report_type:biopharma OR ss_report_type:c-d)';
          break;
        case "medtech":
          $bunit_filter = 'ss_report_type:(ss_report_type:medtech)';
          break;
        case "digital":
          $bunit_filter = 'ss_report_type:(ss_report_type:digital-library)';
          break;

      }

      $matches = [];
      $suggestions = [];

      $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');
      $config = array(
        'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
        'endpoint' => array(
          'localhost' => $settings
        )
      );

      $client = new \Solarium\Client($config);

      $content = new \stdClass();
      $content->paged = false;
      $query = $client->createSelect();


      $query->createFilterQuery('bunit')->setQuery($bunit_filter);
      $query->setRows(0);

      // get the facetset component
      $facetSet = $query->getFacetSet();
      $facetSet->createFacetField('suggestion')->setField('tnsyn_suggession_contents');
      $facetSet->setPrefix($request_query);
      $facetSet->setMinCount(1);
      $facetSet->setLimit(10);

      // execute query
      $result = $client->select($query);

      $suggestion_factory = new SuggestionFactory($request_query);

      if ($facet_result = $result->getFacetSet()->getFacet('suggestion')) {
        foreach ($facet_result->getValues() as $suggestion => $count) {
          $suggestions[] = $suggestion_factory->createFromSuggestedKeys($suggestion);
        }
      }

      //    $suggestions = $this->getAutocompleteSuggesterSuggestions($result, $suggestion_factory);

      foreach ($suggestions as $suggestion) {
        $build = $suggestion->toRenderable();
        if ($build) {
          try {
            $label = \Drupal::service('renderer')->render($build);
          }
          catch (\Exception $e) {
            continue;
          }

          // Decide what the action of the suggestion is – entering specific
          // search terms or redirecting to a URL.
          if ($suggestion->getUrl()) {
            // Generate an HTML-free version of the label to use as the value.
            // Setting the label as the value here is necessary for proper
            // accessibility via screen readers (which will otherwise read the
            // URL).
            $url = $suggestion->getUrl()->toString();
            $trimmed_label = trim(strip_tags((string) $label)) ?: $url;
            $matches[] = [
              'value' => $trimmed_label,
              'url' => $url,
              'label' => $label,
            ];
          }
          else {
            $matches[] = [
              'value' => trim($suggestion->getSuggestedKeys()),
              'label' => $label,
            ];
          }
        }
      }

      return new JsonResponse($matches);

    }catch (\Exception $e) {

    }

  }

}
