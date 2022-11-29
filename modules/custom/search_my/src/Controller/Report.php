<?php

/**
 * @file
 * Contains \Drupal\custom_search\Controller\SearchController.
 */

namespace Drupal\insight_search\Controller;

use Drupal\Core\Controller\ControllerBase;
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
use Drupal\insight_search\Controller\DigitalReport;
use Drupal\Component\Utility\UrlHelper;
use Drupal\insight_search\Controller\SearchController;


/**
 * SearchController.
 */
class Report extends ControllerBase {
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
    protected $digital_report;
    
    const BUSNIESS_UNIT = 'BioPharma';


    /**
     * Class constructor.
     */
    public function __construct(EntityTypeManagerInterface $entity, RequestStack $request, ParseModePluginManager $parse_mode_manager, TaxonomyTermTree $taxonomyTermTree, DigitalReport $digital_report) {
        $this->nodeStorage = $entity->getStorage('node');
        $this->request = $request->getMasterRequest();
        $this->parseModeManager = $parse_mode_manager;
        $this->taxonomy_term_tree_service = $taxonomyTermTree;
        $this->digital_report = $digital_report;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        // Instantiates this form class.
        $taxonomy_term_tree_service = $container->get('content_tree.TaxonomyTermTree');
        return new static(
                $container->get('entity_type.manager'), $container->get('request_stack'), $container->get('plugin.manager.search_api.parse_mode'), $taxonomy_term_tree_service, $container->get('insight_search.digitalreport')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSolardata($nid = '', $params = '', $page = '') {

        $settings = \Drupal\Core\Site\Settings::get('scms_solr_config');
        $config = array(
            'adapter' => 'Solarium\Core\Client\Adapter\Guzzle',
            'endpoint' => array(
                'localhost' => $settings
            )
        );
        $client = new \Solarium\Client($config);
        $query = $client->createSelect();


      //  $solr_client = $this->getSolrClient('scms');
        // Initiate Solarium basic select query.
       // $query = new SelectQuery();
        $returned_fields = array(
            'is_nid', 
            'item_id',
            'index_id', 
            'site',
            'ss_search_api_id', 
            'tf_title',
            'tf_field_topic_title', 
            'tf_report_title',
            'tf_body_field', 
            'tf_field_file_attachment',
            'sm_field_file_attachment_mime',
            'sm_field_file_attachment_name',
            'ss_url', 
            'sm_field_legacy_category_name', 
            'sku:sm_field_sku',
            'hasAccess:if(exists(query({!v="${sku}"})),1,0)',
            'ds_field_publish_date', 
            'tf_body_html_field',
            'access_check',
            'numFound',
            'sm_field_file_attachment_url',
            'sm_field_destination_category_name'
            );
        $query->setFields(array_unique($returned_fields));
        $query->createFilterQuery('a')->setQuery('level:*.RL');
        $query->createFilterQuery('report')->setQuery('ss_type:content_library');
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
        $page = $page * 10;

        if (!empty($params['queryParams'])) {
            $query->setQuery('tf_title:' . $params['queryParams']);
        }


        if (!empty($params['pbdate'])) {
            $fdate = $params['pbdate'];
            $today = date("Y-m-d\TH:i:s.000\Z", strtotime(date("Y-m-d")));
            $query->createFilterQuery('range')->setQuery('ds_field_publish_date:[' . $fdate . ' TO ' . $today . ']');
        }
        $query->setStart($page)->setRows(10);

        $query->addSort('ds_field_publish_date', 'desc');
        
        $SearchController = new SearchController();
        $UserEntitlements =  $SearchController->getUserEntitlements(self::BUSNIESS_UNIT);

        $query->addParam('sku', 'sm_field_sku:(' . $UserEntitlements . ')');

        $result_set = $client->select($query);
        $resultData = $result_set->getData();
        return $resultData;
    }

    /**
     * {@inheritdoc}
     */
    public function getFacetCounts($params = '') {
        //$solr_client = $this->getSolrClient('scms');
        
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
       // $query = new SelectQuery();
        $returned_fields = array('is_nid');
        $query->setFields(array_unique($returned_fields));
        $query->createFilterQuery('level')->setQuery('level:*.RL');
        $query->createFilterQuery('report')->setQuery('ss_type:content_library');


        if (!empty($params['filter'])) {
            $alpha = 'c';
            foreach ($params['filter'] as $param) {
                $query->createFilterQuery($alpha)->setQuery($param);
                $alpha++;
            }
        }

        if (!empty($params['pbdate'])) {
            $fdate = $params['pbdate'];
            $today = date("Y-m-d\TH:i:s.000\Z", strtotime(date("Y-m-d")));
            $query->createFilterQuery('range')->setQuery('ds_field_publish_date:[' . $fdate . ' TO ' . $today . ']');
        }

        if (!empty($params['queryParams'])) {
            $query->setQuery('tf_report_title:' . $params['queryParams']);
        }

        $facet_set = $query->getFacetSet();
        $facet_set->createFacetField('therapy_area_disease')->setField('is_field_therapy_area_disease');
        $facet_set->createFacetField('destination_category')->setField('im_field_destination_category');
        $result_set = $client->select($query);
        $therapy_area_disease_facet = $result_set->getFacetSet()->getFacet('therapy_area_disease')->getValues();
        $destination_category_facet = $result_set->getFacetSet()->getFacet('destination_category')->getValues();
        $servicePreview = \Drupal::service('insights_preview.previewService');
        $facetarray = $servicePreview->arrayMergeRecursiveDistinct($therapy_area_disease_facet, $destination_category_facet);
        return $facetarray;
    }

    /**
     * {@inheritdoc}
     */
    public function getReportLibrary() {

        try {


            $Digitalservice = \Drupal::service('insight_search.digitalreport');
            $digital = \Drupal::request()->query->get('solution');
            $queryParams = \Drupal::request()->query->get('query');
             \Drupal::service('insights_service.user')->insertLastSearchitem($queryParams); 
             
             // Generating search log
              $queryParams = \Drupal::request()->query->get('query');
              \Drupal::service('insights_service.user')->insertLastSearchitem($queryParams);
              
            if (isset($digital) && $digital == 'digital') {
                return $Digitalservice->getDigitalReport();
                
            } else {
               
                // Get solarium client.
                $pbdate = '';
                $variables_to_js = $this->getSearchHistory();
                $current_uri = \Drupal::request()->getRequestUri();
                $output = [];
                $rt = urldecode($current_uri);
                parse_str($rt, $output);
                if (!empty($output['pd'])) {

                    $date = date("Y-m-d", strtotime('-' . $output['pd']));
                    $pbdate = date("Y-m-d\TH:i:s.000\Z", strtotime($date));
                }
                $term_data = $this->getReportTaxonomy('rl_therapy_area_diseases');
                $term_data_sol = $this->getReportTaxonomy('product_line');
                $research_type = $this->getReportTaxonomy('report_research_type');
                $term['area_diseases'] = $term_data;
                $term['product_line'] = $term_data_sol;
                $term['research_type'] = $research_type;

                // add filters

                $selct = array();
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
                $page = \Drupal::request()->query->get('page');
                $queryfornextpage = \Drupal::request()->query->all();
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
                    if ($page > $loop) {
                        $next_page_url = false;
                    }
                }
                $showownedres = UrlHelper::buildQuery($queryfornextpage);
                $result = $result['response']['docs'];

                // Get query parameters filters.



                foreach ($result as $key => $data) {
                    $nid = $data['is_nid'];
                    $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
                    foreach ($data['sm_field_file_attachment_name'] as $urlkey => $filename) {
                        $extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $icon_type = $Digitalservice->insight_platform_get_icon_type(trim($extension));
                        $result[$key]['icon_type'][$urlkey] = $icon_type;
                    }
                }
                return [
                    '#cache' => array('max-age' => 0),
                    '#theme' => 'search_report',
                    '#report_count' => $report_count,
                    '#report' => $result,
                    '#term' => $term,
                    '#facet' => $taxonomyfacet,
                    '#next_page_url' => $next_page_url,
                    '#showownedres' => $showownedres,
                    '#attached' => [
                        'library' => [
                            'insight_search/search_filter',
                        ],
                        'drupalSettings' => [
                            'insight_platform_search' => array('search_result' => $variables_to_js),
                        ]
                    ]
                ];
            }
        } catch (Exception $e) {
            \Drupal::logger('insight_search')->error($e->getMessage());
            throw $e;
        }
    }

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

    public function getSearchHistory() {
        $query_params = \Drupal::request()->query->all();
        $report_library_page = FALSE;
        $current_facet_filters = array();
        if (array_key_exists('category', $query_params) && $query_params['category'] == 'library') {
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

    /**
     * {@inheritdoc}
     */
    function getSingleResult($nid) {

        try {
            $Digitalservice = \Drupal::service('insight_search.digitalreport');
            $digital = \Drupal::request()->query->get('solution');
            if (isset($digital) && $digital == 'digital') {

                return $Digitalservice->getSingleResult($nid);
            } else {
                $response = new AjaxResponse();
                global $base_url;
                $singledata = $this->getSolardata($nid);

                $singledata = $singledata['response']['docs'];

                foreach ($singledata as $key => $data) {
                    $nid = $data['is_nid'];
                    $alias = \Drupal::service('path.alias_manager')->getAliasByPath('/node/' . $nid);
                    $singledata[$key]['path_alias'] = $base_url . $alias;
                    foreach ($data['sm_field_file_attachment_name'] as $urlkey => $filename) {
                        $extension = pathinfo($filename, PATHINFO_EXTENSION);
                        $icon_type = $Digitalservice->insight_platform_get_icon_type(trim($extension));

                        $singledata[$key]['icon_type'][$urlkey] = $icon_type;
                    }
                }
//echo"<pre>";print_r($singledata);die;
                $build = [
                    '#theme' => 'search_report_result',
                    '#singlereport' => $singledata,
                    '#attached' => [
                      'library' => [
                        'insight_search/search_filter',
                      ]
                    ]
                ];

                $response->addCommand(new OpenModalDialogCommand('Report', $build, ['width' => '900', 'height' => '650']));

                return $response;
            }
        } catch (Exception $e) {
            \Drupal::logger('insight_search')->error($e->getMessage());
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    function download($filename) {

        try {

            $Digitalservice = \Drupal::service('insight_search.digitalreport');

            ignore_user_abort(TRUE);
            // Disable the time limit for this script.
            set_time_limit(0);
            $filepath = $Digitalservice->get_signed_url($filename);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            //print $extension;die;
            $mime_type = $Digitalservice->insight_platform_get_mime_type(trim($extension));
            if (isset($mime_type)) {
                header('Content-Type: ' . $mime_type . '');
            }

            header("Content-disposition: attachment; filename=\"" . basename(urldecode($file_name)) . "\"");
            readfile($filepath);
//        if (array_key_exists('global_dload', $params) && $params['global_dload'] == 1) {
//
//            unlink($filepath);
//        }
        } catch (Exception $e) {
            \Drupal::logger('insight_search')->error($e->getMessage());
            throw $e;
        }
        exit;
    }

    /**
     * {@inheritdoc}
     */
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
    
    function getUserEntitlements() {
        $currentAccount = \Drupal::currentUser();
        $mail = $currentAccount->getEmail();
        $client = \Drupal::httpClient();
        $request = $client->post(self::CURRENT_USER_SKU_BASE_URL, [
            'json' => [
                'username' => $mail,
                'BusinessUnit' => 'BioPharma',
            ]
        ]);
        $response = json_decode($request->getBody());
        // In case of any exception 
        if ($response->status == 'error') {
            return '0 OR 0';
        }
        if (isset($response) && !empty($response)) {
            $data = array();
            if (isset($response[0]->BioPharma) && !empty($response[0]->BioPharma)) {
                foreach ($response[0]->BioPharma as $records_set) {
                    foreach ($records_set as $chunk_data) {
                        $data[] = $chunk_data->Report_SKU;
                    }
                }
            }


            if (isset($data) && count($data) > 0) {
                return implode(' OR ', $data);
            } else {
            return '0 OR 0';
            }
        }
    }

}
