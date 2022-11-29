<?php

namespace Drupal\insight_search\Plugin\search_api_autocomplete\suggester;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_autocomplete\AutocompleteBackendInterface;
use Drupal\search_api_autocomplete\SearchInterface;
use Drupal\search_api_autocomplete\Suggester\SuggesterPluginBase;

/**
 * Defines a Insight Autocomplete suggester class.
 *
 * @SearchApiAutocompleteSuggester(
 *   id = "insight_suggester",
 *   label = @Translation("Insight suggester"),
 *   description = @Translation("Custom Solr Suggester for Insight."),
 * )
 */

class InsightSuggester extends SuggesterPluginBase implements PluginFormInterface {

  use PluginFormTrait;



  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'fields' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // Let the user select the fulltext fields to use for autocomplete.

    $options['tnsyn_suggession_contents'] = 'tnsyn_suggession_contents';

    $form['fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Override used fields'),
      '#description' => $this->t('Select the fields which should be searched for matches when looking for autocompletion suggestions. Leave blank to use the same fields as the underlying search.'),
      '#options' => $options,
      '#default_value' => array_combine($this->getConfiguration()['fields'], $this->getConfiguration()['fields']),
      '#attributes' => ['class' => ['search-api-checkboxes-list']],
    ];
    $form['#attached']['library'][] = 'search_api/drupal.search_api.admin_css';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getAutocompleteSuggestions(QueryInterface $query, $incomplete_key, $user_input) {

    $index = $this->getSearch()->getIndex();
    if (!$index->hasValidServer()) {
      return NULL;
    }
    $server = $index->getServerInstance();
    $backend = $server->getBackend();
    if ($server->supportsFeature('search_api_autocomplete') || $backend instanceof AutocompleteBackendInterface) {
      return $backend->getAutocompleteSuggestions($query, $this->getSearch(), $incomplete_key, $user_input);
    }
    return NULL;

  }

}