<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\insight_search\Controller\SearchController;

/**
 * Description of MedtechSearchController
 */
class MedtechSearchController extends ControllerBase {

    private $pageLimit = 10;
    public $enableSpellCheck = TRUE;
    public $enableHighlight = TRUE;

    const BUSNIESS_UNIT = 'Medtech';

    /**
     * Method responsible for medtech search
     * @return array
     */
    public function search() {
        
        $request = \Drupal::request();
        if (\Drupal::request()->isXmlHttpRequest()) {
            $response = $this->sendRequestToReportGen($request);
            return $response['build'];
        }
        
        // Generating search log
        $queryParams = \Drupal::request()->query->get('query');
        \Drupal::service('insights_service.user')->insertLastSearchitem($queryParams);

        $response = $this->sendRequestToReportGen($request);
        $resultset = $response['resultset'];
        $sortFieldsMap = $response['sortFieldsMap'];
        $content->items = $response['build'];
        $content->count = $resultset->getNumFound();
        $content->sort = $response['sort'];
        $content->query = $response['qStr'];
        $content->tabs = $response['tabs'];
        $content->suggestions = $response['suggestions'];
        $content->ownership = $request->get('ownership', 'all');
        $content->pubdate = $request->get('pubdate', 'any');
        $content->research_type = $request->get('research_type', 'all');

        $content->sortOptions = array_map(function($item) {
            return $item[2];
        }, $sortFieldsMap);

        if ($request->get('page', null)) {
            $content->paged = true;
        }
        return [
            '#theme' => 'medtech_search',
            '#content' => $content,
            '#attached' => [
                'drupalSettings' => ['totalItems' => $resultset->getNumFound()],
                'library' => ['insight_search/insight_medtech_search'],
            ],
        ];
    }

    /**
     * Send request to get the search result
     * @param type $request
     * @return array
     * @throws BadRequestHttpException
     */
    public function sendRequestToReportGen($request) {

        $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');
        $config = array(
            'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
            'endpoint' => array(
                'localhost' => $settings
            )
        );

        $qStr = $request->get('query');
        $ownership = $request->get('ownership');
        $product_type = $request->get('product_type');
        $research_type = $request->get('research_type');
        $pubdate = $request->get('pubdate');
        $sort = $request->get('sort', 'score');

        $filters = array(
            'ownership' => $ownership,
            'product_type' => $product_type,
            'research_type' => $research_type,
            'pubdate' => $pubdate,
        );

        $sortFieldsMap = [
            'title' => [
                'ss_sort_title',
                'asc',
                'Title'
            ],
            'date' => [
                'ds_field_report_publication_date',
                'desc',
                'Publication Date'
            ]
        ];

        $querySpellCheck = true;
        if ($request->query->has('spellcheck')) {
            $querySpellCheck = (bool) $request->get('spellcheck');
        }

        $autoSuggest = false;
        if ($request->query->has('origin')) {
            $autoSuggest = $request->get('origin');
        }

        $client = new \Solarium\Client($config);

        $content = new \stdClass();
        $content->paged = false;
        $query = $client->createSelect();
        // Code started for report section 
        if ($qStr) {
            $keyword = $qStr;
            $quotes = preg_match('/^(["\']).*\1$/m', $keyword);
            $mm_parameter = '';
            if ($quotes == 1) {
                //$keyword = trim($keyword, '"');
                $mm_parameter = 'mm=100';
            } else {
                $keyword = '"' . trim($keyword) . '"';
            }

            
            
            // Making Ids dynamic
            $is_field_solution_group = $this->getDesiredTidBasedOnCategoryType('business_units','MedTech');
            $p_is_field_report_research_type = $this->getDesiredTidBasedOnCategoryType('report_research_type','Proprietary Research');
            $s_is_field_report_research_type = $this->getDesiredTidBasedOnCategoryType('report_research_type','Syndicated');
           
            
            $marketqf = "tf_field_topic_title^10.0 tf_report_title^2.0 tf_title^1.0 tf_body_field^1.0 tf_field_file_attachment^0.1 tf_field_library_trpy_ara_dseries_parent_names tf_field_library_trpy_ara_dseries_names";
            $reportqf = "tf_field_topic_title tf_report_title tf_title^1.0 tf_body_field^0.2 tf_field_file_attachment^0.2 tf_field_library_trpy_ara_dseries_parent_names tf_field_library_trpy_ara_dseries_names";
            $csginsight = '{!edismax v="${qq}" qf="' . $marketqf . '"}';
            $report = '{!edismax v="${qq}" qf="' . $reportqf . '"}';
            $biopharma_relevance = '{!edismax v="${qq}" qf="topic_feature_result^3.0 topic_feature_result_replace^3.0" sow= false}';
            $embed_relevance = '((ss_embed_rel:embedrel) AND {!edismax v="${qq}" qf="tf_title^2.0 tf_body_field^3.0"})';
            $query_string_medtech = '{!parent which="(level:*.REPORT)" score=max} ';
            $medtech_marketqf = "tf_field_topic_title tf_report_title tf_title";
            $medtech_marketothers = '{!edismax v=${qr} qf="topic_feature_result topic_feature_result_replace tf_body_field tf_field_file_attachment tf_field_library_trpy_ara_dseries_parent_names tf_field_library_trpy_ara_dseries_names" ' . $mm_parameter . '}';
            $medtech_csginsight = '{!edismax v=${qq} qf="' . $medtech_marketqf . '" pf="' . $medtech_marketqf . '" mm=100} OR ' . $medtech_marketothers;
            $medtech_reportqf = "tf_report_title tf_title";
            $medtech_reportothers = '{!edismax v=${qr} qf="tf_report_title tf_title tf_body_field tf_field_file_attachment tf_field_library_trpy_ara_dseries_parent_names tf_field_library_trpy_ara_dseries_names" ' . $mm_parameter . '}';
            $medtech_report = '{!edismax v=${qq} qf="' . $medtech_reportqf . '" pf="' . $medtech_reportqf . '" mm=100} OR ' . $medtech_reportothers;
            $medtech_tableauqf = 'tf_report_title tf_field_chapter_title';
            $medtech_tableauothers = '{!edismax v=${qr} qf="topic_feature_result topic_feature_result_replace TherapyArea Tableau_Dashboards tf_body_field DeviceSeries Module Segment Manufacturer Brand Model im_hospital_id hospital_name city county state im_zip" ' . $mm_parameter . '}';
            $medtech_tableau = '{!edismax v=${qq} qf="' . $medtech_tableauqf . '" pf="' . $medtech_tableauqf . '" mm=100} OR ' . $medtech_tableauothers;
            $market_insight = '(((level:*.CS AND is_landing_card:0) OR level:*.L2 OR level:*.L1 OR level:*.CSG) AND (' . $medtech_csginsight . ')) OR (((level:*.RL AND is_field_solution_group:' . $is_field_solution_group . ' AND (is_field_report_research_type:'.$s_is_field_report_research_type.' OR ( is_field_report_research_type:'.$p_is_field_report_research_type.' AND ${sku} ))) AND	(' . $medtech_report . ')))';
            $commercial_targeting = '((( level:*.CT) AND (' . $medtech_tableau . ') AND (${sku})))';
            $brand_tracking = '((( level:*.BT) AND (' . $medtech_tableau . ') AND (${sku})))';
            if (!isset($filters['product_type'])) {
                $query_string_medtech .= $market_insight . ' OR ' . $commercial_targeting . ' OR ' . $brand_tracking;
            } else {
                foreach ($filters['product_type'] as $productType => $pType) {
                    $queryCondition = ($productType == 0) ? ' ' : ' OR ';
                    $query_string_medtech .= $queryCondition . $$pType;
                }
            }

            $SearchController = new SearchController();
            $UserEntitlements =  $SearchController->getUserEntitlements(self::BUSNIESS_UNIT);
            $query->setQuery($query_string_medtech);
            $query->addParam('qq', $keyword);
            $query->addParam('qr', $keyword);
            $query->addParam('f.im_field_deliverable_type.facet.limit', '50');
            $query->addParam('f.im_field_therapy_area_disease.facet.limit', '50');
            $query->addParam('f.im_field_geography.facet.limit', '50');
            $query->addParam('f.im_field_destination_category.facet.limit', '50');
            $query->addParam('f.im_field_legacy_category.facet.limit', '50');
            $query->addParam('f.is_field_report_research_type.facet.limit', '50');
            $query->addParam('hl', 'on');
            $query->addParam('hl.fl', 'tf_title,tf_report_title');
            $query->addParam('hl.simple.pre', '<b>');
            $query->addParam('hl.simple.post', '</b>');
            $query->addParam('hl.boundaryScanner', 'breakIterator');
            $query->addParam('f.tf_body_field.hl.snippets', '1');
            $query->addParam('f.tf_body_field.hl.fragsize', '400');
            $query->addParam('sku', 'sm_field_sku:(' . $UserEntitlements . ')');
        }

        $query->setFields(
                array(
                    'id',
                    'nid:is_nid',
                    'description:tf_body_field',
                    'tf_field_3rd_party_metadata_intoduc',
                    'ss_type',
                    'url:ss_url',
                    'publish_date:ds_field_publish_date',
                    'score',
                    'report_type:ss_report_type',
                    'topic_title:tf_field_topic_title',
                    'chapter_title:tf_field_chapter_title',
                    'csg_title:tf_field_csg_title',
                    'level',
                    'ss_plaform_url',
                    'node_type:ss_node_type',
                    'sku:sm_field_sku',
                    'hasAccess:if(exists(query({!v="${sku}"})),1,0)',
                    'title:tf_report_title',
                    'file_attachments_name:sm_field_file_attachment_name',
                    'file_attachments_url:sm_field_file_attachment_url',
                    'file_attachments_mime_type:sm_field_file_attachment_mime',
                    'ss_field_disable_platform_related_d',
                    'Tableau_Dashboards',
                    'Client',
                    'Customer_dashboard_link',
                    'is_tableau'
                )
        );


        // Handling Filter String 
        $filterString = $this->setFieldQuery($filters, $is_field_solution_group, $p_is_field_report_research_type, $s_is_field_report_research_type);
        if (!empty($filterString)) {
            $query->addParam('fq', $filterString);
            // $query->createFilterQuery('filters')->setQuery(implode($filterString, ' AND '));
        }

        if ($page = $request->get('page', null)) {
            $query->setStart($page * $this->pageLimit)->setRows($this->pageLimit);
        } else {
            $query->setStart(0)->setRows($this->pageLimit);
        }

        $sort_filter = $request->get('sort', 'score');

        if (isset($sort_filter) && !empty($sort_filter)) {
            switch ($sort_filter) {
                case 'date':
                    $sort_option = 'ds_field_publish_date';
                    break;

                case 'score':
                    $sort_option = 'score';
                    break;
            }
        }

        //Add sort 
        if ($sort) {
            // Default should be desc
            $query->addSort($sort_option, 'desc');
        }
        $facetSet = $query->getFacetSet();
        //set spellcheck query
        if ($querySpellCheck) {
            // add spellcheck settings
            preg_match('/"([^"]+)"/', $qStr, $matches);
            $spellcheck_query = (!empty($matches) && count($matches) > 0) ? str_replace('"', '', $qStr) : $qStr;
            $spellcheck = $query->getSpellcheck();
            $spellcheck->setQuery($spellcheck_query);
            $spellcheck->setCount(50);
            $spellcheck->setAccuracy(0.7);
            $spellcheck->setMaxCollationTries(1);
            $spellcheck->setOnlyMorePopular(false);
            $spellcheck->setExtendedResults(true);
            $spellcheck->setCollateExtendedResults(true);
            $spellcheck->setDictionary('default');
        }

        try {
            $resultset = $client->select($query);
        } catch (\Solarium\Exception\HttpException $e) {
            $ping = $client->createPing();
            try {
                $result = $client->ping($ping);
            } catch (\Solarium\Exception\HttpException $xx) {
                throw new BadRequestHttpException('Unable to connect to solr');
            }
        }

        $template = 'medtech_search_item';
        $hl = $query->getHighlighting();
        $highlighting = $resultset->getHighlighting();
        $build = [];
        if ($resultset->getNumFound() > 0) {
            foreach ($resultset->getDocuments() as $document) {
                $render_title = '';
                $render_desc = '';
                $highlightedDoc = $highlighting->getResult($document->id);
                $internal_data = array();
                if ($highlightedDoc) {
                    foreach ($highlightedDoc as $field => $highlight) {
                        if ($document->ss_type == 'content_library') {
                            if ($field == 'tf_field_3rd_party_metadata_intoduc') {
                                $render_desc = $highlight[0];
                            }
                        } else {
                            if ($field == 'tf_body_field') {
                                $render_desc = $highlight[0];
                            }
                        }
                    }

                    foreach ($highlightedDoc as $field => $highlight) {
                        if ($field == 'tf_report_title') {
                            $render_title = $highlight[0];
                        }
                    }
                }
                // die($render_title);
                if (isset($render_desc) && !empty($render_desc)) {
                    $internal_data['description'] = $render_desc;
                } else {
                    //if ($document->ss_type == 'content_library') {
                        //dump($document);
                     //   $internal_data['description'] = $this->actionSoftTrim($document->tf_field_3rd_party_metadata_intoduc[0], 400);
                   // } else {
                     //   $internal_data['description'] = $this->actionSoftTrim($document->description[0], 400);
                   // }
                  $internal_data['description'] = $this->actionSoftTrim($document->description[0], 400);

                }

                if (isset($render_title) && !empty($render_title)) {
                    $internal_data['title'] = $render_title;
                } else {
                    $internal_data['title'] = $document->title;
                }

                //Adding facet info to build.
                $build[] = [
                    '#cache' => array('Cache-Control' => 'no-cache'),
                    '#theme' => $template,
                    '#content' => $document,
                    '#highliteddocdata' => $internal_data,
                    '#tabs' => $tabs,
                    '#ownership' => $request->get('ownership', 'all'),
                    '#pubdate' => $request->get('pubdate', 'any'),
                    '#research_type' => $request->get('research_type', 'all'),
                    '#product_type' => $request->get('product_type', 'all'),
                ];
            }
        } else {
            $build[] = [
                '#cache' => array('max-age' => 0),
                '#theme' => 'medtech_search_no_result',
                '#content' => array('query' => $qStr),
                '#tabs' => $tabs,
                '#ownership' => $request->get('ownership', 'all'),
                '#pubdate' => $request->get('pubdate', 'any'),
                '#research_type' => $request->get('research_type', 'all'),
                '#product_type' => $request->get('product_type', 'all'),
            ];
        }

        // spellcheck result
        $auto_redirect = FALSE;
        $spellcheckResult = $resultset->getSpellcheck();
        if (!is_null($spellcheckResult)) {
            $spellcheckSuggestions = (bool) count($spellcheckResult->getSuggestions());
            if ($spellcheckSuggestions) {
                $spellcheckData = $qStr;
                foreach ($spellcheckResult->getSuggestions() as $suggestion) {
                    preg_match_all('/<b>(.*?)<\/b>/s', $suggestion->getWords()[0]['word'], $matches);
                    if (isset($matches) && empty($matches[1][0])) {
                        $spellcheckData = str_replace($suggestion->getOriginalTerm(), $suggestion->getWords()[0]['word'], strtolower($spellcheckData));
                        $auto_redirect = TRUE;
                    }
                }
            }
        }

        // auto redirect to suggest keyword
        if ($auto_redirect) {
            $redirect = new RedirectResponse('/search?query=' . $spellcheckData . '&origin=' . $qStr);
            $redirect->send();
        }

        $ret_response['build'] = $build;
        $ret_response['resultset'] = $resultset;
        $ret_response['sort'] = $sort;
        $ret_response['qStr'] = $qStr;
        $ret_response['query'] = $qStr;
        $ret_response['tabs'] = $tabs;
        $ret_response['suggestions'] = ($autoSuggest) ? $autoSuggest : false;

        if ($sort == 'date') {
            $sorttypelabel = 'Publication Date';
        }
        if ($sort == 'score') {
            $sorttypelabel = 'Relevance';
        }
        $ret_response['build']['#attached'] = [
            'drupalSettings' => ['totalItems' => $resultset->getNumFound(), 'sorttypelabel' => $sorttypelabel, 'sort_type' => $sort],
            'library' => ['insight_search/insight_medtech_search'],
        ];
        $ret_response['sortFieldsMap'] = $sortFieldsMap;
        return $ret_response;
    }

    /**
     * Get Publish Date
     * @param string $value
     * @return string
     */
    public function getPublisDateRange($value = NULL) {
        switch ($value) {
            case '6months':
                $date_range = 'ds_field_publish_date:[NOW-6MONTH TO NOW]';
                break;
            case '1year':
                $date_range = 'ds_field_publish_date:[NOW-1YEAR/YEAR TO NOW]';
                break;
            case '2years':
                $date_range = 'ds_field_publish_date:[NOW-2YEAR/YEAR TO NOW]';
                break;
            default:
                $date_range = 'ds_field_publish_date:[* TO *]';
                break;
        }
        return $date_range;
    }

    /**
     * Search more result
     */
    public function searchMoreResult() {
        $request = \Drupal::request();
        $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');
        $config = array(
            'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
            'endpoint' => array(
                'localhost' => $settings
            )
        );

        $qStr = $request->get('query');
        $rid = $request->get('rid');
        $type = $request->get('type');
        $sort = $request->get('sort', 'date');
        $sortFieldsMap = [
            'title' => [
                'ss_sort_title',
                'asc',
                'Title'
            ],
            'date' => [
                'ds_field_report_publication_date',
                'desc',
                'Publication Date'
            ]
        ];
        $client = new \Solarium\Client($config);
        $content = new \stdClass();
        $content->paged = false;
        $query = $client->createSelect();
        if ($qStr) {
            $query->setQuery('*');
            $fq = '(level: ' . $rid . '.L1 OR level: ' . $rid . '.CSG)';
            $query->addParam('fq', $fq);
            $edismax = $query->getEDisMax();
            $edismax->setQueryFields('tf_field_topic_title^0.3 tf_report_title^1.0 tf_title^4.0 tf_body_field^0.2 tf_field_file_attachment^0.3');
        }

        preg_match('/"([^"]+)"/', $qStr, $matches);
        $search_words = (!empty($matches) && count($matches) > 0) ? str_replace('"', '', $qStr) : $qStr;
        $search_words = explode(' ', $search_words);
        foreach ($search_words as $word) {
            $term_frequency .= "termfreq(tf_field_file_attachment,$word),";
        }
        $report_search = "(level:" . $rid . ".L1 OR level:" . $rid . ".CSG)";
        
        $query->setFields(
                array(
                    'id',
                    'nid:is_nid',
                    'description:tf_body_field',
                    'tf_field_3rd_party_metadata_intoduc',
                    'ss_type',
                    'url:ss_url',
                    'publish_date:ds_field_publish_date',
                    'score',
                    'report_type:ss_report_type',
                    'topic_title:tf_field_topic_title',
                    'chapter_title:tf_field_chapter_title',
                    'csg_title:tf_field_csg_title',
                    'is_embed',
                    'is_field_embed_slide_no',
                    'level',
                    'ss_plaform_url',
                    'hasAccess:if(exists(query({!v="${sku}"})),1,0)',
                    'node_type:ss_node_type',
                    'sku:sm_field_sku',
                    'ss_node_type',
                    'match_count:sum(' . rtrim($term_frequency, ',') . ')',
                    'level',
                    'nid:item_id',
                    'title:tf_title',
                    'file_attachments_name:sm_field_file_attachment_name',
                    'file_attachments_url:sm_field_file_attachment_url',
                    'file_attachments_mime_type:sm_field_file_attachment_mime',
                    'title:tf_title',
                    '[child parentFilter="' . $report_search . '" childFilter="((level:' . $rid . '.L2 OR (level:' . $rid . '.CS AND is_landing_card:0)) AND ((tf_title:(' . addslashes($qStr) . ')) OR (tf_body_field:(' . addslashes($qStr) . '))))" limit=1000 childFieldList="is_nid,tf_title,nid,ss_node_type,ss_url"]',
                )
        );
        
        $SearchController = new SearchController();
        $UserEntitlements = $SearchController->getUserEntitlements(self::BUSNIESS_UNIT);
            
        $query->addParam('sku', 'sm_field_sku:(' . $UserEntitlements . ')');



        $facetSet = $query->getFacetSet();
        try {
            $resultset = $client->select($query);
        } catch (\Solarium\Exception\HttpException $e) {
            $ping = $client->createPing();
            try {
                $result = $client->ping($ping);
            } catch (\Solarium\Exception\HttpException $xx) {
                throw new BadRequestHttpException('Unable to connect to solr');
            }
        }
        
       // dump($resultset->getDocuments());
        $build[] = [
            '#cache' => array('max-age' => 0),
            '#theme' => 'insight_search_more_result',
            '#content' => $resultset->getDocuments()
        ];
        print render($build);
        exit;
    }

    /**
     * Getting soft trim string
     * @param test $text
     * @param int $count
     * @param string $wrapText
     * @return string
     */
    function actionSoftTrim($text, $count, $wrapText = '...') {
        if (strlen($text) > $count) {
            preg_match('/^.{0,' . $count . '}(?:.*?)\b/siu', $text, $matches);
            $text = $matches[0];
        } else {
            $wrapText = '';
        }
        return $text . $wrapText;
    }

    /**
     * Setting field query for filter 
     * @param type $filters
     * @param type $rl_medtech
     * @return string
     */
    function setFieldQuery($filters, $is_field_solution_group, $p_is_field_report_research_type, $s_is_field_report_research_type) {
        $common_Query = '(ss_report_type:report-library AND is_field_solution_group:' . $is_field_solution_group . ')';
        if (strtolower($filters['research_type']) == 'proprietary research') {
            $fq[] = $common_Query . ' AND is_field_report_research_type:'.$p_is_field_report_research_type;
        } elseif (strtolower($filters['research_type']) == 'syndicated') {
            $fq[] = '(ss_report_type:medtech OR (' . $common_Query . ' AND is_field_report_research_type:'.$s_is_field_report_research_type.' ))';
        } else {
            $fq[] = '(ss_report_type:medtech OR ' . $common_Query . ')';
        }

        if (isset($filters['pubdate']) && !empty($filters['pubdate'])) {
            $fq[] = $this->getPublisDateRange($filters['pubdate']);
        }
        // Set Filter query for onwnership.
        if (isset($filters['ownership']) && !empty($filters['ownership'])) {
            if ($filters['ownership'] == 'owned') {
                $fq[] = '(${sku})';
            } elseif ($filters['ownership'] == 'un-owned') {
                $fq[] = '-${sku} OR !(ss_field_product_type_value:"OVERVIEW")';
            } elseif ($filters['ownership'] == 'information') {
                $fq[] = 'ss_field_product_type_value:"OVERVIEW"';
            }
        }
        return $fq;
    }

    /**
     * Set spell-check configuration.
     * @param object $query
     * @param string $keyword
     * @return object
     */
    function setSpellCheck($query, $keyword) {
        preg_match('/"([^"]+)"/', $keyword, $matches);
        $spellcheck_query = (!empty($matches) && count($matches) > 0) ? str_replace('"', '', $keyword) : $keyword;
        $query->addParam('spellcheck.q', $spellcheck_query);
        $spellcheck = $query->getSpellcheck();
        $spellcheck->setQuery($spellcheck_query);
        $spellcheck->setCount(50);
        $spellcheck->setAccuracy(0.7);
        $spellcheck->setMaxCollationTries(1);
        $spellcheck->setOnlyMorePopular(false);
        $spellcheck->setExtendedResults(true);
        $spellcheck->setCollateExtendedResults(true);
        $spellcheck->setDictionary('default');
        return $query;
    }

    /**
     * Get desired Tid based on Category Type
     * @param int $vid
     * @param string $name
     * @return string
     */
    function getDesiredTidBasedOnCategoryType($vid, $name) {
        $query = \Drupal::database()->select('taxonomy_term_field_data', 't');
        $query->fields('t', ['tid']);
        $query->condition('t.vid', $vid);
        $query->condition('t.name', $name);
        $query->range(0, 1);
        $query->execute();
        $result_set = $query->execute()->fetchObject();
        return isset($result_set->tid) ? $result_set->tid : null;
    }

}
