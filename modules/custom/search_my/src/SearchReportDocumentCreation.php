<?php

/**
 * @file Provides a class for forming document structure for Report Nodes.
 * @author Sunapu Siddharth <ssiddharth@dresources.com>
 */

namespace Drupal\insight_search;

use Drupal\insight_search\SearchDocumentCreation;
use Drupal\insight_search\SearchEmbedPptDocumentCreation;
//for function get_signed_url:
use Drupal\file\Entity\File;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Drupal\taxonomy\Entity\Term;
use Drupal\node\NodeInterface;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SearchReportDocumentCreation extends SearchDocumentCreation {

    const DATA_DIGGER_URL_TABLEAU_DASHBOARD = 'http://internal-druid-api-DruidAPI-1GEDW06GB3YTT-367163149.us-east-1.elb.amazonaws.com:8082/api/v1';

    //we can access all the variables of parent class.

    public function createDocument($document) {
        global $base_url;
        if (empty($document)) {
            return FALSE;
        }

        if ($document->ss_type == 'report') {
            $document_nid = $document->is_nid;
            $shared_parent_ids = $this->getParentEntityIdsFromMappingTable($document_nid);
            $parent_ids = $shared_parent_ids[0];
            $document_topic_nid = $parent_ids['topic']['entity_nid'];
            $document_chapter_nid = $parent_ids['chapter']['entity_nid'];

            if (empty($shared_parent_ids) || is_null($document_topic_nid) || empty($shared_parent_ids) || is_null($document_chapter_nid) || empty($document_chapter_nid)) {
                // file_put_contents('search_document.log', 'This document does not have topic chapter or report nid: ' . $document_nid . PHP_EOL, FILE_APPEND);
                return FALSE;
            }


            //if we have  a shared report we need to crete multiple report . Need to add the code for shared nodes .
            //load the toc and get the json and topic and chapter details .

            if (isset($document_topic_nid) && !empty($document_topic_nid)) {
                $node_exist_flag = $this->checkNodeExistORNot($document_topic_nid);
                if (!$node_exist_flag) {
                    return FALSE;
                }
                try {
                    $document_topic_entity = $this->entity_manager->getStorage('node')->load($document_topic_nid);
                    $document_topic_title = $document_topic_entity->get('title')->value;
                    $document_topic_url = $document_topic_entity->toUrl()->toString();
                } catch (Exception $e) {
                    \Drupal::logger("ERROR-$document_topic_nid")->error($e->getMessage());
                    //file_put_contents('search_document.log', 'ERRORTopicID-$document_topic_nid, Error:'.$e->getMessage() . PHP_EOL, FILE_APPEND);
                }
            }



            // This code is only for debuging purposes
            if (empty($document_topic_nid) || empty($document_chapter_nid) || empty($document_nid)) {
                //file_put_contents('search_document.log', 'This document does not have topic chapter or report nid: ' . $document_nid . PHP_EOL, FILE_APPEND);
                return FALSE;
            }

            try {
                $document_chapter_entity = $this->entity_manager->getStorage('node')->load($document_chapter_nid);
                $document_chapter_title = $document_chapter_entity->get('title')->value;
                $document_chapter_url = $document_chapter_entity->toUrl()->toString();
                $document_report_title = $document->tf_report_title;
            } catch (Exception $e) {
                \Drupal::logger("ERROR-$document_chapter_nid")->error($e->getMessage());
            }

            if ($document->ss_type == 'report') {
                $report_abstract_value = $this->getAbstractTextByReportID($document->is_nid);
            }

            //$cs_abstract_value = '';
            $device_report_data = '';
            $document_index_id = $document->index_id;

            $document_hash = $document->hash;
            $csg_abstract_value = $report_abstract_value;
            // $csg_abstract_value = $this->getNodeBodyContent($document_nid);
            //loading the reportjson from Database
            $report_data = $this->report->getReportToc($document_topic_nid, $document_chapter_nid, $document_nid); //it also fethces json from db
            // print_r($report_data);die;
            // If report data have nothing , simply returning FALSE
            if (count($report_data) <= 0) {
                return FALSE;
            }

            $document_report_entity = $this->entity_manager->getStorage('node')->load($document->is_nid);

            $req_data['topic_url'] = $document_chapter_url;
            $req_data['document_report_entity'] = $document_report_entity;


            $is_document_valid = $this->searchValidateDocument($document, 'report', $req_data);
            $check_tableau_dashboard_exists = $this->checkIFtableauDashboardExists($report_data);
            // In case of Tableau Dashboard passing the true state for the validation 
            if ($check_tableau_dashboard_exists) {
                $is_document_valid = 1;
            }

            // IF Document is not passed with Validation returning FALSE
            if (!$is_document_valid) {
                return FALSE;
            }



            // Get the first title node body field for report node.
            // If abstract is empty then saving the first node 
//            if (empty($csg_abstract_value)) {
//                if (isset($report_data['sub_data'])) {
//                    $text_entity_id = $this->getFirstTitleNodeBodyField($report_data['sub_data']);
//                    if (isset($text_entity_id) && !empty($text_entity_id)) {
//                        $first_text_node_body_content = $this->getAbstractBodyFieldByEnityId($text_entity_id);
//                    }
//                }
//                $csg_abstract_value = $first_text_node_body_content;
//            }
            // Adding description in case of OVERVIEW
            if (empty($csg_abstract_value) && $report_data['overview'] == 1) {
                $csg_abstract_value = $this->getDescriptionForOverview($report_data);
            }

            //echo '<pre>';
            //print_r($csg_abstract_value);die;
            // Getting SKU and setting in sm_field_sku for indexing 
            $document_sku = $this->combineReportSkus($document->ss_field_report_sku, $document->ss_field_previous_version_sku);
            if (isset($document_sku) && !empty($document_sku)) {
                $document_sku = explode(',', $document_sku);
                $document->addField('sm_field_sku', $document_sku);
            }

            //$document_product_type_value = $document->field_report_product_type;
            $this->report_common_fields_argument['document_product_type_value'] = $document_product_type_value;
            //set the topic type so that it can be used in the code elsewhere:
            $topic_type = $this->getReportType($document_topic_nid, $document_topic_entity);

            $this->report_common_fields_argument['topic_type'] = $topic_type;
            //setting the public variables of $report_common_fields_argument.
            $this->report_common_fields_argument['hash'] = $document_hash;
            $this->report_common_fields_argument['index_id'] = $document_index_id;
            $this->report_common_fields_argument['document_nid'] = $document_nid;

            // Adding some empty field
            $this->report_common_fields_argument['document_sku'] = $document_sku;
            $this->report_common_fields_argument['document_product_type_value'] = $document->sm_field_report_product_type;

            //setting the authors for the document ;
            $document_authors = $this->getDocumentAuthors($document);
            $this->report_common_fields_argument['document_authors'] = $document_authors;

            //adding author field to doc :
            $document->addField('tf_content_authors', $document_authors);

            //
            //echo "^^^^";
            //echo '<pre>';print_r($report_data);die;
            // Get the url from toc and call fn _get_csg_link to get the link of first l1
            // Adding additonal field for overviews.
            // no need to get the url and process it we are doint it in toc.
//  if (isset($document->ss_field_product_type_value) && $document->ss_field_product_type_value != 'OVERVIEW') {
            $report_url = $report_data['url_alias']; //add base_url here not used the ss_url bcoz we need to provide the first L1 url of report.
//    $document->setField('ss_url',$report_url);
//  }
//  //creation of parent_nodes array:
            $parent_nodes[] = array('nid' => $document_topic_nid, 'title' => $document_topic_title, 'url' => $document_topic_url);
            $parent_nodes[] = array('nid' => $document_chapter_nid, 'title' => $document_chapter_title, 'url' => $document_chapter_url);
            $parent_nodes[] = array('nid' => $document->is_nid, 'title' => $document_report_title, 'url' => $report_url);
            $this->report_common_fields_argument['parent_nodes'] = $parent_nodes;
            //ADDING OF Breadcrumb links and titles
            $this->createBreadcrumbTitlesAndBreadcrumbLinks($parent_nodes);
            $document = $this->setReportS3BucketUrl($document);
            $i = 0;
            //adding the report fields in the below function:
            $document = $this->insertAdditionalFieldIntoReportDocument($document);
            $childdoc = array();
            foreach ($document->getFields() as $field_name => $field_value) {
                if ($field_name == 'level') {
                    $childdoc[0][$field_name] = "$document_nid.CSG";
                } elseif ($field_name == 'id') {
                    $childdoc[0][$field_name] = $document->{$field_name} . "-1";
                } elseif ($field_name == 'ss_search_api_id') {
                    $childdoc[0][$field_name] = 'entity:node/' . $document->is_nid . '-1:en';
                } else {
                    $childdoc[0][$field_name] = $document->{$field_name};
                }
            }
            // Adding toc data here .
            // Adding chapter-nid as parent nid for csg.
            $childdoc[0]['is_parent_nid'] = $document_chapter_nid;

            //calling the validate method to check if the report has even a single folder:
            // Added condition for tableau dashboard , because It can come at root level
            // Adding more condition for OVERVIEW content , Because for overview content there is no any folder
            $folder_exists = $this->validateReportCheckIFfolderExists($report_data);
            if (!$folder_exists) {
                //file_put_contents('search_document.log', 'This report document does not have a folder: ' . $document_nid . PHP_EOL, FILE_APPEND);
                // Code commented by me because if report is not having any folder data will not br index.  
                return FALSE;
            }

            //rewriting the code to move all the below code to a helper class ://functionality to form docs for all the children:
            $report_child_docs = $this->getReportChildDocumentTreeStructure($document, $childdoc, $csg_abstract_value, $report_data);
            //print_r($report_child_docs);die;
            $childdoc = $report_child_docs['child_doc'];

            if (empty($csg_abstract_value)) {
                $csg_abstract_value = $report_child_docs['csg_abstract_value'];
            }

            $childdoc[0]["tf_body_field"] = strip_tags($csg_abstract_value);
            $childdoc[0]["tf_body_html_field"] = $csg_abstract_value;
            $document->setField("tf_body_field", strip_tags($csg_abstract_value));
            $document->setField("tf_body_html_field", $csg_abstract_value);
            if (!empty($childdoc)) {
                $document->setField("_childDocuments_", ($childdoc));
            }

            //fn to remove the extra fields of report document :
            $document = $this->removeReportDocumentExtraFields($document);
            file_put_contents('solr_report_doc_log.txt', print_r($document, TRUE), FILE_APPEND);
        }
        return $document;
    }

    /**
     * Insert additioal field into report document
     * @global string $base_url
     * @param object $document
     * @return object
     */
    public function insertAdditionalFieldIntoReportDocument($document) {
        global $base_url;
        $parent_nodes = $this->report_common_fields_argument['parent_nodes'];
        //loading the topic entity req by the common functions:
        $topic_entity = $this->entity_manager->getStorage('node')->load($parent_nodes[0]['nid']);
        $report_entity = $this->entity_manager->getStorage('node')->load($document->is_nid);
        //creation of tf_report_title:
        $doc_tf_report_title = ucwords($parent_nodes[0]['title']) . ' - ' . ucwords($parent_nodes[1]['title']) . ' - ' . ucwords($parent_nodes[2]['title']);
        $report_title = $parent_nodes[2]['title'];
        $report_type = $this->getReportType($parent_nodes[0]['nid'], $topic_entity);

        $document->setField("ss_field_csg_url", $document->ss_url);
        $document->setField("tf_report_title", $doc_tf_report_title);
        $document->setField('tf_report_title_sort', $doc_tf_report_title);
        $document->setField("tf_field_csg_title", $document->tf_report_title);
        $document->setField("is_field_csg_nid", $document->is_nid);
        $document->setField("level", "$document->is_nid.REPORT");
        $document->setField("ss_report_type", $report_type);
        $document->setField("is_field_topic_nid", $parent_nodes[0]['nid']);
        $document->setField("tf_field_topic_title", $parent_nodes[0]['title']);
        $document->setField("topic_feature_result", $document->tf_field_topic_title);
        $document->setField("ss_field_topic_url", $base_url . '/' . $parent_nodes[0]['url']);
        $document->setField("is_field_chapter_nid", $parent_nodes[1]['nid']);
        $document->setField("ss_field_chapter_url", $base_url . '/' . $parent_nodes[1]['url']);
        $document->setField("tf_field_chapter_title", $parent_nodes[1]['title']);
        $document->setField("tf_title", $document->tf_report_title);
        $document->setField("ss_type", 'container');
        //$document->setField("item_id", (string)$document->is_nid);
        $document->setField("ss_item_id", $document->is_nid);
        $document->setField("bs_field_is_chapter", 0); // by default this field should be set to false.
        $document->setField("bs_is_overview", $document->bs_field_overview); // by default this field should be set to false.
        $document->setField("sm_field_breadcrumb_titles", $this->report_common_fields_argument['breadcrumb_titles']);
        $document->setField("sm_field_breadcrumb_links", $this->report_common_fields_argument['breadcrumb_links']);
        if ($document->bs_field_overview == 'false') {
            
        } else {
            $document->setField("ss_field_product_type_value", 'OVERVIEW');
        }

        //add the chapter order to which the report belongs to :
        $chapter_order = 0;
        $chapter_order = $this->getChapterOrder($parent_nodes[1]['nid'], $topic_entity);
        $document->setField("bs_field_isgeography", $this->isReportGeography($report_entity));
        $document->setField("is_field_chapter_order", $chapter_order);
        //adding the fields product type - ss_field_product_type_value and bs_field_product_type(this will be true if it is an overview report else false)
        $topic_type = $this->report_common_fields_argument['topic_type'];
        //die($topic_type);
        if ($topic_type == 'medtech') {
            $document->setField('bs_field_product_type', true);
            //add additional fields for medtech reports .
            //add device therapy area tid and name and if it's additional material  the skus ?
            //take therapy area from topic since report doesn't have field.
            $topic_therapy_area_target_id = $topic_entity->field_therapyarea->target_id;
            if (!empty($topic_therapy_area_target_id)) {
                $therapy_area_term = Term::load($topic_therapy_area_target_id);
                $therapy_area_name = $therapy_area_term->getName();
                $document->setField('is_field_device_theraphy_area_tid', $topic_therapy_area_target_id);
                $document->setField('ss_field_device_theraphy_area_name', $therapy_area_name);
            }
            //field_therapyarea
        } else {
            //  $document->setField('bs_field_product_type', false);
            $document->setField('bs_field_product_type', 0);
        }

        if (isset($document->ss_field_report_product_type) && !empty($document->ss_field_report_product_type)) {
            $document->addField('ss_field_product_type_value', strtoupper(str_replace(' ', '', str_replace('-', '_', $document->ss_field_report_product_type))));
        }
        //adding the user biography and photo url : waiting for mir's code to be merged 
        //$document_author_photo_bio = $this->getAuthorBioAndPhoto($document);
        //$document->setField("sm_authors_photo_url", $document_author_photo_bio['photo_url']);
        //$document->setField("sm_authors_bio", $document_author_photo_bio['bio']);
        return $document;
    }

    /**
     * Removing CSG title if same as Chaper title
     */
    public function removeCsgTitleIfSameAsChapterTitle() {
        $parent_nodes = $this->report_common_fields_argument['parent_nodes'];
        // Remove CSG title if CSG title and Chapter title are same.
        if ($parent_nodes[1]['title'] == $parent_nodes[2]['title']) {
            $report_title = $parent_nodes[0]['title'] . " - " . $parent_nodes[1]['title'];
        } else {
            $report_title = $parent_nodes[0]['title'] . " - " . $parent_nodes[1]['title'] . " - " . $parent_nodes[2]['title'];
            $external_topic_name = $parent_nodes[0]['title'] . " | " . $parent_nodes[1]['title'] . " | " . $parent_nodes[2]['title'];
        }
        $this->report_common_fields_argument['external_topic_name'] = $external_topic_name;
    }

    /**
     * Create breadcrumb titles and breadcrumb liks 
     * @global string $base_url
     * @param array $parent_nodes
     */
    public function createBreadcrumbTitlesAndBreadcrumbLinks($parent_nodes) {
        global $base_url;
        $breadcrumb_titles = $breadcrumb_links = array();
        foreach ($parent_nodes as $parent_node) {
            array_push($breadcrumb_links, $base_url . '/' . $parent_node['url']);
            array_push($breadcrumb_titles, $parent_node['title']);
        }
        $this->report_common_fields_argument['breadcrumb_titles'] = $breadcrumb_titles;
        $this->report_common_fields_argument['breadcrumb_links'] = $breadcrumb_links;
    }

    /**
     * Get Parent Entity IDs From Mapping Table
     * @param type $entity_id
     * @return type
     */
    public function getParentEntityIdsFromMappingTable($entity_id) {
        $parent_ids = array();
        $con = \Drupal\Core\Database\Database::getConnection();
        $query = $con->select('insight_mapping', 'it')
                ->fields('it', array('topic_nid', 'chapter_nid', 'report_nid'))
                ->condition('nid', $entity_id, '=');
        $results = $query->execute()->fetchAll();
        foreach ($results as $key => $result) {
            $parent_ids[$key]['topic'] = array('entity_type' => 'topic', 'entity_nid' => $result->topic_nid);
            $parent_ids[$key]['chapter'] = array('entity_type' => 'chapter', 'entity_nid' => $result->chapter_nid);
        }
        return $parent_ids;
    }

    /**
     * Get All Nids/Entity and Data Under Folder 
     * @param type $array
     * @param array $allowed_nodes
     * @return type
     */
    public function getAllNidsUnderFolder($array, $allowed_nodes = array()) {
        $allowed_nodes = array('text', 'table', 'figure', 'attachment', 'file_attachment', 'tableau_dashboard');
        $allowed_insight_entity = array('folder');
        $result = array();
        foreach ($array as $key => $row) {
            if (in_array($row['container_type'], $allowed_nodes)) {
                $result[] = array('nid' => $row['nid'], 'title' => $row['title'], 'url_alias' => $row['url_alias'], 'container_type' => $row['container_type'], 'field_suppress_title' => $row['field_suppress_title']);
            } elseif (in_array($row['container_type'], $allowed_insight_entity)) {
                $result[] = array('nid' => $row['id'], 'title' => $row['name'], 'url_alias' => $row['url_alias'], 'container_type' => $row['container_type'], 'field_folder_suppress_title' => $row['field_folder_suppress_title']);
            }
            if (isset($row['sub_data']) && count($row['sub_data']) > 0) {
                $result = array_merge($result, $this->getAllNidsUnderFolder($row['sub_data'], $allowed_nodes));
            }
        }
        return $result;
    }

    /**
     * Get Node Body Content
     * @param type $node_id
     * @return type
     */
    public function getNodeBodyContent($node_id) {
        $node_body_field = NULL;
        if (!empty($node_id)) {
            $node_entity_object = $this->entity_manager->getStorage('node')->load($node_id);

            if ($node_entity_object instanceof NodeInterface) {
                if ($node_entity_object->hasField('body')) {
                    $node_body_field = $node_entity_object->get('body')->value;
                }
            }
        }
        return $node_body_field;
    }

    /**
     * Checking If landing card is exist or not
     * @param type $node_json_object
     * @return int
     */
    public function checkisLandingCard($node_json_object) {
        if ($node_json_object['container_type'] == 'landing_card') {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Checking If the content is abstract or not 
     * @param type $node_id
     * @return boolean
     */
    public function isAbstract($node_id) {
        if (!empty($node_id)) {
            $node_entity_object = $this->entity_manager->getStorage('node')->load($node_id);
            if (!empty($node_entity_object)) {
                if ($node_entity_object->hasField('field_isabstract')) {
                    $node_abstract_field = $node_entity_object->get('field_isabstract')->value;
                    if ($node_abstract_field) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Load custom Entity Or Node
     * @param type $json_object_of_object
     * @return type
     */
    public function loadCustomEntityOrNode($json_object_of_object) {
        $entity_id = array_key_exists('id', $json_object_of_object) ? $json_object_of_object['id'] : $json_object_of_object['nid'];
        $entity_type = $json_object_of_object['container_type'];
        $node_bundles_array = \Drupal::service('entity_type.bundle.info')->getBundleInfo('node');
        $node_bundles = array_keys($node_bundles_array);
        $entity_type = 'node';
        if (!empty($entity_type) && !empty($entity_id)) {
            $entity_object = $this->entity_manager->getStorage($entity_type)->load($entity_id);
        }
        return $entity_object;
    }

    /**
     * Get Report Type by Passing Topic ID and Topic Entity
     * @param type $topic_nid
     * @param type $topic_entity
     * @return string
     */
    public function getReportType($topic_nid = NULL, $topic_entity) {
        $report_type = NULL;
        if (is_object($topic_entity) && !$topic_entity->get('field_topic_type')->isEmpty()) {
            $topic_type = $topic_entity->get('field_topic_type')->getValue()[0]['value'];

            switch ($topic_type) {
                case 'disease':
                case 'epidemiology':
                case 'biosimilars':
                    $report_type = 'biopharma';
                    break;
                case 'device':
                    $report_type = 'medtech';
                    break;
                case 'drug':
                case 'drug_class':
                case 'therapeutic_area':
                case 'company':
                    $report_type = 'c-d';
                    break;
                case 'library':
                    $report_type = 'report-library';
                    break;
            }
        }
        return $report_type;
    }

    public function getReportChildDocumentTreeStructure($document, $childdoc, $csg_abstract_value, $report_data) {
        $i = 1;
        $cs_abstract_value = '';

//        print "hi--".$csg_abstract_value;exit;
        foreach ($report_data['sub_data'] as $child_l1) {
            $childdoc[$i] = $this->getCommonFieldsForReportDocumentChildTreeStructure($document, $child_l1);
            $childdoc[$i]["is_parent_nid"] = $document->is_nid;
            //need to check for what all node_types we need breadcrumb links and titles
            //$childdoc[$i]["sm_field_breadcrumb_titles"] = $document->sm_field_breadcrumb_titles;
            //$childdoc[$i]["sm_field_breadcrumb_links"] = $document->sm_field_breadcrumb_links;
            $childdoc[$i]["sm_field_breadcrumb_titles"] = $this->report_common_fields_argument['breadcrumb_titles'];
            $childdoc[$i]["sm_field_breadcrumb_links"] = $this->report_common_fields_argument['breadcrumb_links'];
            if ($child_l1['container_type'] == 'folder') {
                //folder directy under report
                $childdoc[$i]["level"] = "$document->is_nid.L1";
                $childdoc[$i]["ss_node_type"] = "folder";
                // Code started for PPT embded 
                if (isset($child_l1['attachment_type']) && !empty($child_l1['attachment_type']) && $child_l1['attachment_type'] == 'embed') {
                    $node_wrapper = $this->loadCustomEntityOrNode($child_l1);
                    $embed_ppt_class = new SearchEmbedPptDocumentCreation($this->entity_manager);
                    $embed_ppt_class->setContentTreeService($this->report, $this->chapter, $this->folder);
                    $embed_doc = $embed_ppt_class->createDocument($document, $child_l1, 1, $node_wrapper, $i);
                    if (!empty($embed_doc)) {
                        $childdoc[$i]['_childDocuments_'] = $embed_doc;
                    }
                }
                // Code ends with PPT embded
                else {
                    $j = 0;
                    foreach ($child_l1['sub_data'] as $child_l2) {
                        $childdoc[$i]["_childDocuments_"][$j] = $this->getCommonFieldsForReportDocumentChildTreeStructure($document, $child_l2);
                        //for Adding the toc feature linking
                        $childdoc[$i]["_childDocuments_"][$j]['is_parent_nid'] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid'];
                        $childdoc[$i]["_childDocuments_"][$j]['is_l2_nid'] = array_key_exists('id', $child_l2) ? $child_l2['id'] : $child_l2['nid'];
                        //print_r($child_l2);die;
                        if ($child_l2['container_type'] == 'folder') {
                            //for l2 under l1.
                            $childdoc[$i]["_childDocuments_"][$j]["level"] = "$document->is_nid.L2";
                            $childdoc[$i]["_childDocuments_"][$j]["ss_node_type"] = "folder";
                            $k = 0;
                            if (isset($child_l2['sub_data']) && count($child_l2['sub_data']) > 0) {
                                $l2_child_nodes = $this->getAllNidsUnderFolder($child_l2['sub_data']);
                            }
                            //under l2 everything comes as flat structure.
                            foreach ($l2_child_nodes as $child_l3) {
                                $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k] = $this->getCommonFieldsForReportDocumentChildTreeStructure($document, $child_l3);
                                $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]['is_parent_nid'] = array_key_exists('id', $child_l2) ? $child_l2['id'] : $child_l2['nid'];
                                if ($child_l3['container_type'] != 'folder') {
                                    //add a if else condition for embed ppt
                                    $node_type = $child_l3['container_type'];
                                    $node_wrapper = $this->loadCustomEntityOrNode($child_l3);
                                    $embed_attachment_field_value = '';
                                    if ($node_wrapper instanceof NodeInterface) {
                                        if ($node_wrapper->bundle() == 'file_attachment') {
                                            if ($node_wrapper->hasField('field_fattach_embed_attachment')) {
                                                $embed_attachment_field_value = $node_wrapper->get('field_fattach_embed_attachment')->getValue()[0]['value'];
                                            }
                                        }
                                    }
                                    if ($embed_attachment_field_value == 'embed') {
                                        //it is an embed content.
                                        // $embed_ppt_class = new SearchEmbedPptDocumentCreation($this->report, $this->entity_manager, $this->chapter, $this->folder);
//                  $embed_ppt_class = new SearchEmbedPptDocumentCreation($this->entity_manager);
//                  $embed_ppt_class->setContentTreeService($this->report, $this->chapter, $this->folder);
//                  $embed_doc = $embed_ppt_class->createDocument($document, $child_l3, 1, $node_wrapper, $i);
//                  if(!empty($embed_doc)){
//              $childdoc[$i]['_childDocuments_'] = $embed_doc;
//              }
                                        //$childdoc[$i]['_childDocuments_'] = $embed_doc;
                                    } else {
                                        //add the fields for text here.
                                        if ($node_type == 'text') {
                                            $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["ss_node_type"] = 'data';
                                            // In case of supress title setting folder title 
                                            if (isset($child_l3['field_suppress_title']) && $child_l3['field_suppress_title'] == 1) {
                                                $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["tf_title"] = $child_l2['title'];
                                            }
                                        } else {
                                            $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["ss_node_type"] = $node_type;
                                        }
                                        $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["is_landing_card"] = $this->checkisLandingCard($child_l3);
                                        $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["bs_field_abstract"] = $this->isAbstract(array_key_exists('id', $child_l3) ? $child_l3['id'] : $child_l3['nid']);
                                        if (empty($csg_abstract_value) && empty($cs_abstract_value) && $node_type == 'text' && !$this->checkisLandingCard($child_l3)) {
                                            $cs_abstract_value = $this->getNodeBodyContent($child_l3['nid']);
                                        }
                                        if ($node_type == 'text' || $node_type == 'table') {
                                            $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["tf_body_field"] = strip_tags($this->getNodeBodyContent(array_key_exists('id', $child_l3) ? $child_l3['id'] : $child_l3['nid']));
                                            $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["tf_body_html_field"] = $this->getNodeBodyContent(array_key_exists('id', $child_l3) ? $child_l3['id'] : $child_l3['nid']);
                                        } elseif ($child_l3['container_type'] == 'figure') {
                                            $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["ss_image_link"] = $this->getNodeImageUrl($child_l3['nid']);
                                        }
                                        //for adding the parent nids for TOC:
                                        $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]['is_cs_nid'] = array_key_exists('id', $child_l3) ? $child_l3['id'] : $child_l3['nid'];
                                        $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]['is_l2_nid'] = array_key_exists('id', $child_l2) ? $child_l2['id'] : $child_l2['nid']; // add l2 nid
                                        $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]['is_l1_nid'] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid']; // add li nid
                                    }
                                } else {
                                    $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]["ss_node_type"] = "folder";
                                    $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]['is_cs_nid'] = array_key_exists('id', $child_l3) ? $child_l3['id'] : $child_l3['nid'];
                                    $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]['is_l2_nid'] = array_key_exists('id', $child_l2) ? $child_l2['id'] : $child_l2['nid']; // add l2 nid
                                    $childdoc[$i]["_childDocuments_"][$j]["_childDocuments_"][$k]['is_l1_nid'] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid']; // add l1 nid
                                }
                                $k++;
                            }
                            $childdoc[$i]["_childDocuments_"][$j]['is_l2_nid'] = array_key_exists('id', $child_l2) ? $child_l2['id'] : $child_l2['nid'];
                            $childdoc[$i]["_childDocuments_"][$j]['is_l1_nid'] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid'];
                        } else {
                            //for cs or any other under l1.
                            $node_type = $child_l2['container_type'];

                            $node_wrapper = $this->loadCustomEntityOrNode($child_l2);
                            //Add condition for ppt embed.
                            $embed_attachment_field_value = '';
                            if ($node_wrapper instanceof NodeInterface) {

                                if ($node_wrapper->bundle() == 'file_attachment') {
                                    if ($node_wrapper->hasField('field_fattach_embed_attachment')) {
                                        $embed_attachment_field_value = $node_wrapper->get('field_fattach_embed_attachment')->getValue()[0]['value'];
                                    }
                                }
                            }
                            if ($embed_attachment_field_value == 'embed') {
                                //it is an embed content.
                                //$embed_ppt_class = new SearchEmbedPptDocumentCreation($this->report, $this->entity_manager, $this->chapter, $this->folder);
//              $embed_ppt_class = new SearchEmbedPptDocumentCreation($this->entity_manager);
//              $embed_ppt_class->setContentTreeService($this->report, $this->chapter, $this->folder);
//              $embed_doc = $embed_ppt_class->createDocument($document, $child_l2, 1, $node_wrapper, $i);
//              if(!empty($embed_doc)){
//              $childdoc[$i]['_childDocuments_'] = $embed_doc;
//              }
                            } else {
                                //add the fields for text here.
                                if ($node_type == 'text') {
                                    $childdoc[$i]["_childDocuments_"][$j]["ss_node_type"] = 'data';
                                    // In case of supress title setting folder title 
                                    if (isset($child_l2['field_suppress_title']) && $child_l2['field_suppress_title'] == 1) {
                                        $childdoc[$i]["_childDocuments_"][$j]["tf_title"] = $child_l1['title'];
                                    }
                                } else {
                                    $childdoc[$i]["_childDocuments_"][$j]["ss_node_type"] = $node_type;
                                }

                                $childdoc[$i]["_childDocuments_"][$j]["is_landing_card"] = $this->checkisLandingCard($child_l2);
                                $childdoc[$i]["_childDocuments_"][$j]["bs_field_abstract"] = $this->isAbstract($child_l2['nid']);
                                if (empty($csg_abstract_value) && empty($cs_abstract_value) && $node_type == 'text' && !$this->checkisLandingCard($child_l2)) {
                                    $cs_abstract_value = $this->getNodeBodyContent($child_l2['nid']);
                                }

                                if ($node_type == 'text' || $node_type == 'table') {
                                    $childdoc[$i]["_childDocuments_"][$j]["tf_body_field"] = strip_tags($this->getNodeBodyContent($child_l2['nid']));
                                    $childdoc[$i]["_childDocuments_"][$j]["tf_body_html_field"] = $this->getNodeBodyContent($child_l2['nid']);
                                } elseif ($child_l2['container_type'] == 'figure') {
                                    $childdoc[$i]["_childDocuments_"][$j]["ss_image_link"] = $this->getNodeImageUrl($child_l2['nid']);
                                }

                                $childdoc[$i]["_childDocuments_"][$j]["level"] = "$document->is_nid.CS";
//            $childdoc[$i]["_childDocuments_"][$j] =  array_merge((array)$childdoc[$i]["_childDocuments_"][$j],(array)$this->addParentRelationshipForNodes($child_l2,NULL,$child_l1));
                                $childdoc[$i]["_childDocuments_"][$j]['is_cs_nid'] = array_key_exists('id', $child_l2) ? $child_l2['id'] : $child_l2['nid'];
                                $childdoc[$i]["_childDocuments_"][$j]['is_l1_nid'] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid'];
                            }
                        }
                        $j++;
                    }
                }

                $childdoc[$i]['is_l1_nid'] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid'];
            } else {
                //cs or any other directly under report.
                $node_type = $child_l1['container_type'];
                $node_wrapper = $this->loadCustomEntityOrNode($child_l1);
                //add condition for embed .
                $embed_attachment_field_value = '';
                if ($node_wrapper instanceof NodeInterface) {
                    if ($node_wrapper->bundle() == 'file_attachment') {
                        if ($node_wrapper->hasField('field_fattach_embed_attachment')) {
                            $embed_attachment_field_value = $node_wrapper->get('field_fattach_embed_attachment')->getValue()[0]['value'];
                        }
                    }
                }
                if ($embed_attachment_field_value == 'embed') {
                    //it is an embed content.
                    //$embed_ppt_class = new SearchEmbedPptDocumentCreation($this->report, $this->entity_manager, $this->chapter, $this->folder);
//          $embed_ppt_class = new SearchEmbedPptDocumentCreation($this->entity_manager);
//          $embed_ppt_class->setContentTreeService($this->report, $this->chapter, $this->folder);
//          $embed_doc = $embed_ppt_class->createDocument($document, $child_l1, 2, $node_wrapper, $i);
                } else {
                    //add the fields for text here.
                    if ($node_type == 'text') {
                        $childdoc[$i]["ss_node_type"] = 'data';
                        // In case of supress title setting folder title 
                        if (isset($child_l1['field_suppress_title']) && $child_l1['field_suppress_title'] == 1) {
                            $childdoc[$i]["ss_node_type"] = $report_data['title'];
                        }
                    } else {
                        $childdoc[$i]["ss_node_type"] = $node_type;
                    }

                    $childdoc[$i]["is_landing_card"] = $this->checkisLandingCard($child_l1);
                    $childdoc[$i]["bs_field_abstract"] = $this->isAbstract(array_key_exists('id', $child_l1));
                    if (empty($csg_abstract_value) && empty($cs_abstract_value) && $node_type == 'text' && !$this->checkisLandingCard($child_l1)) {
                        $cs_abstract_value = $this->getNodeBodyContent(array_key_exists('id', $child_l1));
                    }

                    if ($node_type == 'text' || $node_type == 'table') {
                        $childdoc[$i]["tf_body_field"] = strip_tags($this->getNodeBodyContent($child_l1['nid']));
                        $childdoc[$i]["tf_body_html_field"] = $this->getNodeBodyContent($child_l1['nid']);
                    } elseif ($child_l1['container_type'] == 'figure') {
                        $childdoc[$i]["ss_image_link"] = $this->getNodeImageUrl($child_l1['nid']);
                    }
                    $childdoc[$i]["level"] = "$document->is_nid.CS";
                    if ($this->isAbstract($child_l1['nid'])) {
                        $childdoc[$i]["is_l1_nid"] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid'];
                        $childdoc[0]["bs_field_csg_contains_abstract"] = 'true';
                    } else {
                        $childdoc[$i]["is_cs_nid"] = array_key_exists('id', $child_l1) ? $child_l1['id'] : $child_l1['nid'];
                    }
                }
            }
            $i++;
        }

        if (empty($csg_abstract_value) && !empty($cs_abstract_value)) {
            $csg_abstract_value = $cs_abstract_value;
        }
        return array('child_doc' => $childdoc, 'csg_abstract_value' => $csg_abstract_value);
    }

    /**
     * Getting common filds for report/ document
     * @global string $base_url
     * @global string $base_url
     * @param type $document
     * @param type $child_entity
     * @return type
     */
    public function getCommonFieldsForReportDocumentChildTreeStructure($document, $child_entity) {
        global $base_url;
        $childdoc_at_individual_level = array();
        $document_hash = $this->report_common_fields_argument['hash'];
        $document_index_id = $this->report_common_fields_argument['index_id'];
        $document_nid = $this->report_common_fields_argument['document_nid'];
        $parent_nodes = $this->report_common_fields_argument['parent_nodes'];

        $childdoc_at_individual_level["id"] = "$document_hash-$document_index_id-" . (array_key_exists('id', $child_entity) ? $child_entity['id'] : $child_entity['nid'] ) . "-$document_nid";
        $childdoc_at_individual_level["hash"] = $document_hash;
        $childdoc_at_individual_level["index_id"] = $document_index_id;
        //$childdoc_at_individual_level["item_id"] = array_key_exists('id', $child_entity) ? (string)$child_entity['id'] : (string)$child_entity['nid'];
        $childdoc_at_individual_level["ss_item_id"] = array_key_exists('id', $child_entity) ? $child_entity['id'] : $child_entity['nid'];
        $childdoc_at_individual_level["is_nid"] = array_key_exists('id', $child_entity) ? $child_entity['id'] : $child_entity['nid'];
        $childdoc_at_individual_level["tf_title"] = array_key_exists('name', $child_entity) ? $child_entity['name'] : $child_entity['title'];
        $childdoc_at_individual_level["sm_field_sku"] = $document->sm_field_sku;
        $childdoc_at_individual_level["ss_url"] = $child_entity['url_alias'];
        $childdoc_at_individual_level["ss_report_type"] = $document->ss_report_type;
        $childdoc_at_individual_level["sm_field_breadcrumb_titles"] = $this->report_common_fields_argument['breadcrumb_titles'];
        $childdoc_at_individual_level["sm_field_breadcrumb_links"] = $this->report_common_fields_argument['breadcrumb_links'];
        $childdoc_at_individual_level["is_field_topic_nid"] = $parent_nodes[0]['nid'];
        $childdoc_at_individual_level["tf_field_topic_title"] = $parent_nodes[0]['title'];
        $childdoc_at_individual_level["ss_field_topic_url"] = $base_url . '/' . $parent_nodes[0]['url'];
        $childdoc_at_individual_level["is_field_chapter_nid"] = $parent_nodes[1]['nid'];
        $childdoc_at_individual_level["tf_field_chapter_title"] = $parent_nodes[1]['title'];
        $childdoc_at_individual_level["ss_field_chapter_url"] = $base_url . '/' . $parent_nodes[1]['url'];
        $childdoc_at_individual_level["is_field_csg_nid"] = $parent_nodes[2]['nid'];
        $childdoc_at_individual_level["tf_report_title"] = $document->tf_report_title;
        $childdoc_at_individual_level["tf_report_title_sort"] = $document->tf_report_title;
        $childdoc_at_individual_level["tf_field_csg_title"] = $parent_nodes[2]['title'];
        $childdoc_at_individual_level["ss_field_csg_url"] = $base_url . '/' . $parent_nodes[2]['url'];
        $childdoc_at_individual_level["TopicName"] = $parent_nodes[0]['title'];
        $childdoc_at_individual_level["topic_feature_result"] = $document->topic_feature_result;
        $childdoc_at_individual_level["level"] = "$document->is_nid.CS";
        $node_type = $this->folder->getEntityTypeCustomFunction($child_entity['container_type']);
        $childdoc_at_individual_level["ss_search_api_id"] = 'entity:' . $node_type . '/' . $childdoc_at_individual_level['is_nid'] . ':en';
        if (!empty($document->ds_field_publish_date)) {
            $childdoc_at_individual_level['ds_field_publish_date'] = $document->ds_field_publish_date;
        }
        // Adding the auther infomation at different content type level
        $document_authors = $this->getDocumentAuthors($document);
        if (isset($document->ss_field_primary_author) && !empty($document->ss_field_primary_author)) {
            $childdoc_at_individual_level['ss_field_primary_author'] = $document->ss_field_primary_author;
            $childdoc_at_individual_level['tf_content_authors'] = $document_authors;
        }
        // Adding product type value 
        if ($document->bs_field_overview == 'false') {
            
        } else {
            $childdoc_at_individual_level['ss_field_product_type_value'] = 'OVERVIEW';
        }

        $childdoc_at_individual_level['bs_field_overview'] = $document->bs_field_overview;
        if ($child_entity['container_type'] == 'tableau_dashboard') {
            global $base_url;
            $get_val_url = parse_url($base_url, PHP_URL_PATH);
            $document_nid = $child_entity['nid'];
            $chapter_url_alias = str_replace($get_val_url, "", $document->ss_url);
            $shared_parent_ids = $this->getParentEntityIdsFromMappingTable($document_nid);
            $parent_ids = $shared_parent_ids[0];
            $document_topic_nid = $parent_ids['topic']['entity_nid'];
            if (isset($document_topic_nid) && !empty($document_topic_nid)) {
                $document_topic_entity = $this->entity_manager->getStorage('node')->load($document_topic_nid);
            }
            $document_chapter_nid = $document->is_field_chapter_nid;
            if (isset($document_chapter_nid) && !empty($document_chapter_nid)) {
                $tm_chapter_title_entity = $this->entity_manager->getStorage('node')->load($document_chapter_nid);
            }

            //  $topic_type = $this->getReportType($document_topic_nid, $document_topic_entity);
//     if ($topic_type != 'medtech') {
//       return FALSE; //return false if chapter is not a device chapter.
//     }

            $tm_chapter_title = $tm_chapter_title_entity->get('title')->value;
            $document_topic_title = $document_topic_entity->get('title')->value;
            $document_topic_url = $document_topic_entity->toUrl()->toString();
            $document_ss_url = $base_url . '/' . $document->ss_url;
            //code for adding url alias for chapters:
            if ($tm_chapter_title == 'Commercial Targeting') {
                $product = 'commercial-targeting';
                $product_type = 'Commercial Targetting';
                $about_append_url = 'about-commercial-targeting';
                $document_ss_url = $document_ss_url . '/' . $about_append_url;
                $document->setField("is_field_chapter_order", 1);
            } elseif ($tm_chapter_title == 'Brand Tracking') {
                $product = 'brand-tracking';
                $product_type = 'Brand Track';
                $about_append_url = 'about-brand-tracking';
                $document_ss_url = $document_ss_url . '/' . $about_append_url;
                $document->setField("is_field_chapter_order", 2);
            }

            $chapter_details = array(
                'Brand Track' => array(
                    'land_url' => 'brand-tracking',
                    'market_insights' => 'about-brand-tracking',
                    'chapter_title' => 'Brand Tracking',
                    'node_type' => 'bt_tableau'
                ),
                'Commercial Targetting' => array(
                    'land_url' => 'commercial-targeting',
                    'market_insights' => 'about-commercial-targeting',
                    'chapter_title' => 'Commercial Targeting',
                    'node_type' => 'ct_tableau'
                )
            );
            $topicnid = $document_topic_nid;
            if (isset($child_entity['nid']) && !empty($child_entity['nid'])) {
                $topic_node = $document_topic_entity;
                $tableau_node = $this->entity_manager->getStorage('node')->load($child_entity['nid']);
            }

            if ($tableau_node instanceof NodeInterface && $topic_node instanceof NodeInterface) {
                $deviceseries_id = $topic_node->get('field_device_series_id')->value;
                $tableau_details = $tableau_node;
                if (!empty($tableau_details) && !empty($deviceseries_id)) {
                    $topic_url = $document_topic_url;
                    $url = self::DATA_DIGGER_URL_TABLEAU_DASHBOARD . "/" . "tableau/metadata/" . $product . "/" . $deviceseries_id;
                    try {
                        $response = \Drupal::httpClient()->get($url, array(
                            'timeout' => 5000,
                            'method' => 'GET',
                            'headers' => array('Content-Type' => 'application/json')
                                )
                        );
                        $result = (string) $response->getBody();
                        if (empty($result)) {
                            // return FALSE;
                        }
                    } catch (RequestException $e) {
                        //return FALSE;
                    }

                    if (isset($result) && !empty($result)) {
                        $metadata[$product] = json_decode($result);
                    }
                    $entity_id_hash = $document_hash . '-' . $document_index_id . '-' . $tableau_node->nid->value;
                    $dashboardurlhash = md5($topicnid . $tableau_node->field_link->uri);
                    $report_title = $topic_node->title->value . ' - ' . $chapter_details[$product_type]['chapter_title'] . ' - ' . $tableau_node->title->value;
                    $chapter_url = $base_url . '/' . $topic_url . '/' . $chapter_details[$product_type]['land_url'];
                    $tableau_ip_url = $chapter_url . '/' . strtolower($tableau_node->title->value) . '/tableau';
                    $childdoc_at_individual_level["tf_report_title"] = $report_title;
                    $childdoc_at_individual_level["level"] = $tableau_node->nid->value . '.REPORT';
                    $childdoc_at_individual_level["Tableau_Dashboards"] = $tableau_node->title->value;
                    $childdoc_at_individual_level["Client"] = $tableau_node->field_client_name->value;
                    $childdoc_at_individual_level["Customer_dashboard_link"] = $tableau_node->field_link->uri;
                    $childdoc_at_individual_level["is_tableau"] = TRUE;
                    $childdoc_at_individual_level["hash_url"] = $dashboardurlhash;
                    $childdoc_at_individual_level["tf_field_chapter_title"] = $chapter_details[$product_type]['chapter_title'];
                    $childdoc_at_individual_level["tf_body_field"] = $tableau_node->field_description->value;
                    $childdoc_at_individual_level["ss_url"] = $tableau_ip_url;
                    $childdoc_at_individual_level["ss_node_type"] = $child_entity['container_type'];
                    $childdoc_at_individual_level['DeviceSeriesID'] = $deviceseries_id;
                    $childdoc_at_individual_level['SKU'] = $metadata['brand-tracking']->SKU;
                    $childdoc_at_individual_level['DeviceSeries'] = $topic_node->title->value;
                    $childdoc_at_individual_level['Tableau_Dashboards'] = $tableau_node->title->value;
                    $childdoc_at_individual_level['Client'] = $tableau_node->field_client_name->value;
                    $childdoc_at_individual_level['ss_node_type'] = $chapter_details[$product_type]['node_type'];
                    $childdoc_at_individual_level['level'] = ($product_type == 'Brand Track') ? $tableau_node->nid->value . '.BT' : $tableau_node->nid->value . '.CT';
                    $childdoc_at_individual_level['ss_report_type'] = $document->ss_report_type;
                    $childdoc_at_individual_level['Customer_dashboard_link'] = $tableau_node->field_link->uri;
                    $childdoc_at_individual_level['tf_report_title'] = $report_title;
                    $childdoc_at_individual_level['sm_field_sku'] = $document->sm_field_sku;
                    $childdoc_at_individual_level['hash_url'] = $dashboardurlhash;
                    $childdoc_at_individual_level['tf_field_chapter_title'] = $chapter_details[$product_type]['chapter_title'];

                    if (!empty($document->ds_field_publish_date)) {
                        $childdoc_at_individual_level['ds_field_publish_date'] = $document->ds_field_publish_date;
                    }

                    $childdoc_at_individual_level['tf_body_field'] = $tableau_node->field_description->value;
                    $childdoc_at_individual_level['ss_field_chapter_url'] = $chapter_url . '/' . $chapter_details[$product_type]['market_insights'];
                    $childdoc_at_individual_level['topic_feature_result'] = $topic_node->title->value;
                    $childdoc_at_individual_level['topic_feature_result_replace'] = $topic_node->title->value;

                    if (isset($metadata[$product]) && !empty($metadata[$product])) {
                        if ($product_type == 'Brand Track') {
                            $metadata_config = array(
                                'TherapyArea' => $metadata['brand-tracking']->TherapyArea,
                                'Module' => $metadata['brand-tracking']->Module,
                                'Segment' => $metadata['brand-tracking']->Segement,
                                'Model' => $metadata['brand-tracking']->Model,
                                'Manufacturer' => $metadata['brand-tracking']->Manufacturer,
                                'Brand' => $metadata['brand-tracking']->Brand
                            );
                        } else {
                            $metadata_config = array(
                                'TherapyArea' => $metadata['commercial-targeting']->TherapyArea,
                                'Module' => $metadata['commercial-targeting']->Module,
                                'Segment' => $metadata['commercial-targeting']->Segment,
                                'im_hospital_id' => $metadata['commercial-targeting']->Hospital_ID,
                                'hospital_name' => $metadata['commercial-targeting']->Hospital_Name,
                                'city' => $metadata['commercial-targeting']->City,
                                'county' => $metadata['commercial-targeting']->County_Name,
                                'state' => $metadata['commercial-targeting']->State,
                                'im_zip' => $metadata['commercial-targeting']->ZIP
                            );
                        }
                    }

                    foreach ($metadata_config as $seg => $datavalue) {
                        if (!empty($datavalue)) {
                            $childdoc_at_individual_level[$seg] = (array) $datavalue;
                        }
                    }
                }
            }
        }
        // Code end for indexing tableau dashboard

        return $childdoc_at_individual_level;
    }

    /**
     * Combine report SKUs
     * @param string $current_sku
     * @param srring $previous_sku
     * @return array
     */
    public function combineReportSkus($current_sku, $previous_sku) {
        $combined_skus = array_merge(explode(' ', $current_sku), explode(' ', $previous_sku));
        return $current_sku;
    }

    /**
     * Removing report document extra fields 
     * @param object $document
     * @return type
     */
    public function removeReportDocumentExtraFields(&$document) {
        $document->removeField('ss_field_previous_version_sku');
        $document->removeField('ss_field_report_sku');
        $document->removeField('sm_field_primary_author');
        $document->removeField('sm_field_secondary_author');
        $document->removeField('ss_search_api_datasource');
        return $document;
    }

    /**
     * Get Docuemnt Author
     * @param type $document
     * @return type
     */
    public function getDocumentAuthors($document) {
        $doc_primary_authors = $document->ss_field_primary_author;
        return $doc_primary_authors;
    }

    /**
     * Add Parent Relationship for Nodes
     * @param array $cs_level
     * @param array $l2_level
     * @param array $l1_level
     * @return array
     */
    public function addParentRelationshipForNodes($cs_level = NULL, $l2_level = NULL, $l1_level = NULL) {
        $cs_nid = $l2_nid = $l1_nid = 0;
        if (!empty($cs_level)) {
            $cs_nid = array_key_exists('id', $cs_level) ? $cs_level['id'] : $cs_level['nid'];
        }
        if (!empty($l2_level)) {
            $l2_nid = array_key_exists('id', $l2_level) ? $l2_level['id'] : $l2_level['nid'];
        }
        if (!empty($l1_level)) {
            $l1_nid = array_key_exists('id', $l1_level) ? $l1_level['id'] : $l1_level['nid'];
        }
        return array(
            'is_cs_nid' => $cs_nid,
            'is_l2_nid' => $l2_nid,
            'is_l1_nid' => $l1_nid,
        );
    }

    /**
     * Get chapter order
     * @param int $chapter_nid
     * @param int $topic_entity
     * @return int
     */
    public function getChapterOrder($chapter_nid, $topic_entity) {
        if (empty($topic_entity)) {
            return;
        }
        $chapter_order = array_search($chapter_nid, array_column($topic_entity->field_topic_chapter_reference->getValue(), 'target_id'));
        return $chapter_order;
    }

    /**
     * Get the author Bio and Photo
     * @param type $document
     * @return type
     */
    public function getAuthorBioAndPhoto($document) {
        $author_bio = $author_photo_url = $authors = array();
        array_push($authors, $document->itm_field_report_author_user_id);
        array_push($authors, $document->itm_field_report_secondary_author_user_id);
        foreach ($authors as $author_id) {
            $author_entity = $this->entity_manager->getStorage('user')->load($author_id);
            $photo_url_target_id = $author_entity->user_picture->target_id;
            if (!empty($photo_url_target_id)) {
                //load the s3 url of the user picture.
                $file_entity = File::load($photo_url_target_id);
                $filename = $file_entity->getFilename();
                $url = $this->get_file_s3_bucket_url($filename);
            }
            $photo_url = $url; //get the first in array since we can have only one picture 
            $biography = $author_entity->field_biography->value;
            array_push($author_photo_url, $photo_url);
            array_push($author_bio, $biography);
        }
        return array('photo_url' => $author_photo_url, 'bio' => $author_bio);
    }

    /**
     * Create device chapter document
     * @global string $base_url
     * @param type $document
     * @return boolean
     */
    public function createDeviceChapterDocument($document) {
        //fetch the topic :
        global $base_url;
        $get_val_url = parse_url($base_url, PHP_URL_PATH);
        $document_nid = $document->its_chapter_nid;
        $chapter_url_alias = str_replace($get_val_url, "", $document->ss_url);
        $shared_parent_ids = $this->getParentEntityIdsFromMappingTable($document_nid);
        $parent_ids = $shared_parent_ids[0];
        $document_topic_nid = $parent_ids['topic']['entity_nid'];
        $document_topic_nid = 1;
        $document_topic_entity = $this->entity_manager->getStorage('node')->load($document_topic_nid);
        $topic_type = $this->getReportType($document_topic_nid, $document_topic_entity);
        if ($topic_type != 'medtech') {
            return FALSE; //return false if chapter is not a device chapter.
        }

        $document_chapter_nid = $document_nid;
        $document_topic_title = $document_topic_entity->get('name')->value;
        $document_topic_url = $document_topic_entity->toUrl()->toString();

        $document_ss_url = $base_url . '/' . $document->ss_url;
        //code for adding url alias for chapters:
        if ($document->tm_chapter_title == 'Commercial Targeting') {
            $about_append_url = 'about-commercial-targeting';
            $document_ss_url = $document_ss_url . '/' . $about_append_url;
            $document->setField("is_field_chapter_order", 1);
        } elseif ($document->tm_chapter_title == 'Brand Tracking') {
            $about_append_url = 'about-brand-tracking';
            $document_ss_url = $document_ss_url . '/' . $about_append_url;
            $document->setField("is_field_chapter_order", 2);
        } else {
            //load the toc and get the json and topic and chapter details .
            // Need to check if csg's are present inside it . if yes then add url of csg else about page url.
            $chapter_json = $this->chapter->getChapterToc($document_topic_nid, $document_chapter_nid); //it also fethces json from db
            if (!empty($chapter_json)) {
                // Get the url from toc and call fn _get_csg_link to get the link of first l1.
                if (isset($chapter_json['report'])) {
                    $chapters_first_report_url_alias = $chapter_json['report'][0]['url_alias'];
                    $document_ss_url = $chapters_first_report_url_alias;
                }
            } else {
                $about_append_url = 'about-market-insights';
                $document_ss_url = $document_ss_url . '/' . $about_append_url;
            }
            $document->setField("is_field_chapter_order", 0);
        }
        //additional fields :
        $document->setField("tf_field_chapter_title", $document->tm_chapter_title);
        $document->setField("is_field_chapter_nid", $document->its_chapter_nid);
        $document->setField("ss_url", $document_ss_url);
        $document->setField("ss_report_type", 'medtech');
        $document->setField("level", '.FR');
        $document->addField("item_id", $document->its_chapter_nid);
        $document->addField("is_field_topic_nid", $document_topic_nid);
        $document->addField("tf_field_topic_title", $document_topic_title);
        $document->addField("topic_feature_result", $document_topic_title);
        $document->addField("ss_field_topic_url", $base_url . '/' . $document_topic_url);
        $document->addField("bs_field_is_chapter", 'true');
        $document->addField("ss_type", 'container');
        return $document;
    }

    /**
     * Check whether document is valid or not 
     * @param type $document
     * @param type $document_type
     * @return type
     */
    public function searchValidateDocument($document, $document_type, $data) {
        switch ($document_type) {
            case 'report':
                $is_document_valid = $this->validateReportDocument($document, $data);
                break;
            case 'chapter':
                $is_document_valid = $this->validateChapterDocument($document); //need to add implementation for this
                break;
            case 'report_library':
                $is_document_valid = $this->validateReportLibraryDocument($document); //need to add implementation for this
                break;
            default:
                break;
        }
        return $is_document_valid;
    }

    /**
     * Validate report document 
     * @param type $document
     * @return int
     */
    public function validateReportDocument($document, $data) {
       $is_documnent_valid = 1;
        $device_csg_data = $data['document_report_entity'];
        // Check if the document has a SKU.
        if (!isset($document->ss_field_report_sku) || empty($document->ss_field_report_sku)) {
            $data_topic_url = $data['topic_url'];
            $topic_url_parts = explode('/', $data_topic_url);
            if (!($topic_url_parts[1] == 'device') && $document->bs_field_overview != 'true') {
                \Drupal::logger('missingSku')->error('missingSku for NID:' . $document->is_nid);
                $is_documnent_valid = 0;
            } 
            elseif ($document->bs_field_overview != 'true') {
                if (empty($device_csg_data->get('field_report_is_geography')->value)) {
                    \Drupal::logger('ReportIndexingError')->error('missingGeo for NID:' . $document->is_nid);
                    $is_documnent_valid = 0;
                } else {
                    if (($device_csg_data->get('field_report_is_geography')->value == 0)) {
                        // Adding condition to skip the shared Csg's these are - Country Overviews and Methodology.
                        if ($device_csg_data->get('field_report_toc_type')->value == 'ADDITIONAL_MATERIAL') {
                            $is_documnent_valid = 0;
                        }
                    } else {
                        $is_documnent_valid = 0;
                    }
                }
            }
        }


        // Check if the document has a date associated.
        if (!isset($document->ds_field_publish_date) && empty($document->ds_field_publish_date)) {
            \Drupal::logger('ReportIndexingError')->error('missingPublishDate for NID:' . $document->is_nid);
            $is_documnent_valid = 0;
        }
        if (!isset($document->ss_url) && empty($document->ss_url)) {
            $is_documnent_valid = 0;
            \Drupal::logger('ReportIndexingError')->error('SS URL url is missing for NID:' . $document->is_nid);
        }
        if (!isset($document->site) && empty($document->site)) {
            $is_documnent_valid = 0;
            \Drupal::logger('ReportIndexingError')->error('Site url is missing for NID:' . $document->is_nid);
        }

        return $is_documnent_valid;
    }

    /**
     * Generate search log
     * @param type $document
     * @param type $error
     */
    public function searchLog($document, $error = NULL) {
        $errMsg = date("l jS \of F Y h:i:s A") . ": Document with nid: $document->is_nid has a problem.";
        if ($error) {
            $errMsg .= " Error: $error";
        }
        //file_put_contents('search_document.log', $errMsg . PHP_EOL, FILE_APPEND);
    }

    /**
     * Check if Tableau dashboard is exist or not
     * @param type $report_data
     * @return int
     */
    public function checkIFtableauDashboardExists($report_data) {
        $tableau_exists = 0;
        if (isset($report_data['sub_data']) || !empty($report_data['sub_data'])) {
            foreach ($report_data['sub_data'] as $report_sub_data) {
                if ($report_sub_data['container_type'] == 'tableau_dashboard') {
                    $tableau_exists = 1;
                    break;
                }
            }
        }
        return $tableau_exists;
    }

    /**
     * Validate report in case of folder not exist
     * @param type $report_data
     * @return int
     */
    public function validateReportCheckIFfolderExists($report_data) {
        $folder_exists = 0;
        // Returning TRUE for overview content  , before there is no any folder content
        if (isset($report_data['overview']) && $report_data['overview'] == 1) {
            return 1;
        }
        // Checkng for folder and Tableau dashboard
        if (isset($report_data['sub_data']) || !empty($report_data['sub_data'])) {
            foreach ($report_data['sub_data'] as $report_sub_data) {
                if ($report_sub_data['container_type'] == 'folder' || $report_sub_data['container_type'] == 'tableau_dashboard') {
                    $folder_exists = 1;
                    break;
                }
            }
        }
        return $folder_exists;
    }

    /**
     * Set report S3 bucket url 
     * @param type $document
     * @return type
     */
    public function setReportS3BucketUrl($document) {
        $url = [];
        $con = \Drupal\Core\Database\Database::getConnection();
        $query = $con->select('node__field_content_reference', 'cr');
        $query->join('node__field_fattach_embed_attachment', 'tt', 'tt.entity_id = cr.field_content_reference_target_id');
        $query->join('node__field_fig_chart_file', 'file', 'file.entity_id = cr.field_content_reference_target_id');
        $query->join('node_field_data', 'node', 'file.entity_id = node.nid');
        $query->fields('file', array('field_fig_chart_file_target_id'));
        $query->fields('node', array('title'));
        $query->condition('cr.entity_id', $document->is_nid, '=');
        $query->condition('tt.bundle', 'file_attachment', '=');
//    $query ->condition('tt.field_fattach_embed_attachment_value', 'sidebar', '=');
        //$results = $query->execute()->fetchObject(); 
        $results = $query->execute();
        foreach ($results as $resultsdata) {
            if (isset($resultsdata->field_fig_chart_file_target_id) && !empty($resultsdata->field_fig_chart_file_target_id)) {
                $file_entity = File::load($resultsdata->field_fig_chart_file_target_id);
                $filename = $file_entity->getFilename();
                $csg_filenames[] = $filename;
                $csg_file_uri[] = $file_entity->getFileUri();
                $url[] = $this->get_file_s3_bucket_url($filename);
                $url_withcdn[] = file_create_url($file_entity->getFileUri());
                $file_names[] = $resultsdata->title;
            }
        }
        $document->setField("sm_field_file_attachment_name", $file_names);
        $document->setField("sm_field_file_attachment_url", $url_withcdn);
        // }
        return $document;
    }

    /**
     * Checking is report is Geographies
     * @param object $report_entity
     * @return int
     */
    public function isReportGeography($report_entity) {
        $topic_type = $this->report_common_fields_argument['topic_type'];
        if ($topic_type == 'medtech') {
            $node_geo_value = $report_entity->field_report_toc_type->getValue();
            if (isset($node_geo_value[0])) {
                $is_geography = $report_entity->field_report_toc_type->getValue()[0]['value'];
                if ($is_geography == 'Geographies') {
                    return 1;
                }
            }
        }
        return 0;
    }

    /**
     * function to get the image url for figure nodes:
     * Previously it was field_fig_chart_file now I changed as field_file_image_attachment
     */
    public function getNodeImageUrl($node_nid) {
        $node_data = $this->entity_manager->getStorage('node')->load($node_nid);
        if ($node_data->hasField('field_file_image_attachment')) {
            foreach ($node_data->get('field_file_image_attachment') as $file) {
                $file_entity = File::load($file->target_id);
               // $filename = $file_entity->getFilename();
                $url[] = file_create_url($file_entity->getFileUri());
               // $url[] = $this->get_file_s3_bucket_url($filename);
            }
        }
        return $url;
    }

    /**
     * Get abstract text by report ID
     * @param type $report_nid
     * @return type
     */
    function getAbstractTextByReportID($report_nid) {
        $con = \Drupal\Core\Database\Database::getConnection();
        $query = $con->select('node__field_content_reference', 'cr');
        $query->join('node__field_text_type', 'tt', 'tt.entity_id = cr.field_content_reference_target_id');
        $query->fields('cr', array('field_content_reference_target_id'));
        $query->condition('cr.entity_id', $report_nid, '=');
        $query->condition('tt.field_text_type_value', 'Abstract', '=');
        $results = $query->execute()->fetchObject();
        if (isset($results->field_content_reference_target_id) && !empty($results->field_content_reference_target_id)) {
            return $this->getAbstractBodyFieldByEnityId($results->field_content_reference_target_id);
        }
        return;
    }

    /**
     * Get abstract body field by entity ID
     * @param int $abstract_id
     * @return string
     */
    function getAbstractBodyFieldByEnityId($abstract_id) {
        try {
            $abstract_object = $this->entity_manager->getStorage('node')->load($abstract_id);
            return $abstract_object->body->value;
        } catch (Exception $e) {
            \Drupal::logger('getAbstractBodyFieldByEnityIdERROR')->notice($e->getMessage());
        }
    }

    /**
     * Checking whether node is exist or not
     * @param int $nid
     * @return object
     */
    function checkNodeExistORNot($nid) {
        $values = \Drupal::entityQuery('node')->condition('nid', $nid)->execute();
        $node_exists = !empty($values);
        return $node_exists;
    }

    /**
     * Get description for overview
     * @param type $report_data
     * @return type
     */
    function getDescriptionForOverview($report_data) {
        if (isset($report_data['sub_data'][0]['nid']) && !empty($report_data['sub_data'][0]['nid'])) {
            return $this->getAbstractBodyFieldByEnityId($report_data['sub_data'][0]['nid']);
        }
    }

    /**
     * Get first title node body field
     * @param type $report_data
     * @return type
     */
//    function getFirstTitleNodeBodyField($report_data) {
//        if (isset($report_data['container_type']) && !empty($report_data['container_type']) && $report_data['container_type'] == 'text') {
//            return isset($innerdata['nid']) ? $innerdata['nid'] : '';
//        } else {
//            if (isset($report_data) && !empty($report_data)) {
//                foreach ($report_data as $innerdata) {
//                    if (isset($innerdata['container_type']) && !empty($innerdata['container_type']) && $innerdata['container_type'] == 'text') {
//                        return isset($innerdata['nid']) ? $innerdata['nid'] : '';
//                    } else {
//                        return $this->getFirstTitleNodeBodyField($innerdata['sub_data']);
//                    }
//                }
//            }
//        }
//    }
}
