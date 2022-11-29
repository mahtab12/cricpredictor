<?php

namespace Drupal\insight_search\Plugin\search_api\processor\Property;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Processor\ConfigurablePropertyBase;

/**
 * Defines a "dummy field" property.
 *
 * @see \Drupal\search_api_solr\Plugin\search_api\processor\DummyFields
 */
class ChildDocumentProperty extends ConfigurablePropertyBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(FieldInterface $field, array $form, FormStateInterface $form_state) {
    $configuration = $field->getConfiguration();

//    var_dump($field->getDatasource()->getPropertyDefinitions());
    $fields = array_map(function($fieldItem){
      return $fieldItem->getLabel();
    }, $field->getDatasource()->getPropertyDefinitions());
    $form['field'] = [
      '#type' => 'select',
      '#title' => $this->t('Reference Field'),
      '#options' => $fields,
//      '#description' => $this->t('The value to be set initially on the dummy field.'),
      '#default_value' => $configuration['field'],
      '#required' => TRUE,
    ];

    return $form;
  }

}
