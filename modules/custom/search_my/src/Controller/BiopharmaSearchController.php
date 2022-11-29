<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use Drupal\search_api\Entity\Index;
use Drupal\insight_search\Controller\SearchController;

/**
 * BiopharmaSearchController responsible to handle all the biopharma search
 */
class BiopharmaSearchController extends ControllerBase {

    private $pageLimit = 10;

    const BUSNIESS_UNIT = 'BioPharma';

    /**
     * Method responsible for biopharma search
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
            '#theme' => 'biopharma_search',
            '#content' => $content,
            '#attached' => [
                'drupalSettings' => ['totalItems' => $resultset->getNumFound()],
                'library' => ['insight_search/insight_biopharma_search'],
            ],
        ];
    }

    /**
     * sendRequestToReportGen
     * @param object $request
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
        $research_type = $request->get('research_type');
        $pubdate = $request->get('pubdate');
        $tabs = $request->get('tabs', 'container');
        $sort = $request->get('sort', 'score');

        $filters = array(
            'ownership' => $ownership,
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


        $exactmatch = '';
        $keyword = $qStr;
        $quotes = preg_match('/^(["\']).*\1$/m', $keyword);
        $mm_parameter = '';
        if ($quotes == 1) {
            $keyword = trim($keyword, '"');
            $search_words = preg_split('/\s+/', $keyword);
            if (count($search_words) > 1) {
                $keyword = preg_replace('/\s+/', ' AND ', $keyword);
            }
        }

        // Making Ids dynamic
        $is_field_solution_group = $this->getDesiredTidBasedOnCategoryType('business_units', 'Biopharma');
        $p_is_field_report_research_type = $this->getDesiredTidBasedOnCategoryType('report_research_type', 'Proprietary Research');
        $s_is_field_report_research_type = $this->getDesiredTidBasedOnCategoryType('report_research_type', 'Syndicated');
        $SearchController = new SearchController();
        $UserEntitlements =  $SearchController->getUserEntitlements(self::BUSNIESS_UNIT);
            //  var_dump($UserEntitlements);die;      
        switch ($tabs) {
            // Code started for report section 
            case 'container':
                if ($keyword) {
                    $marketqf = "tf_field_topic_title^10.0 tf_report_title^2.0 tf_title^1.0 tf_body_field^1.0 tf_field_file_attachment^0.1 tf_field_library_trpy_ara_dseries_parent_names tf_field_library_trpy_ara_dseries_names";
                    $reportqf = "tf_field_topic_title tf_report_title tf_title^1.0 tf_body_field^0.2 tf_field_file_attachment^0.2 tf_field_library_trpy_ara_dseries_parent_names tf_field_library_trpy_ara_dseries_names";
                    $csginsight = '{!edismax v="${qq}" qf="' . $marketqf . '"}';
                    $report = '{!edismax v="${qq}" qf="' . $reportqf . '"}';
                    $biopharma_relevance = '{!edismax v="${qq}" qf="topic_feature_result^3.0 topic_feature_result_replace^3.0" sow= false}';
                    $embed_relevance = '((ss_embed_rel:embedrel) AND {!edismax v="${qq}" qf="tf_title^2.0 tf_body_field^3.0"})';
                    $query_string_biopharma = '{!parent which="(level:*.REPORT)" score=max} ';
                    $csg = '((ss_report_type:biopharma OR ss_report_type:c-d) AND ((${csginsight} OR ${biopharma_relevance} OR ${embed_relevance}) AND ((level:*.CS AND is_landing_card:0) OR (level:*.L2 OR level:*.L1 OR level:*.CSG))))';
                    $commonQuery = '((${reportinsight}) AND ((level:*.RL AND (is_field_solution_group:' . $is_field_solution_group . ' AND ';
                    $syndicated = $commonQuery . '(is_field_report_research_type:' . $s_is_field_report_research_type . ')))))';
                    $proprietary = $commonQuery . '(is_field_report_research_type:' . $p_is_field_report_research_type . ' AND (${sku}))))))';
                    if (strtolower($filters['research_type']) == 'all') {
                        $query_string_biopharma .= $csg . ' OR ' . $syndicated . ' OR ' . $proprietary;
                    } else if (strtolower($filters['research_type']) == 'proprietary research') {
                        $query_string_biopharma .= $proprietary;
                    } elseif (strtolower($filters['research_type']) == 'syndicated') {
                        $query_string_biopharma .= $csg . ' OR ' . $syndicated;
                    } else {
                        $query_string_biopharma .= $csg . ' OR ' . $syndicated . ' OR ' . $proprietary;
                    }
//die($query_string_biopharma);
                    $query->setQuery($query_string_biopharma);
                    $query->addParam('qq', $keyword);
                    $query->addParam('qr', $keyword);
                    $query->addParam('hl', 'on');
                    $query->addParam('hl.fl', 'tf_title,tf_report_title');
                    $query->addParam('hl.simple.pre', '<b>');
                    $query->addParam('hl.simple.post', '</b>');
                    $query->addParam('hl.boundaryScanner', 'breakIterator');
                    $query->addParam('f.tf_body_field.hl.snippets', '1');
                    $query->addParam('f.tf_body_field.hl.fragsize', '400');
                    $query->addParam('csginsight', '{!edismax v="${qq}" qf="' . $marketqf . '"' . $exactmatch . '}');
                    $query->addParam('reportinsight', '{!edismax v="${qq}" qf="' . $reportqf . '"' . $exactmatch . '}');
                    $query->addParam('biopharma_relevance', $biopharma_relevance);
                    $query->addParam('embed_relevance', $embed_relevance);
                    $query->addParam('sku', 'sm_field_sku:(' . $UserEntitlements . ')');
                    $edismax = $query->getEDisMax();
                    $edismax->setQueryFields('tf_field_topic_title^0.3 tf_report_title^1.0 tf_title^4.0 tf_body_field^0.2 tf_field_file_attachment^0.3');
                    $edismax->setBoostQuery('ss_report_type:(biopharma OR c-d)^1');
                    $edismax->setBoostFunctions('ord(ss_embed_rel)^20');
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
//                            'csg_title:tf_field_csg_title',
                            'level',
                            'ss_plaform_url',
                            'node_type:ss_node_type',
                            'sku:sm_field_sku',
                            'hasAccess:if(exists(query({!v="${sku}"})),1,0)',
                            'title:tf_report_title',
                            'file_attachments_name:sm_field_file_attachment_name',
                            'file_attachments_url:sm_field_file_attachment_url',
                            'file_attachments_mime_type:sm_field_file_attachment_mime',
//                            'ss_field_disable_platform_related_d',
//                            'is_field_chapter_nid',
                            'ss_field_product_type_value',
                            'is_overview:bs_is_overview',
                            'sm_field_file_attachment_url'
                        )
                );
                break;

            case 'chart':
            case 'table':
            case 'text':
                if ($keyword) {
                    $query->setQuery($keyword);
                    $query->addParam('hl', 'on');
                    $query->addParam('hl.fl', 'tf_title,tf_report_title');
                    $query->addParam('hl.simple.pre', '<b>');
                    $query->addParam('hl.simple.post', '</b>');
                    $query->addParam('hl.boundaryScanner', 'breakIterator');
                    $query->addParam('f.tf_body_field.hl.snippets', '1');
                    $query->addParam('f.tf_body_field.hl.fragsize', '400');
                    $query->addParam('sku', 'sm_field_sku:(' . $UserEntitlements . ')');
                    // Edismax      
                    $edismax = $query->getEDisMax();
                    $edismax->setQueryFields('tf_field_topic_title^0.3 tf_report_title^1.0 tf_title^4.0 tf_body_field^0.2 tf_field_file_attachment^0.3');
                    $edismax->setBoostQuery('tf_title:Bibliography^-10');
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
                            'is_embed',
                            'is_field_embed_slide_no',
                            'level',
                            'ss_plaform_url',
                            'node_type:ss_node_type',
                            'sku:sm_field_sku',
                            'hasAccess:if(exists(query({!v="${sku}"})),1,0)',
                            'ss_field_product_type_value',
                            'title:tf_title',
                            'sm_field_breadcrumb_titles',
                            'sm_field_breadcrumb_links',
                            'ss_image_link',
                            'node_type:ss_node_type',
                            'is_overview:bs_is_overview'
                        )
                );
                break;
        }

        $filterString = $this->setFieldQuery($filters, $tabs, $p_is_field_report_research_type, $s_is_field_report_research_type);
        if (!empty($filterString)) {
           // print_r($filterString);die;
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
       //$facetSet = $query->getFacetSet();

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
            $spellcheck->setDictionary(['default', 'file']);
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

        $template = $this->getDesiredTemplateNameByType($tabs);
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
                        if ($field == 'tf_body_field') {
                            $render_desc = $highlight[0];
                        }

                        if (in_array($tabs, array('chart', 'text', 'table'))) {
                            if ($field == 'tf_title') {
                                $render_title = $highlight[0];
                                continue;
                            }
                        } else if ($tabs == 'container') {
                            if ($field == 'tf_report_title') {
                                $render_title = $highlight[0];
                            }
                        }
                    }
                }
                if (isset($render_desc) && !empty($render_desc)) {
                    $internal_data['description'] = $render_desc;
                } else {
                    $internal_data['description'] = $this->actionSoftTrim($document->description[0], 400);
                }

                $internal_data['title'] = $render_title;
                //Adding facet info to build.
                $build[] = [
                    '#cache' => array('Cache-Control' => 'no-cache'),
                    '#theme' => $template,
                    '#content' => $document,
                    '#highliteddocdata' => $internal_data,
                    '#tabs' => $tabs,
                    '#ownership' => $request->get('ownership', 'all'),
                    '#pubdate1' => $request->get('pubdate', 'any'),
                    '#research_type' => $request->get('research_type', 'all'),
                ];
            }
        } else {
            $build[] = [
                '#cache' => array('max-age' => 0),
                '#theme' => 'biopharma_search_no_result',
                '#content' => array('query' => $qStr),
                '#tabs' => $tabs,
                '#ownership' => $request->get('ownership', 'all'),
                '#pubdate' => $request->get('pubdate', 'any'),
                '#research_type' => $request->get('research_type', 'all'),
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
            'library' => ['insight_search/insight_biopharma_search'],
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
     * @throws BadRequestHttpException
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

        $query->setFields(
                array(
                    'id',
                    'nid:item_id',
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
//                    'nid:item_id',
                    'title:tf_title',
                    'file_attachments_name:sm_field_file_attachment_name',
                    'file_attachments_url:sm_field_file_attachment_url',
                    'file_attachments_mime_type:sm_field_file_attachment_mime',
                    'title:tf_title',
                    '[child parentFilter="(level: ' . $rid . '.L1 OR level: ' . $rid . '.CSG)" childFilter="((level: ' . $rid . '.L2 OR (level: ' . $rid . '.CS AND is_landing_card:0 OR -bs_field_abstract:true)) AND ((tf_title:(' . addslashes($qStr) . ')) OR (tf_body_field:(' . addslashes($qStr) . '))))" limit=1000 childFieldList="is_nid,tf_title,nid,ss_node_type,ss_url,is_field_embed_cs_id,is_field_embed_slide_no"]',
                )
        );

         $SearchController = new SearchController();
         $UserEntitlements =  $SearchController->getUserEntitlements(self::BUSNIESS_UNIT);
         $query->addParam('sku', 'sm_field_sku:(' . $UserEntitlements . ')');

       // $facetSet = $query->getFacetSet();
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
        $build[] = [
            '#cache' => array('max-age' => 0),
            '#theme' => 'insight_search_more_result',
            '#content' => $resultset->getDocuments()
        ];
        print render($build);
        exit;
    }

    /**
     * Get desired template name 
     * @param string $param
     */
    function getDesiredTemplateNameByType($tabs) {
        $template = '';
        switch ($tabs) {
            case 'container':
                $template = 'biopharma_search_item';
                break;

            case 'chart':
                $template = 'biopharma_search_item_chart';
                break;

            case 'table':
                $template = 'biopharma_search_item_table';
                break;

            case 'text':
                $template = 'biopharma_search_item_text';
                break;
        }
        return $template;
    }

    /**
     * This will return the soft trim string
     * @param text $text
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
     * Setting the field Query and returning all FQ in array form.
     * @param array $filters
     * @param string $current_active_tab
     * @return array
     */
    public function setFieldQuery($filters, $current_active_tab, $p_is_field_report_research_type, $s_is_field_report_research_type) {
        if ($current_active_tab != 'container') {
            $fq[] = $this->getNodeTypeFilter($current_active_tab);
            $fq[] = 'ss_report_type:biopharma OR ss_report_type:c-d';
            $fq[] = "(-bs_field_abstract:true)";
            $fq[] = $this->setLandingCardFilter(FALSE);
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
        
          // Set Filter query for research_type.
        if($current_active_tab!='container'){
        if (isset($filters['research_type']) && !empty($filters['research_type'])) {
            if ($filters['research_type'] == 'proprietary research') {
                $fq[] = "(is_field_report_research_type:$p_is_field_report_research_type)";
            } elseif ($filters['research_type'] == 'syndicated') {
                $fq[] = "(is_field_report_research_type:$s_is_field_report_research_type)";
            }
        }
        }
        return $fq;
    }

    /**
     * Set landing card filter flag.
     *
     * @param mixed $flag
     *    Indicates the flag for landing card.
     *
     * @return string
     *    Returns the landing card filter.
     */
    public function setLandingCardFilter($lc_flag) {
        if ($lc_flag) {
            $landing_card_filter = "is_landing_card:1";
        } else {
            $landing_card_filter = "is_landing_card:0";
        }

        return $landing_card_filter;
    }

    /**
     * Get the Node Type Filter
     * @param string $value
     * @return string
     */
    function getNodeTypeFilter($value) {

        switch ($value) {
            case 'chart':
                $node_type_filter = "ss_node_type:figure";
                break;

            case 'table':
                $node_type_filter = "ss_node_type:table";
                break;

            case 'text':
                $node_type_filter = "ss_node_type:data";
                break;
        }
        return $node_type_filter;
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
