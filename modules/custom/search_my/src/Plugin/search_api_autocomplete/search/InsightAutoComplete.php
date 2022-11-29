<?php
/**
 * Created by PhpStorm.
 * User: bthiyagarajan
 * Date: 18/10/19
 * Time: 12:38 PM
 */

namespace Drupal\insight_search\Plugin\search_api_autocomplete\search;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\search_api\Utility\QueryHelperInterface;
use Drupal\search_api_autocomplete\Search\SearchPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Provides a base class for search plugins.
 *
 * Plugins extending this class need to define a plugin definition array through
 * annotation. The definition includes the following keys:
 * - id: The unique, system-wide identifier of the search plugin.
 * - label: The human-readable name of the search plugin, translated.
 * - description: A human-readable description for the search plugin,
 *   translated.
 *
 * A complete plugin definition should be written as in this example:
 *
 * @code
 * @SearchApiAutocompleteSearch(
 *   id = "insight_search_autocomplete",
 *   label = @Translation("Insight Search Autocomplete"),
 *   description = @Translation("Custom-defined site-specific search."),
 *   index = "insight_content",
 * )
 * @endcode
 * /
 **/

class InsightAutocomplete extends SearchPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The query helper service.
   *
   * @var \Drupal\search_api\Utility\QueryHelperInterface|null
   */
  protected $queryHelper;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $plugin = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );

    $plugin->setQueryHelper($container->get('search_api.query_helper'));

    return $plugin;
  }

  /**
   * Retrieves the query helper.
   *
   * @return \Drupal\search_api\Utility\QueryHelperInterface
   *   The query helper.
   */
  public function getQueryHelper() {
    return $this->queryHelper ?: \Drupal::service('search_api.query_helper');
  }

  /**
   * Sets the query helper.
   *
   * @param \Drupal\search_api\Utility\QueryHelperInterface $query_helper
   *   The new query helper.
   *
   * @return $this
   */
  public function setQueryHelper(QueryHelperInterface $query_helper) {
    $this->queryHelper = $query_helper;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function createQuery($keys, array $data = []) {
    $query = $this->getQueryHelper()->createQuery($this->getIndex());
    $query->keys($keys);

//    $query->setOption('qf',true);

//    $query->setOption('distrib', false);
    $page = $this->getPage();
//    dump($page);exit;
//    if ($page && $page->getSearchedFields()) {
//      $query->setFulltextFields(array_values('title'));
//    }
//    dump($query);exit;
    return $query;
  }

  protected function getPage() {
    /** @var \Drupal\search_api_page\SearchApiPageInterface $page */

    return ['title'=> 'a','body' => 'n'];
  }

}