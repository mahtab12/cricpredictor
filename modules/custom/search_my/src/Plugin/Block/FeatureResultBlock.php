<?php

namespace Drupal\insight_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\insight_search\Controller\SearchController;
/**
 * Search Block
 *
 * @Block(
 *   id = "insight_search_feature_result_block",
 *   admin_label = @Translation("Insight Search Feature Result Block"),
 *   category = @Translation("Insights Platform"),
 * )
 */
class FeatureResultBlock extends BlockBase implements ContainerFactoryPluginInterface {

    /**
     * Drupal\Core\Form\FormBuilderInterface definition.
     *
     * @var \Drupal\Core\Form\FormBuilderInterface
     */
    protected $formBuilder;

    /**
     * InsightSearchBlock constructor.
     * @param array $configuration
     * @param $plugin_id
     * @param $plugin_definition
     * @param FormBuilderInterface $form_builder
     */
    public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
        parent::__construct($configuration, $plugin_id, $plugin_definition);
        $this->formBuilder = $form_builder;
    }

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        return new static(
            $configuration,
            $plugin_id,
            $plugin_definition,
            $container->get('form_builder')
        );
    }

    /**
     * {@inheritdoc}
     */
    public function build() {

      $request = \Drupal::request();

      //if($request->request->has('query')) {
        $search_keyword = $request->query->get('query');
        $searchObj = new SearchController();
        $result = $searchObj->searchFeatureResult($search_keyword);
      //}

//      dump($result);exit;
      $build = [
        "#theme" => 'insight_search_feature_result',
        '#content' => $result,
      ];
      return $build;
    }

}