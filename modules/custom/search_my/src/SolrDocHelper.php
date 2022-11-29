<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search;

/**
 * Description of SolrDocHelper
 *
 * @author sakreddy
 */
class SolrDocHelper {

  //put your code here

  public static function rewriteFields($data) {
    $out = [];
    foreach ($data as $key => $value) {
      $out[self::solrField($key)] = $value;
    }
    return $out;
  }

  public static function solrField($key = null) {

    if (is_null($key)) {
      return null;
    }

    $fieldsMap = [
        'tf_report_title' => 'report_title',
        'ds_field_report_publication_date' => 'pub_date',
        'ss_url' => 'report_url',
        'itm_field_report_state' => 'geo',
        'its_field_report_product_type' => 'type',
        'its_field_report_providers' => 'provider',
//        'ds_updated_timestamp' => 'updated_timestamp',
        'its_report_id' => 'report_id',
    ];

    return isset($fieldsMap[$key]) ? $fieldsMap[$key] : $key;
  }

  public static function getFieldsList() {
    $fields = array('ss_url', 'ss_product_type_name', 'its_field_report_product_type','tf_report_title', 'ss_field_report_product_code','ds_updated_timestamp','its_report_id');
    return array_merge($fields, array_map(function($item) {
              return $item['name'];
            }, self::getSortFields()));
  }

  public static function getSortFields() {
    return [
        'title' => [
            'name' => 'tf_report_title_sort',
            'order' => 'asc',
            'title' => 'Title'
        ],
        'date' => [
            'name' => 'ds_field_report_publication_date',
            'order' => 'desc',
            'title' => 'Publication Date'
        ]
    ];
  }

}
