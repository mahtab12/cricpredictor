<?php

/**
 * @file Provides a class for forming document structure for embed PPT.
 * @author Sunapu Siddharth <ssiddharth@dresources.com>
 */

namespace Drupal\insight_search;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

use Drupal\insight_search\SearchDocumentCreation;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Drupal\file\Entity\File;
use Aws\Credentials\Credentials;
use Drupal\node\NodeInterface;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class SearchEmbedPptDocumentCreation extends SearchDocumentCreation {

    const INSIGHT_SEARCH_MODULE_PATH = 'modules/common/insights-platform-generic-module/insight_search/solr_jar';

    /**
     * Forms the solr document for embed PPT.
     *
     * @param Object docuemnt,Array $node_data, int $document_levels_to_be_created,Object $node_wrapper,int $doc_counter
     * @return array
     */
    public function createDocument($document, $node_data, $document_levels_to_be_created, $node_wrapper, $doc_counter) {
        //print_r($node_data);die;
        $document_hash = $document->hash;
        $document_index_id = $document->index_id;
        $document_nid = $document->is_nid;
        $i = $doc_counter;
        //print_r($node_wrapper->get('title')->value);die;
        $parent_nodes = $this->report_common_fields_argument['parent_nodes'];
        $report_title = $parent_nodes[2]['title'];
        $node_data_nid = array_key_exists('id', $node_data) ? $node_data['id'] : $node_data['nid'];
        if ($node_wrapper instanceof NodeInterface) {
            if ($node_wrapper->hasField('field_embed_toc_string')) {
                $json_data_embed = $this->convertTOCStringInDesiredFormat($node_wrapper->get('field_embed_toc_string')->value);
                $node_data_embed_ins['toc'] = $this->sendTOCStringGetArrayFormat($json_data_embed);
                $node_data_embed_toc = $node_data_embed_ins;
                $embed_doc = array();
                $document_nid = $document->is_nid;
                $file = array_shift($node_wrapper->get('field_fig_chart_file')->getValue());
                $cs_embed_filename_uri_and_filename = $this->getS3UrlAndFilename($file);
                $cs_embed_filename_uri = $cs_embed_filename_uri_and_filename['url'];
                $cs_embed_filename = $cs_embed_filename_uri_and_filename['filename'];
                $document->setField("ss_embed_rel", "embedrel");
                $extension = pathinfo($cs_embed_filename, PATHINFO_EXTENSION);
                $embed_extension_type = ['ppt', 'pptx'];
                //$embed_doc[$i]["is_parent_nid"] = $document_nid;
                //$embed_doc[$i]["ss_embed_rel"] = 'embedrel';
                $is_embed = 1;

                $filepath = $this->store_ppt_embed_files_in_local($cs_embed_filename_uri); //working 
                if (!empty($filepath)) {
                    $type = $this->get_embed_file_type_for_parsing($extension);
                    //print_r($type);
                    //   print_r($document_levels_to_be_created);die;
                    if ($document_levels_to_be_created == 1) {
                        $embed_first_slide = $node_data_embed_toc['toc'][0]['pageNo'];

                        // print_r($embed_first_slide);die;
                        //  $embed_slide_number_next = $embed_first_slide;
                        //   $final_parsed_contents = $this->retrieve_slide_details($type, $embed_first_slide, $embed_slide_number_next, $filepath);
                        //   $start = 0;
//      foreach ($final_parsed_contents as $final_parsed_content) {
//        //for adding all the other fields into the document :
//        foreach ($document->getFields() as $field_name => $field_value) {
//          $embed_doc[$start][$field_name] = $document->{$field_name};
//        }
//        $node_type = 'embed';
//        $embed_doc[$start]["id"] = "$document_hash-$document_index_id-$node_data_nid-$document_nid-$start";
//        $embed_doc[$start]["ss_embed_rel"] = 'embedrel';
//        $embed_doc[$start]["is_field_embed_cs_id"] = $node_data_nid;
//        $embed_doc[$start]["ss_node_type"] = $node_type;
//        $embed_doc[$start]["hash"] = $document_hash;
//        $embed_doc[$start]["index"] = $document_index_id;
//        $embed_doc[$start]["is_embed"] = $is_embed;
//        $embed_doc[$start]["tf_title"] = $final_parsed_content['slidName'];
//        $embed_doc[$start]["tf_body_field"] = $final_parsed_content['slidConttent'];
//        $embed_doc[$start]["is_field_embed_slide_no"] = $final_parsed_content['slidNumber'];
//        $embed_doc[$start]["is_landing_card"] = $this->checkisLandingCard($node_data_nid);
//        $embed_doc[$start]["ss_url"] = $node_data['url_alias'];
//        $embed_doc[$start]["level"] = "$document_nid.CS";
//        $embed_doc[$start]["is_nid"] = $node_data_nid.$document_nid.$start;
//        $embed_doc[$start]["ss_search_api_id"] = "$node_data_nid-$document_nid-$start";//maybe need to change this
//        $start++;
//        
//        
//        
//      }
//      
//      
                        //var_dump($node_data);die;  

                        $tf_field_topic_title = $document->tf_field_topic_title;
                        $document_topic_nid = $document->is_field_topic_nid;
                        // Code started by Ishwar for PPT Embeded 
                        //print_r($node_data_embed_toc);die;
                        foreach ($node_data_embed_toc['toc'] as $key => $toc_data) {
                            // print_r($toc_data); die;
                            // foreach ($document->getFields() as $field_name => $field_value) {
                            //  $embed_doc[$key][$field_name] = $document->{$field_name};
                            // }
                            $embed_doc[$key]["level"] = "$document_nid.L1";
                            $embed_doc[$key]["ss_node_type"] = "folder";
                            $embed_doc[$key]["id"] = "$document_hash-$document_index_id-$node_data_nid-$document_nid-$l";
                            $embed_doc[$key]["hash"] = $document_hash;
                            $embed_doc[$key]["index_id"] = $document_index_id;
                            $embed_doc[$key]["item_id"] = $node_data_nid;
                            $embed_doc[$key]["is_nid"] = $node_data_nid;
                            $embed_doc[$key]["tf_title"] = $toc_data['title'];
                            $embed_doc[$key]["is_embed"] = $is_embed;
                            $embed_doc[$key]["ss_embed_rel"] = 'embedrel';
                            $embed_doc[$key]["tf_report_title"] = $report_title;
                            $embed_doc[$key]["tf_field_topic_title"] = $document_topic_title;
                            $embed_doc[$key]["is_field_topic_nid"] = $document_topic_nid;
                            $embed_slide_number_start = $toc_data['pageNo'];
                            $embed_slide_number_next = $node_data_embed_toc['toc'][$key + 1]['pageNo'];
                            if (empty($embed_slide_number_next)) {
                                $embed_slide_number_next = $embed_slide_number_start;
                            }
                            //  $final_parsed_contents = retrieve_slide_details($type, $embed_slide_number_start, $embed_slide_number_next, $filepath);
                            $final_parsed_contents = $this->retrieve_slide_details($type, $embed_slide_number_start, $embed_slide_number_next, $filepath);

                            $start = 0;
                            foreach ($final_parsed_contents as $index => $final_parsed_content) {
                                if ($index == 0) {
                                    $embed_doc[$key]["ss_url"] = $node_data['url_alias'] . '#' . $node_data_nid . '-' . $toc_data['pageNo'];
                                    $embed_doc[$key]["is_field_embed_slide_no"] = $final_parsed_content['slidNumber'];
                                }
                                $embed_doc[$key]["_childDocuments_"][$start]["id"] = "$document_hash-$document_index_id-$node_data_nid-$document_nid-embed-$m";
                                $embed_doc[$key]["_childDocuments_"][$start]["ss_embed_rel"] = 'embedrel';
                                $embed_doc[$key]["_childDocuments_"][$start]["is_field_embed_cs_id"] = $document_nid;
                                $embed_doc[$key]["_childDocuments_"][$start]["ss_node_type"] = 'embed';
                                $embed_doc[$key]["_childDocuments_"][$start]["hash"] = $document_hash;
                                $embed_doc[$key]["_childDocuments_"][$start]["index"] = $document_index_id;
                                $embed_doc[$key]["_childDocuments_"][$start]["tf_title"] = $final_parsed_content['slidName'];
                                $embed_doc[$key]["_childDocuments_"][$start]["tf_body_field"] = $final_parsed_content['slidConttent'];
                                $embed_doc[$key]["_childDocuments_"][$start]["is_field_embed_slide_no"] = $final_parsed_content['slidNumber'];
                                $embed_doc[$key]["_childDocuments_"][$start]["is_landing_card"] = $this->checkisLandingCard($document_nid);
                                $embed_doc[$key]["_childDocuments_"][$start]["ss_url"] = $node_data['url_alias'] . '#' . $node_data_nid . '-' . $toc_data['pageNo'];
                                $embed_doc[$key]["_childDocuments_"][$start]["level"] = "$document_nid.CS";
                                $embed_doc[$key]["_childDocuments_"][$start]["tf_report_title"] = $report_title;
                                $embed_doc[$key]["_childDocuments_"][$start]["tf_field_topic_title"] = $document_topic_title;
                                $embed_doc[$key]["_childDocuments_"][$start]["is_field_topic_nid"] = $document_topic_nid;
                                $start++;
                                $m++;
                            }
                            $l++;
                        }

                        // Code ends by Ishwar for PPT Embeded 
                        //echo '<pre>';      
                        // print_r($embed_doc);die;
                    } else {
                        //for embed ppts that have to come under a report directly where we need tp create chapter and the slides under it .
                        $m = 0;
                        $l = 0;

                        // echo '<pre>';      
                        // print_r($embed_doc);die;

                        foreach ($node_data_embed_toc['toc'] as $key => $toc_data) {
                            //for adding all the other fields into the document :
                            //addtional check
                            if (isset($toc_data['pageNo'])) {
                                foreach ($document->getFields() as $field_name => $field_value) {
                                    $embed_doc[$key][$field_name] = $document->{$field_name};
                                }
                                $embed_doc[$key]["level"] = "$document_nid.L1";
                                $embed_doc[$key]["ss_node_type"] = "folder";
                                $embed_doc[$key]["id"] = "$document_hash-$document_index_id-$node_data_nid-$document_nid-$l";
                                $embed_doc[$key]["hash"] = $document_hash;
                                $embed_doc[$key]["index_id"] = $document_index_id;
                                $embed_doc[$key]["ss_item_id"] = $node_data_nid;
                                $embed_doc[$key]["is_nid"] = $node_data_nid;
                                $embed_doc[$key]["tf_title"] = $toc_data['heading'];
                                $embed_doc[$key]["is_embed"] = $is_embed;
                                $embed_doc[$key]["ss_embed_rel"] = 'embedrel';
                                $embed_doc[$key]["tf_report_title"] = $report_title;
                                $embed_doc[$key]["tf_field_topic_title"] = $document->tf_field_topic_title;
                                $embed_doc[$key]["is_field_topic_nid"] = $document->is_field_topic_nid;
                                $embed_doc[$key]["is_nid"] = $node_data_nid . $document_nid . $l;
                                $embed_doc[$key]["ss_search_api_id"] = "$node_data_nid-$document_nid-$l"; //maybe need to change this
                                $embed_slide_number_start = $toc_data['pageNo'];
                                $embed_slide_number_next = $node_data_embed_toc['toc'][$key + 1]['pageNo'];
                                if (empty($embed_slide_number_next)) {
                                    $embed_slide_number_next = $embed_slide_number_start;
                                }
                                $final_parsed_contents = $this->retrieve_slide_details($type, $embed_slide_number_start, $embed_slide_number_next, $filepath);
                                $start = 0;
                                foreach ($final_parsed_contents as $index => $final_parsed_content) {
                                    if ($index == 0) {
                                        $embed_doc[$key]["ss_url"] = $node_data['url_alias'];
                                        $embed_doc[$key]["is_field_embed_slide_no"] = $final_parsed_content['slidNumber'];
                                    }
                                    foreach ($document->getFields() as $field_name => $field_value) {
                                        $embed_doc[$key]["_childDocuments_"][$start][$field_name] = $document->{$field_name};
                                    }
                                    $embed_doc[$key]["_childDocuments_"][$start]["id"] = "$document_hash-$document_index_id-$node_data_nid-$document_nid-embed-$m";
                                    $embed_doc[$key]["_childDocuments_"][$start]["ss_embed_rel"] = 'embedrel';
                                    $embed_doc[$key]["_childDocuments_"][$start]["is_field_embed_cs_id"] = $node_data_nid;
                                    $embed_doc[$key]["_childDocuments_"][$start]["ss_node_type"] = "embed";
                                    $embed_doc[$key]["_childDocuments_"][$start]["hash"] = $document_hash;
                                    $embed_doc[$key]["_childDocuments_"][$start]["index"] = $document_index_id;
                                    $embed_doc[$key]["_childDocuments_"][$start]["tf_title"] = $final_parsed_content['slidName'];
                                    $embed_doc[$key]["_childDocuments_"][$start]["tf_body_field"] = $final_parsed_content['slidConttent'];
                                    $embed_doc[$key]["_childDocuments_"][$start]["is_field_embed_slide_no"] = $final_parsed_content['slidNumber'];
                                    $embed_doc[$key]["_childDocuments_"][$start]["is_landing_card"] = $this->checkisLandingCard($node_data_nid);
                                    $embed_doc[$key]["_childDocuments_"][$start]["ss_url"] = $node_data['url_alias'];
                                    $embed_doc[$key]["_childDocuments_"][$start]["level"] = "$document_nid.CS";
                                    $embed_doc[$key]["_childDocuments_"][$start]["tf_report_title"] = $document->tf_report_title;
                                    $embed_doc[$key]["_childDocuments_"][$start]["tf_field_topic_title"] = $document->tf_field_topic_title;
                                    $embed_doc[$key]["_childDocuments_"][$start]["is_field_topic_nid"] = $document->is_field_topic_nid;
                                    $embed_doc[$key]["_childDocuments_"][$start]["is_nid"] = $node_data_nid . $document_nid . $l . $m;
                                    $embed_doc[$key]["_childDocuments_"][$start]["ss_search_api_id"] = "$node_data_nid-$document_nid-$l-$m"; //maybe need to change this
                                    $start++;
                                    $m++;
                                }
                                $l++;
                            }
                        }
                    }
//    file_put_contents('search_embed_doc2.txt', print_r($embed_doc, TRUE), FILE_APPEND);
                    // Unlinking the file
                    $uri_for_dataset = file_create_url($cs_embed_filename_uri);

                    $uri_parts_array = explode('/', $uri_for_dataset);
                    $count_final = count($uri_parts_array);

                    $pptfileName = urldecode($uri_parts_array[$count_final - 1]);
                    //die($pptfileName);
                    if (isset($pptfileName) && !empty($pptfileName)) {
                        $document_root_path = $_SERVER['DOCUMENT_ROOT'];
                        $filepath_remove = "sites/default/files/feed_embed_files/embeds/$pptfileName";
                        $final_path = $document_root_path . '/' . $filepath_remove;
                        if (file_exists($final_path)) {
                            unlink($final_path);
                        }
                    }
                    //echo '<pre>';
                    //print_r($embed_doc);die;

                    return $embed_doc;
                }
            }
        }
    }

    /**
     * Forms the s3 url and filename.
     *
     * @param array $file
     * @return array
     */
    public function getS3UrlAndFilename($file) {
        $file_entity = File::load($file['target_id']);
        $filename = $file_entity->getFilename();
        $fileuri = $file_entity->getFileUri();
        $file_create_url = file_create_url($fileuri);
        //$url = $this->get_file_s3_bucket_url($filename);
        return array('url' => $file_create_url, 'filename' => $filename);
    }

    //embed functions :
    /**
     * Downloads the embed ppt from s3 into local for parsing by JAR application
     *
     * @param String $uri
     */
    function store_ppt_embed_files_in_local($uri) {
        //return "sites/default/files/feed_embed_files/embeds/test.pptx";

        $uri = file_create_url($uri);

        $uri_parts = explode('/', $uri);
        $count = count($uri_parts);

        $pptfileName = urldecode($uri_parts[$count - 1]);
        $s3fs_settings = \Drupal::config('s3fs.settings');
        $s3fs_region = $s3fs_settings->get('region');
        $s3fs_access_key = \Drupal\Core\Site\Settings::get('s3fs.access_key');
        $s3fs_access_secret = \Drupal\Core\Site\Settings::get('s3fs.secret_key');
        $credentials = new Credentials($s3fs_access_key, $s3fs_access_secret);
        $s3Client = new S3Client([
            'version' => 'latest',
            'region' => $s3fs_region,
            'credentials' => $credentials,
        ]);

        $bucketName = $s3fs_settings->get('bucket');
        $filepath = "sites/default/files/feed_embed_files/embeds/$pptfileName";
        $key = $pptfileName;
        try {

            // Get the object.
            $result = $s3Client->getObject([
                'Bucket' => $bucketName,
                'Key' => $key,
                'SaveAs' => $filepath
            ]);

            $embed_file = \Drupal::service('file_system')->realpath($filepath);
            return $embed_file;
        } catch (S3Exception $e) {
            \Drupal::logger('Error While indexing ppt embded')->error($e->getMessage());
            return false;
            // echo $e->getMessage() . PHP_EOL;
        }
    }

    /**
     * Returns the type of file for parsing.
     *
     * @param type $extension
     *    Indicates the extension.
     * @return string
     *    Returns the file type.
     */
    function get_embed_file_type_for_parsing($extension) {
        if (empty($extension)) {
            return;
        } else {
            if ($extension == 'pdf') {
                $type = 'pdf';
            } else if ($extension == 'ppt' || $extension == 'pptx') {
                $type = 'ppt';
            }
        }
        return $type;
    }

    /**
     * Returns the slide details.
     *
     * @param type $type
     *    Indicates the type.
     * @param type $embed_first_slide
     *    Indicates the first slide.
     * @param type $filepath
     *    Indicates the file-path.
     * @return type
     *    Returns the slide details.
     */
    function retrieve_slide_details($type, $embed_first_slide, $embed_second_slide, $filepath) {

//      print_r($type); 
//      print_r($embed_first_slide); 
//      print_r($embed_second_slide); 
//      print_r($filepath);
        //   $embed_directory_path = '/Applications/MAMP/htdocs/insight_platform_d8/web/modules/custom/insight_search/solr_jar'; //change this to some path
        //$embed_directory_path = DRUPAL_ROOT.'/modules/common/insights-platform-generic-module/insight_search/solr_jar';
        $embed_directory_path = DRUPAL_ROOT . '/' . self::INSIGHT_SEARCH_MODULE_PATH;

        // print_r($embed_directory_path);die;
        //echo "<br>";

        $embed_parser_name = 'PPTPDF.jar';
        $ppt_pdf_jar_location = $embed_directory_path . '/' . $embed_parser_name;

        // print_r($ppt_pdf_jar_location);die;
        if ($type == 'ppt') {
            $parser_command = 'java -jar ' . $ppt_pdf_jar_location . ' ExtractTextPPTPDf "' . $type . '" "' . $embed_first_slide . '-' . $embed_second_slide . '" "' . $filepath . '"';
            $parsed_details = shell_exec($parser_command);
            $decoded_parsed_details = json_decode($parsed_details, TRUE);
            $final_details = $decoded_parsed_details['sliddetails'];
        }
        //echo '<pre>';
        //print_r($final_details);die;
        return $final_details;
    }

    /**
     * Checks if node is landing card.
     *
     * @param Array $node_json_object
     *    Indicates the array of node from JSON.
     * @return boolean
     *    Returns boolean true if it is a landing card else returns false.
     */
    public function checkisLandingCard($node_json_object) {
        if (isset($node_json_object['container_type'])) {
            if ($node_json_object['container_type'] == 'landing_card') {
                return 1;
            }
        } else {
            return 0;
        }
    }

    /**
     * Converting TOC string in desired format
     * @param type $toc_string
     */
    function convertTOCStringInDesiredFormat($toc_string) {
        try {
            return array_values(array_filter(explode(PHP_EOL, $toc_string)));
        } catch (Exception $e) {
            \Drupal::logger('convertTOCStringInDesiredFormat')->error($e->getMessage());
        }
        return;
    }

    /**
     * Getting TOC string in Array format
     * @param array $toc_string_array
     * @return array
     */
    function sendTOCStringGetArrayFormat($toc_string_array) {
        $newArray = array();
        if (count($toc_string_array) > 0) {
            foreach ($toc_string_array as $chunks_toc) {
                $toc_data = explode('|', $chunks_toc);
                $newArray[] = array(
                    'title' => trim($toc_data[0]),
                    'pageNo' => trim($toc_data[1]),
                    'index' => trim($toc_data[2]),
                );
            }
            $finalArray = $this->insightSortArray($newArray, 'index');
        }
        return $finalArray;
    }

    /**
     * Sorting an array
     * @param array $data
     * @param string $field
     * @return array
     */
    function insightSortArray($data, $field) {
        $field = (array) $field;
        uasort($data, function($a, $b) use($field) {
            $retval = 0;
            foreach ($field as $fieldname) {
                if ($retval == 0)
                    $retval = strnatcmp($a[$fieldname], $b[$fieldname]);
            }
            return $retval;
        });
        return $data;
    }

}
