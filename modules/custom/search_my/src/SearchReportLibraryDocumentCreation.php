<?php

/**
 * @file Provides a class for forming document structure for Report Library.
 * @author Sunapu Siddharth <ssiddharth@dresources.com>
 */

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search;
use Drupal\insight_search\SearchDocumentCreation;
use Drupal\s3fs\StreamWrapper;
use Drupal\file\Entity\File;
use Drupal\Core\Url;    


class SearchReportLibraryDocumentCreation extends SearchDocumentCreation{
  
  public function createDocument($document, $document_type) {
    if (empty($document)) {
      return;
    }
    $document_nid = $document->is_nid;
    //adding new fields:
    if (!empty($document->sm_field_file_attachment_url)) {
      $report_library_node = \Drupal::entityTypeManager()->getStorage('node')->load($document->is_nid)->toArray();
      $sfs3 = new StreamWrapper\S3fsStream();
      // Set S3 bucket urls for the binaries.
      $url = [];
      foreach ($report_library_node['field_file_attachments'] as $file) {
        $file_entity = File::load($file['target_id']);
        $file_uri = $file_entity->getFileUri();
        $sfs3->setUri($file_uri);
//      $sfs3->config['use_cname'] = 0;
        $url[] = $sfs3->getExternalUrl();
      }
      $document->setField("sm_field_file_attachment_url", $url);
    }
    //for level of report library:
    $document->setField("level", "$document_nid.REPORT");
    $exclude_parent_fields = array('sm_field_file_attachment_mime', 'sort_field_file_attachment_mime');
    if ($document_type == 'digital') {
      $document->addField('ss_report_type', 'digital-report');
    } else {
      $document->addField('ss_report_type', 'report-library');
    }
//    $document->setField('field_sku', $report_sku);
    $document->setField('ss_type', 'content_library');
    //for html field and non-html field
    $document->setField("tf_title", $document->tf_report_title);
    $document->setField("tf_body_field", strip_tags($document->tf_body_html_field));
    
    // Setting 
    // Added code to handle the ss_platform_url 
    
    $query = \Drupal::database()->select('node__field_report_sku', 't');
                            $query->fields('t', ['entity_id']);
                            $sub_part = db_and();
                            $sub_part->where("FIND_IN_SET('".$document->sm_field_sku."', t.field_report_sku_value) > 0");
                            $query->condition($sub_part);
                            $query->condition('t.bundle', 'report');
                            $query->range(0,1);
                            $query->execute();
    $result_set = $query->execute()->fetchObject();
    
    if (isset($result_set->entity_id) && !empty($result_set->entity_id)) {
      $document->setField("ss_plaform_url", \Drupal::service('path.alias_manager')->getAliasByPath('/node/'.$result_set->entity_id));
    }
    
    
    // Added publication date for report library
    if (isset($document->ds_changed) && !empty($document->ds_changed)) {
            $document->setField("ds_field_publish_date", $document->ds_changed);
    }
    
    // End code to handle the ss_platform_url 

    
    //remove unwanted fields :
    $document->removeField('sm_context_tags');
    $document->removeField('its_field_legacy_category');
    $document->removeField('its_field_report_research_type');
    $document->removeField('its_field_destination_category');
    $document->removeField('its_field_deliverable_type');
    $document->removeField('is_field_legacy_category');
    $document->removeField('is_field_destination_category');
    $document->removeField('is_field_deliverable_type');
    $document->removeField('its_im_field_geography');
    $document->removeField('im_field_report_research_type');
    $document->removeField('is_field_geography');
    $document->removeField('im_field_report_research_type');
    $document->removeField('its_field_therapy_area_disease');
    $document->removeField('its_field_library_trpy_ara_dseries');
    $document->removeField('is_field_library_trpy_ara_dseries_parent');
    $document->removeField('is_field_library_trpy_ara_dseries');
    //child documents:
    foreach ($document->getFields() as $field_name => $field_value) {
      if ($field_name == 'level') {
        $childdoc[$field_name] = "$document_nid.RL";
      } elseif ($field_name == 'id') {
        $childdoc[$field_name] = $document->{$field_name} . "-1";
      } elseif ($field_name == 'ss_search_api_id') {
        $childdoc[$field_name] = $document->{$field_name} . "-1";
      } else {
        $childdoc[$field_name] = $document->{$field_name};
      }
      if (in_array($field_name, $exclude_parent_fields)) {
        unset($document->{$field_name});
      }
      $childdoc["ss_node_type"] = "report";
    }
    $document->addField("_childDocuments_", array($childdoc));
//    echo "sidnew";
//    echo "<pre>";print_r($document);exit;
    return $document;
  }
  
  
}