<?php

namespace Drupal\cricket_api\Services;

use Drupal\Driver\Exception\Exception;
use GuzzleHttp\Client;

/**
 * Indicates the access service.
 */
class DataFetchService {

  /**
   * Indicates the HTTP Client.
   *
   * @var GuzzleHttp\Client;
   */
  protected $client;

  /**
   * {@inheritdoc}
   */
  public function __construct(Client $http_client) {
    $this->client = $http_client;
  }

  /**
   * Fetch the user access report from.
   */
  public function getMatchList() {
    try {
      $client = \Drupal::httpClient();
      $response = $client->get('http://apinew.cricket.com.au/matches?completedLimit=2&inProgressLimit=3&upcomingLimit=3');
      $data = $response->getBody();
      $match = json_decode($data);
      $matches_list = $match->matchList->matches;
      \Drupal::logger("matches")->notice("<pre>" . print_r($matches_list, TRUE) .  "</pre>");

      return $matches_list;
//      $request = $client->post($url, [
//        'headers' => [
//          'Content-Type' => 'application/json',
//        ],
//        'json' => [
//          "username" => $email,
//          "BusinessUnit" => $business_unit,
//          "Topic_Nid" => $topic_id,
//        ],
//      ]);

    }
      catch (Exception $e) {
    }
  }


  public function user_report_library_access( $previos_sku ) {
    try{
      $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');
      $config = array(
        'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
        'endpoint' => array(
          'localhost' => $settings
        )
      );
      $client = new \Solarium\Client($config);
      $query = $client->createSelect();
      $returned_fields = array('item_id:is_nid', 'sm_field_sku', 'tf_report_title', 'sm_field_destination_category_name', 'ds_field_publish_date', 'sm_field_file_attachment_url','sm_field_file_attachment_name');
      $query->setFields(array_unique($returned_fields));
      $filterArray[] = 'ss_type:content_library';
      $filterArray[] = 'ss_node_type:report';
      $filterArray[] =  "sm_field_sku: (" . $previos_sku . ")";
      $result = [
        "status" => FALSE,
      ];
      if($previos_sku){
        $query->addParam('fq', $filterArray);
        $query->addParam('df', 'tf_title');
        $query->setStart(0)->setRows(20);
        $result_set = $client->select($query);
        $resultData = $result_set->getData();
        if ($resultData['response']['numFound'] > 0) {
          $lib_nid_det['nid'] = $resultData['response']['docs'][0]['item_id'];
          $lib_nid_det['sku'] = $resultData['response']['docs'][0]['sm_field_sku'][0];
          $lib_nid_det['title']= $resultData['response']['docs'][0]['tf_report_title'];
          $lib_nid_det['category_name']= $resultData['response']['docs'][0]['sm_field_destination_category_name'];
          $lib_nid_det['sm_field_file_attachment_url']= $resultData['response']['docs'][0]['sm_field_file_attachment_url'];
          $lib_nid_det['ds_field_publish_date'] = $resultData['response']['docs'][0]['ds_field_publish_date'];
          $lib_nid_det['sm_field_file_attachment_name'] = $resultData['response']['docs'][0]['sm_field_file_attachment_name'];
          $Digitalservice = \Drupal::service('insight_search.digitalreport');
          foreach ($lib_nid_det['sm_field_file_attachment_name'] as $urlkey => $filename) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $icon_type = $Digitalservice->insight_platform_get_icon_type(trim($extension));
            $lib_nid_det['sm_field_file_attachment_name']['icon_type'][$urlkey] = $icon_type;
          }
          $result = array(
            "status" => TRUE,
            "numfound" => $resultData['response']['numFound'],
            "solr_res" => $lib_nid_det,
          );
          return $result;
        }
      }
      return $result;
    } catch (Exception $e){

    }
  }
}
