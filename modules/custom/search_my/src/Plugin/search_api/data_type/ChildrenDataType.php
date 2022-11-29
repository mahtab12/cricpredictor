<?php

namespace Drupal\insight_search\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides an integer data type.
 *
 * @SearchApiDataType(
 *   id = "children",
 *   label = @Translation("Nested Docs"),
 *   description = @Translation("Nested Docs."),
 *   default = "true"
 * )
 */
class ChildrenDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
//    var_dump($value);die;
    return (array) $value;
  }

}
