<?php

/**
 * @file
 * Contains \Drupal\custom_search\Controller\SearchController.
 */

namespace Drupal\insight_search\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\search_api\ParseMode\ParseModePluginManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Entity\Index;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\Core\Routing;
use \Drupal\search_api\Query\QueryInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Aws\CloudFront\CloudFrontClient;
use Aws\Credentials\Credentials;
use Solarium\QueryType\Select\Query\Query as SelectQuery;
use Solarium\Client;
use Drupal\content_tree\TaxonomyTermTree;
use Drupal\Component\Utility\UrlHelper;

/**
 * SearchController.
 */
class DigitalReport {
    /**
     * Number of result by page
     * @var Integer
     */

    /**
     * Request stack that controls the lifecycle of requests
     * @var RequestStack
     */
    private $request;

    /**
     * The node Storage.
     *
     * @var \Drupal\node\NodeStorageInterface
     */
    protected $nodeStorage;

    /**
     * The parse mode manager.
     *
     * @var \Drupal\search_api\ParseMode\ParseModePluginManager
     */
    private $parseModeManager;
    protected $index;
    private $taxonomy_term_tree_service;

    /**
     * Class constructor.
     */
    public function __construct(EntityTypeManagerInterface $entity, RequestStack $request, ParseModePluginManager $parse_mode_manager, TaxonomyTermTree $taxonomyTermTree) {
        $this->nodeStorage = $entity->getStorage('node');
        $this->request = $request->getMasterRequest();
        $this->parseModeManager = $parse_mode_manager;
        $this->taxonomy_term_tree_service = $taxonomyTermTree;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        // Instantiates this form class.
        $taxonomy_term_tree_service = $container->get('content_tree.TaxonomyTermTree');
        return new static(
                $container->get('entity_type.manager'), $container->get('request_stack'), $container->get('plugin.manager.search_api.parse_mode'), $taxonomy_term_tree_service
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getFacetCounts($params = '') {
        //$solr_client = $this->getSolrClient('scms');
        // Initiate Solarium basic select query.
       // $query = new SelectQuery();
        
          $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');
        $config = array(
            'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
            'endpoint' => array(
                'localhost' => $settings
            )
        );
        $client = new \Solarium\Client($config);
        $query = $client->createSelect();
        
        $returned_fields = array('is_nid');
        $query->setFields(array_unique($returned_fields));
        $query->createFilterQuery('digital')->setQuery('ss_type:digital_content_library');

        if (!empty($params['filter'])) {
            $alpha = 'c';
            foreach ($params['filter'] as $param) {
                $query->createFilterQuery($alpha)->setQuery($param);
                $alpha++;
            }
        }

        if (!empty($params['queryParams'])) {
            $query->setQuery('tf_title:' . $params['queryParams']);
        }


        if (!empty($params['pbdate'])) {
            $fdate = $params['pbdate'];
            $today = date("Y-m-d\TH:i:s.000\Z", strtotime(date("Y-m-d")));
            $query->createFilterQuery('range')->setQuery('ds_field_publish_date:[' . $fdate . ' TO ' . $today . ']');
        }

        $facet_set = $query->getFacetSet();
        $facet_set->createFacetField('field_geography')->setField('itm_field_geography');
        $facet_set->createFacetField('research_type')->setField('is_field_report_research_type');
        $facet_set->createFacetField('deliverable_type')->setField('is_field_deliverable_type');
        $facet_set->createFacetField('therapy_area_disease')->setField('is_field_therapy_area_disease');
        $facet_set->createFacetField('legacy_category')->setField('im_field_legacy_category');

        $result_set = $client->select($query);
        $field_geography_facet = $result_set->getFacetSet()->getFacet('field_geography')->getValues();
        $research_type_facet = $result_set->getFacetSet()->getFacet('research_type')->getValues();
        $deliverable_type_facet = $result_set->getFacetSet()->getFacet('deliverable_type')->getValues();
        $therapy_area_disease_facet = $result_set->getFacetSet()->getFacet('therapy_area_disease')->getValues();
        $legacy_category_facet = $result_set->getFacetSet()->getFacet('legacy_category')->getValues();
        $servicePreview = \Drupal::service('insights_preview.previewService');
        $facetarray = $servicePreview->arrayMergeRecursiveDistinct($field_geography_facet, $research_type_facet);
        $facetarray = $servicePreview->arrayMergeRecursiveDistinct($facetarray, $deliverable_type_facet);
        $facetarray = $servicePreview->arrayMergeRecursiveDistinct($facetarray, $therapy_area_disease_facet);
        $facetarray = $servicePreview->arrayMergeRecursiveDistinct($facetarray, $legacy_category_facet);
        
        return $facetarray;
    }

    /**
     * {@inheritdoc}
     */
    public function getSolardata($nid = '', $params = '', $page = '') {
        //print_r($params);die;

       // $solr_client = $this->getSolrClient('scms');
         $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');
        $config = array(
            'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
            'endpoint' => array(
                'localhost' => $settings
            )
        );
        $client = new \Solarium\Client($config);
        $query = $client->createSelect(); 
       
        // Initiate Solarium basic select query.
        //$query = new SelectQuery();
        $returned_fields = array('is_nid', 'index_id', 'site', 'ss_search_api_id', 'tf_title', 'tf_field_topic_title', 'tf_report_title', 'tf_body_field', 'tf_field_file_attachment', 'sm_field_file_attachment_mime', 'sm_field_file_attachment_name',
            'ss_url', 'sm_field_legacy_category_name', 'sm_field_sku', 'ds_field_publish_date', 'tf_body_html_field', 'access_check', 'numFound', 'sm_field_file_attachment_url', 'sm_field_destination_category_name');
        $query->setFields(array_unique($returned_fields));
        $query->createFilterQuery('digital')->setQuery('ss_type:digital_content_library');
        if (!empty($nid)) {
            $query->createFilterQuery('b')->setQuery('is_nid:' . $nid);
        }

        if (!empty($params['filter'])) {
            $alpha = 'c';
            foreach ($params['filter'] as $param) {
                $query->createFilterQuery($alpha)->setQuery($param);
                $alpha++;
            }
        }
        if (!empty($page)) {
            $page = $page * 10;
        } else {
            $page = 0;
        }
        $query->setStart($page)->setRows(10);


        $query->addSort('ds_field_publish_date', 'desc');

        if (!empty($params['queryParams'])) {
            $query->setQuery('tf_report_title:' . $params['queryParams']);
        }


        if (!empty($params['pbdate'])) {
            $fdate = $params['pbdate'];
            $today = date("Y-m-d\TH:i:s.000\Z", strtotime(date("Y-m-d")));
            $query->createFilterQuery('range')->setQuery('ds_field_publish_date:[' . $fdate . ' TO ' . $today . ']');
        }

        $result_set = $client->select($query);
        $resultData = $result_set->getData();
        return $resultData;
    }

    /**
     * {@inheritdoc}
     */
    public function getReportTaxonomy($vocabulary_id) {
        $taxonomy_term_tree_service = \Drupal::service('content_tree.TaxonomyTermTree');
        $taxonomy_term_tree = $taxonomy_term_tree_service->load($vocabulary_id);
        $terms = array();
        foreach ($taxonomy_term_tree as $taxonomy_term_tree_parent_data) {
            foreach ($taxonomy_term_tree_parent_data->children as $taxonomy_term_tree_children_data) {
                $terms[$taxonomy_term_tree_parent_data->name]['children'][] = array(
                    'tid' => $taxonomy_term_tree_children_data->tid,
                    'name' => $taxonomy_term_tree_children_data->name,
                );
            }
            if (is_array($terms[$taxonomy_term_tree_parent_data->name]['children'])) {
                array_multisort(array_column($terms[$taxonomy_term_tree_parent_data->name]['children'], "name"), SORT_ASC, $terms[$taxonomy_term_tree_parent_data->name]['children']);
            }
            $terms[$taxonomy_term_tree_parent_data->name]['name'] = $taxonomy_term_tree_parent_data->name;
            $terms[$taxonomy_term_tree_parent_data->name]['tid'] = $taxonomy_term_tree_parent_data->tid;
        }
        return $terms;
    }

    /**
     * {@inheritdoc}
     */
    public function getDigitalReport() {

        try {
            $queryParams = \Drupal::request()->query->get('query');
            $variables_to_js = $this->getSearchHistory();

            $current_uri = \Drupal::request()->getRequestUri();
            $output = [];
            $rt = urldecode($current_uri);
            parse_str($rt, $output);
            if (!empty($output['pd'])) {
                $date = date("Y-m-d", strtotime('-' . $output['pd']));
                $pbdate = date("Y-m-d\TH:i:s.000\Z", strtotime($date));
            }

            $medtech_geography = $this->getReportTaxonomy('biopharma_digital_geography');
            $study_name = $this->getReportTaxonomy('legacy_category');
            $research_type = $this->getReportTaxonomy('report_research_type');
            $deliverable_type = $this->getReportTaxonomy('deliverable_type');
            $therapy_area_diseases = $this->getReportTaxonomy('rl_therapy_area_diseases');

            $term['medtech_geography'] = $medtech_geography;
            $term['study_name'] = $study_name;
            $term['research_type'] = $research_type;
            $term['deliverable_type'] = $deliverable_type;
            $term['therapy_area_diseases'] = $therapy_area_diseases;


            // add filters

            $selct = array();
            $resultsel = array();
            $select['filter'] = $output['f'];
            $select['pbdate'] = $pbdate;
            $select['queryParams'] = $queryParams;
            $result = $this->getSolardata('', $select);
            $taxonomyfacet = $this->getFacetCounts($select);



            //  get response from solar
            $total_per_page = 10;
            $current_page = 1;

            $report_count = $result['response']['numFound'];
            $num_of_pages = ceil($report_count / $total_per_page);
            $page = \Drupal::request()->query->all();

            $clear_all_filter = $page;
            if (array_key_exists('pd', $page)) {
                $current_pubdate_filter = $page['pd'];
                unset($page['pd']);

                // Remove f query parameters for clear all link.
                $clear_all_filter = $page;
            }

            unset($clear_all_filter['f']);

            $pub_date_filter = array(
                'any_date' => array(
                    'value' => 'Any Publication Date',
                    'url' => UrlHelper::buildQuery($page),
                ),
                '6months' => array(
                    'value' => 'Last 6 Months',
                    'url' => UrlHelper::buildQuery(array_merge($page, array('pd' => '6months'))),
                ),
                '1year' => array(
                    'value' => 'Last year',
                    'url' => UrlHelper::buildQuery(array_merge($page, array('pd' => '1year'))),
                ),
                '2years' => array(
                    'value' => 'Last 2 years',
                    'url' => UrlHelper::buildQuery(array_merge($page, array('pd' => '2years'))),
                ),
                '3years' => array(
                    'value' => 'Last 3 years',
                    'url' => UrlHelper::buildQuery(array_merge($page, array('pd' => '3years'))),
                ),
            );
            $page = $page['page'];

            $queryfornextpage = \Drupal::request()->query->all();
            $page = $queryfornextpage['page'];
            if (isset($page) && ($page != $num_of_pages)) {
                $current_page = $page;
            }
            $next_page = false;
            if ($num_of_pages > 1 && $current_page < $num_of_pages) {
                $next_page = $current_page + 1;
                $queryprms = UrlHelper::buildQuery(array_merge($queryfornextpage, array('page' => $next_page)));
                $next_page_url = '/search?' . $queryprms;
            }

            if (!empty($page)) {

                $result = $this->getSolardata('', $select, $next_page);
                $loop = $report_count / 10;
                if ($page > $num_of_pages) {
                    $next_page_url = false;
                }
            }
//            if (isset($next_page_url) && $next_page_url = !false) {
//                $result = $this->getSolardata('', $select, $next_page);
//            }
//            if (!empty($page)) {
//
//                $result = $this->getSolardata('', $select, $next_page);
//
//               
//            }
            $showownedres = UrlHelper::buildQuery($queryfornextpage);
            $result = $result['response']['docs'];


            foreach ($result as $key => $data) {
                $nid = $data['is_nid'];
                $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
                foreach ($data['sm_field_file_attachment_name'] as $urlkey => $filename) {
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    $icon_type = $this->insight_platform_get_icon_type(trim($extension));

                    $result[$key]['icon_type'][$urlkey] = $icon_type;
                }
            }

            // on load filter select list 

            foreach ($output['f'] as $onloadfilter) {

                $onload_tid = explode(':', $onloadfilter);
                $vname = $onload_tid[0];
                $onloadtid = $onload_tid[1];

                $children = $this->_taxonomy_tid_and_taxonomy_terms_single_entity($onloadtid);
                if ($vname == 'im_field_legacy_category') {

                    $resultsel['selected_legacy_category'][] = $children[$onloadtid];
                }
                if ($vname == 'is_field_deliverable_type') {
                    $resultsel['selected_deliverable_type'][] = $children[$onloadtid];
                }
                if ($vname == 'is_field_report_research_type') {
                    $resultsel['selected_research_type'][] = $children[$onloadtid];
                }
                if ($vname == 'itm_field_geography') {
                    $fildarray[] = $onloadtid;
                }
                if ($vname == 'is_field_therapy_area_disease') {
                    $therapyarray[] = $onloadtid;
                }
            }

            // onload selcted filter list for geography

            if (!empty($fildarray)) {
                $vid = 'biopharma_digital_geography';
                $getChild = $this->getChildTaxonomy($vid, $fildarray);
                array_walk_recursive($getChild, function($k, $v)use(&$georesult) {
                    $georesult[$v][] = $k;
                });
                $georesult['tid'] = array_unique($georesult['tid']);
                $georesult['name'] = array_unique($georesult['name']);
                $resultsel['field_geography'] = $georesult;
            }

            // onload selcted filter list for disease

            if (!empty($therapyarray)) {
                $vid = 'rl_therapy_area_diseases';
                $getChild = $this->getChildTaxonomy($vid, $therapyarray);
                array_walk_recursive($getChild, function($k, $v)use(&$thresult) {
                    $thresult[$v][] = $k;
                });
                $thresult['tid'] = array_unique($thresult['tid']);
                $thresult['name'] = array_unique($thresult['name']);
                $resultsel['therapy_area_disease'] = $thresult;
            }

            return [
                '#theme' => 'digital_report',
                '#report_count' => $report_count,
                '#term' => $term,
                '#facet' => $taxonomyfacet,
                '#report' => $result,
                '#resultselected' => $resultsel,
                '#next_page_url' => $next_page_url,
                '#pub_date_filter' => $pub_date_filter,
                '#current_pubdate_filter' => $current_pubdate_filter,
                '#showownedres' => $showownedres,
                '#cache' => array('max-age' => 0),
                '#attached' => [
                    'library' => [
                        'insight_search/search_filter',
                    ],
                    'drupalSettings' => [
                        'insight_platform_search' => array('search_result' => $variables_to_js),
                        'digital_report_library' => array('target_url' => 'search?category=library&solution=digital')
                    ]
                ]
            ];
        } catch (Exception $e) {
            \Drupal::logger('insight_search')->error($e->getMessage());
            throw $e;
        }
    }

    public function getSearchHistory() {


        $query_params = \Drupal::request()->query->all();
        $report_library_page = FALSE;
        $current_facet_filters = array();
        if (array_key_exists('solution', $query_params) && $query_params['solution'] == 'digital') {
            $report_library_page = TRUE;

            if (array_key_exists('f', $query_params)) {
                $current_facet_filters = $query_params['f'];
            }
        }
        $search_result = array(
            'digital_search' => $report_library_page,
            'current_facet_filters' => $current_facet_filters
        );

        return $search_result;
    }

    public function getSolrClient($server) {
        // get solar configuration
        $backend_config = \Drupal::config('search_api.server.' . $server)->get('backend_config');
        $path = $backend_config['connector_config']['path'] . '/' . $backend_config['connector_config']['core'];
        $host = $backend_config['connector_config']['host'];
        // Initiate solr client.

        $solr = new Client();

        // Create and set solr client endpoint.

        $solr->createEndpoint($backend_config + ['key' => 'core'] + ['path' => $path] + ['host' => $host], TRUE);
        return $solr;
    }

    /**
     * {@inheritdoc}
     */
    function getSingleResult($nid) {
        $response = new AjaxResponse();
        global $base_url;

        // Get the modal form using the form builder.
        //$modal_form = $this->formBuilder->getForm('Drupal\modal_form_example\Form\ModalForm');
        // Add an AJAX command to open a modal dialog with the form as the content.
        //$getresult = file_get_contents('http://solr-clou-solrclou-1w69u8mi2dsx-786664318.us-east-1.elb.amazonaws.com:8983/solr/qa/select?fq=is_nid:' . $nid . '&fq=level:*.RL&indent=on&q=*:*&rows=1&wt=json');
        $singledata = $this->getSolardata($nid);
        $singledata = $singledata['response']['docs'];
//        //echo"<pre>";        print_r($singledata);
//        $getresult = $this->getSolardata();
        //$host = $base_url(); 
        foreach ($singledata as $key => $data) {
            $nid = $data['is_nid'];
            $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
            $singledata[$key]['path_alias'] = $base_url . $alias;
            foreach ($data['sm_field_file_attachment_name'] as $urlkey => $filename) {
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                $icon_type = $this->insight_platform_get_icon_type(trim($extension));

                $singledata[$key]['icon_type'][$urlkey] = $icon_type;
            }
        }

        $build = [
            '#theme' => 'search_report_result',
            '#singlereport' => $singledata,
        ];

        $response->addCommand(new OpenModalDialogCommand(' Report', $build, ['width' => '900', 'height' => '650']));

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    function download($filename) {

        ignore_user_abort(TRUE);
        // Disable the time limit for this script.
        set_time_limit(0);
        $filepath = $this->get_signed_url($filename);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        //print $extension;die;
        $mime_type = $this->insight_platform_get_mime_type(trim($extension));
        if (isset($mime_type)) {
            header('Content-Type: ' . $mime_type . '');
        }

        header("Content-disposition: attachment; filename=\"" . basename(urldecode($file_name)) . "\"");
        readfile($filepath);
//        if (array_key_exists('global_dload', $params) && $params['global_dload'] == 1) {
//
//            unlink($filepath);
//        }
        exit;
    }

    /**
     * {@inheritdoc}
     */
    function get_signed_url($resource) {
        define('CLOUDFRONTKEYPAIRID', 'APKAIZCXNSCWBOU5JSFQ');
        define('CLOUDFRONTPRIVATEKEYNAME', 'pk-APKAIZCXNSCWBOU5JSFQ.pem');
        define('CLOUDFRONTPRIVATEKEYPATH', 'private');
        //$credentials = new Credentials('AKIAIVLRT3GWOU26A3TA', 'hL3NUGgM7BxpXxIf4jP1YK9MhBAcs2xPwC92RkNc');
        $cloudFront = CloudFrontClient::factory(array(
                    'profile' => 'default',
                    'version' => '2018-06-18',
                    'region' => 'us-east-1'
        ));
        $streamHostUrl = 'http://d13b4sl6g0u4ng.cloudfront.net';

        $resourceKey = 'reportLibrary/' . $resource;
        $expires = time() + 300;
        $privateKeyFilePath = DRUPAL_ROOT . '/' . CLOUDFRONTPRIVATEKEYPATH . '/' . CLOUDFRONTPRIVATEKEYNAME;


        if (!file_exists($privateKeyFilePath)) {
            return $privateKeyFilePath;
        }
        define('CLOUDFRONTKEYPAIRID', 'APKAIZCXNSCWBOU5JSFQ');
        // Create a signed URL for the resource.
        $signedUrl = $cloudFront->getSignedUrl([
            'url' => $streamHostUrl . '/' . $resourceKey,
            'expires' => $expires,
            'private_key' => $privateKeyFilePath,
            'key_pair_id' => 'APKAIZCXNSCWBOU5JSFQ',
        ]);

        return $signedUrl;
    }

    /**
     * {@inheritdoc}
     */
    function insight_platform_get_mime_type($ext) {
        $extensions = $this->insight_platform_list_file_types();
        $ext = strtolower($ext);
        return $extensions[$ext];
    }

    /**
     * {@inheritdoc}
     */
    function getEachFacetCount($ext, $params) {
        $extensions = $this->getFacetCount('Digital_report', $params);
        $ext = strtolower($ext);
        //return $extensions[$ext];
        return $extensions[$ext] ? $extensions[$ext] : 0;
    }

    /**
     * {@inheritdoc}
     */
    function insight_platform_list_file_types() {

        $file_types = array(
            'json' => 'application/json',
            'eps' => 'application/eps',
            'jp2' => 'image/jp2',
            'jpx' => 'image/jp2',
            'jpm' => 'image/jp2',
            'jpc' => 'image/jp2',
            'j2k' => 'image/jp2',
            'jpf' => 'image/jp2',
            'mpp' => 'application/vnd.ms-project',
            'psd' => 'image/vnd.adobe.photoshop',
            'ppj' => 'image/vnd.adobe.premiere',
            'prproj' => 'image/vnd.adobe.premiere',
            'asnd' => 'audio/vnd.adobe.soundbooth',
            'aep' => 'application/vnd.adobe.aftereffects.project',
            'aet' => 'application/vnd.adobe.aftereffects.template',
            'fm' => 'application/framemaker',
            'pmd' => 'application/pagemaker',
            'pm6' => 'application/pagemaker',
            'p65' => 'application/pagemaker',
            'pm' => 'application/pagemaker',
            'prn' => 'application/remote-printing',
            'txt' => 'text/plain',
            'sql' => 'text/plain',
            'properties' => 'text/plain',
            'ftl' => 'text/plain',
            'ini' => 'text/plain',
            'bat' => 'text/plain',
            'sh' => 'text/plain',
            'log' => 'text/plain',
            'csv' => 'text/csv',
            'html' => 'text/html',
            'htm' => 'text/html',
            'shtml' => 'text/html',
            'body' => 'text/html',
            'xsd' => 'text/html',
            'mw' => 'text/mediawiki',
            'xhtml' => 'application/xhtml+xml',
            'ps' => 'application/postscript',
            'ai' => 'application/illustrator',
            'aiff' => 'audio/x-aiff',
            'aif' => 'audio/x-aiff',
            'aifc' => 'audio/x-aiff',
            'acp' => 'application/acp',
            'vsd' => 'application/vnd.visio',
            'xdp' => 'application/vnd.adobe.xdp+xml',
            'au' => 'audio/basic',
            'snd' => 'audio/basic',
            'ogv' => 'video/ogg',
            'oga' => 'audio/ogg',
            'spx' => 'audio/ogg',
            'ogg' => 'audio/vorbis',
            'ogx' => 'application/ogg',
            'flac' => 'audio/x-flac',
            'webm' => 'video/webm',
            'avi' => 'video/x-msvideo',
            'qvi' => 'video/x-msvideo',
            'asf' => 'video/x-ms-asf',
            'asx' => 'video/x-ms-asf',
            'wmv' => 'video/x-ms-wmv',
            'wma' => 'audio/x-ms-wma',
            'avx' => 'video/x-rad-screenplay',
            'bcpio' => 'application/x-bcpio',
            'bin' => 'application/octet-stream',
            'exe' => 'application/octet-stream',
            'exe' => 'application/x-dosexec',
            'cdf' => 'application/x-netcdf',
            'nc' => 'application/x-netcdf',
            'cer' => 'application/x-x509-ca-cert',
            'cgm' => 'image/cgm',
            'class' => 'application/java',
            'cpio' => 'application/x-cpio',
            'csh' => 'application/x-csh',
            'css' => 'text/css',
            'wpd' => 'application/wordperfect',
            'xml' => 'text/xml',
            'dtd' => 'text/xml',
            'xslt' => 'text/xml',
            'xsl' => 'text/xml',
            'xsd' => 'text/xml',
            'dvi' => 'application/x-dvi',
            'etx' => 'text/x-setext',
            'gif' => 'image/gif',
            'gtar' => 'application/x-gtar',
            'gzip' => 'application/x-gzip',
            'hdf' => 'application/x-hdf',
            'hqx' => 'application/mac-binhex40',
            'ics' => 'text/calendar',
            'ief' => 'image/ief',
            'bmp' => 'image/bmp',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'dng' => 'image/x-raw-adobe',
            '3fr' => 'image/x-raw-hasselblad',
            'raf' => 'image/x-raw-fuji',
            'cr2' => 'image/x-raw-canon',
            'crw' => 'image/x-raw-canon',
            'k25' => 'image/x-raw-kodak',
            'kdc' => 'image/x-raw-kodak',
            'dcs' => 'image/x-raw-kodak',
            'drf' => 'image/x-raw-kodak',
            'mrw' => 'image/x-raw-minolta',
            'nef' => 'image/x-raw-nikon',
            'nrw' => 'image/x-raw-nikon',
            'orf' => 'image/x-raw-olympus',
            'pef' => 'image/x-raw-pentax',
            'ptx' => 'image/x-raw-pentax',
            'arw' => 'image/x-raw-sony',
            'srf' => 'image/x-raw-sony',
            'sr2' => 'image/x-raw-sony',
            'x3f' => 'image/x-raw-sigma',
            'rw2' => 'image/x-raw-panasonic',
            'rwl' => 'image/x-raw-leica',
            'r3d' => 'image/x-raw-red',
            'js' => 'application/x-javascript',
            'latex' => 'application/x-latex',
            'man' => 'application/x-troff-man',
            'me' => 'application/x-troff-me',
            'ms' => 'application/x-troff-mes',
            'mif' => 'application/x-mif',
            'mpg' => 'video/mpeg',
            'mpeg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'm1v' => 'video/mpeg',
            'm2v' => 'video/mpeg',
            'mp3' => 'audio/mpeg',
            'mp2' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'm4b' => 'audio/mp4',
            'mp4a' => 'audio/mp4',
            'mp4' => 'video/mp4',
            'mp4v' => 'video/mp4',
            'mpg4' => 'video/mp4',
            'm4v' => 'video/x-m4v',
            'mpeg2' => 'video/mpeg2',
            'ts' => 'video/mp2t',
            'm2ts' => 'video/mp2t',
            'mov' => 'video/quicktime',
            'qt' => 'video/quicktime',
            '3gp' => 'video/3gpp',
            '3g2' => 'video/3gpp2',
            '3gp2' => 'video/3gpp2',
            'movie' => 'video/x-sgi-movie',
            'mpv2' => 'video/x-sgi-movie',
            'mv' => 'video/x-sgi-movie',
            'oda' => 'application/oda',
            'pbm' => 'image/x-portable-bitmap',
            'pdf' => 'application/pdf',
            'pgm' => 'image/x-portable-graymap',
            'png' => 'image/png',
            'pnm' => 'image/x-portable-anymap',
            'rpnm' => 'image/x-portable-anymap',
            'ppm' => 'image/x-portable-pixmap',
            'ras' => 'image/x-cmu-raster',
            'rgb' => 'image/x-rgb',
            'tr' => 'application/x-troff',
            't' => 'application/x-troff',
            'roff' => 'application/x-troff',
            'rtf' => 'application/rtf',
            'rtx' => 'text/richtext',
            'sgml' => 'text/sgml',
            'sgm' => 'text/sgml',
            'gml' => 'application/sgml',
            'sh' => 'application/x-sh',
            'shar' => 'application/x-shar',
            'src' => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc' => 'application/x-sv4crc',
            'air' => 'application/vnd.adobe.air-application-installer-package+zip',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            'fla' => 'application/x-fla',
            'dita' => 'application/dita+xml',
            'ditaval' => 'application/dita+xml',
            'ditamap' => 'application/dita+xml',
            'tar' => 'application/x-tar',
            'tcl' => 'application/x-tcl',
            'tex' => 'application/x-tex',
            'texinfo' => 'application/x-texinfo',
            'texi' => 'application/x-texinfo',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'tsv' => 'text/tab-separated-values',
            'ustar' => 'application/x-ustar',
            'wav' => 'audio/x-wav',
            'wrl' => 'x-world/x-vrml',
            'xbm' => 'image/x-xbitmap',
            'xpm' => 'image/x-xpixmap',
            'xwd' => 'image/x-xwindowdump',
            'z' => 'application/x-compress',
            'zip' => 'application/zip',
            'war' => 'application/zip',
            'jar' => 'application/zip',
            'ear' => 'application/zip',
            'apk' => 'application/vnd.android.package-archive',
            'dwg' => 'image/vnd.dwg',
            'dwt' => 'image/x-dwt',
            'eml' => 'message/rfc822',
            'msg' => 'application/vnd.ms-outlook',
            'doc' => 'application/msword',
            'dot' => 'application/msword',
            'ppt' => 'application/vnd.ms-powerpoint',
            'pps' => 'application/vnd.ms-powerpoint',
            'pot' => 'application/vnd.ms-powerpoint',
            'ppa' => 'application/vnd.ms-powerpoint',
            'xls' => 'application/vnd.ms-excel',
            'xlt' => 'application/vnd.ms-excel',
            'xlm' => 'application/vnd.ms-excel',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'docm' => 'application/vnd.ms-word.document.macroenabled.12',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'dotm' => 'application/vnd.ms-word.template.macroenabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroenabled.12',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroenabled.12',
            'potx' => 'application/vnd.openxmlformats-officedocument.presentationml.template',
            'potm' => 'application/vnd.ms-powerpoint.template.macroenabled.12',
            'ppam' => 'application/vnd.ms-powerpoint.addin.macroenabled.12',
            'sldx' => 'application/vnd.openxmlformats-officedocument.presentationml.slide',
            'sldm' => 'application/vnd.ms-powerpoint.slide.macroenabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xltx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.template',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroenabled.12',
            'xltm' => 'application/vnd.ms-excel.template.macroenabled.12',
            'xlam' => 'application/vnd.ms-excel.addin.macroenabled.12',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroenabled.12',
            'fxp' => 'application/x-zip',
            'indd' => 'application/x-indesign',
            'ind' => 'application/x-indesign',
            'key' => 'application/vnd.apple.keynote',
            'pages' => 'application/vnd.apple.pages',
            'numbers' => 'application/vnd.apple.numbers',
            'md' => 'text/x-markdown',
            'rss' => 'application/rss+xml',
            'java' => 'text/x-java-source',
            'jsp' => 'text/x-jsp',
            'jar' => 'application/java-archive',
            'rar' => 'application/x-rar-compressed',
        );

        return $file_types;
    }

    function insight_platform_get_icon_type($ext) {
        $icon = $this->insight_platform_list_icon_types();
        $ext = strtolower($ext);
        return $icon[$ext];
    }

    function insight_platform_list_icon_types() {

        $file_types = array(
            'pptx' => 'icon-ppt',
            'pdf' => 'icon-pdf',
            'docx' => 'icon-doc',
            'xls' => 'icon-excel',
            'xlsm' => 'icon-excel',
        );

        return $file_types;
    }

    function insights_selected_filters_count($field_name) {
        global $selected_filters;
        foreach ($selected_filters[$field_name] as $parentTid => $parent_data) {
            if (array_key_exists('active_child', $parent_data) && $parent_data['active']) {
                $selected_filters[$field_name]['count'] = $selected_filters[$field_name]['count'] + count($parent_data['active_child']);
            } elseif (array_key_exists('inactive_child', $parent_data) && $parent_data['active']) {
                $selected_filters[$field_name]['count'] = $selected_filters[$field_name]['count'] + count($parent_data['inactive_child']);
            } elseif ($parent_data['active']) {
                $selected_filters[$field_name]['count'] = $selected_filters[$field_name]['count'] + 1;
            }
        }
    }

    function _taxonomy_tid_and_taxonomy_terms_parents_on_entity($taxonomyFieldArr) {
        //-- Get tid number and parent tid number
        $arrayResult = array();
        $count = array();
        foreach ($taxonomyFieldArr as $k => $vid) {
            $term = \Drupal\taxonomy\Entity\Term::load($vid);
            $storage = \Drupal::service('entity_type.manager')
                    ->getStorage('taxonomy_term');
            $parents = $storage->loadParents($term->id());
            $termName = $term->id();
            $count[$termName]['parent_tid'] = array_keys($parents);
            $arrayResult[$k][$termName]['tid'] = $term->id();
            $arrayResult[$k][$termName]['name'] = $term->getName();
        }


        return $arrayResult;
    }

    function _taxonomy_tid_and_taxonomy_terms_single_entity($taxonomyFieldArr) {
        //-- Get tid number and parent tid number
        $arrayResult = array();
        $count = array();
        $term = \Drupal\taxonomy\Entity\Term::load($taxonomyFieldArr);
        $storage = \Drupal::service('entity_type.manager')
                ->getStorage('taxonomy_term');
        $parents = $storage->loadParents($term->id());
        $termName = $term->id();
        $count[$termName]['parent_tid'] = array_keys($parents);
        if (empty($count[$termName]['parent_tid'])) {
            $arrayResult[$termName]['tid'] = $term->id();
            $arrayResult[$termName]['name'] = $term->getName();
        }

        return $arrayResult;
    }

    function getChildTaxonomy($vid, $parent_tid) {
        $result1 = array();
        $result2 = array();
        $depth = 1; // 1 to get only immediate children, NULL to load entire tree
        $load_entities = FALSE; // True will return loaded entities rather than ids
        foreach ($parent_tid as $l => $id) {

            $child_tids = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid, $id, $depth, $load_entities);
            foreach ($child_tids as $key => $child) {
                $result1[$l][$key]['tid'] = $child->tid;
                $result1[$l][$key]['name'] = $child->name;
            }
            if (empty($child_tids)) {
                $result2 = $this->_taxonomy_tid_and_taxonomy_terms_parents_on_entity($parent_tid);
            }
        }
        $servicePreview = \Drupal::service('insights_preview.previewService');
        $result = $servicePreview->arrayMergeRecursiveDistinct($result1, $result2);

        return $result;
    }
 
}
