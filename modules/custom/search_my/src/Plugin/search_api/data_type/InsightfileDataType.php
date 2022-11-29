<?php

namespace Drupal\insight_search\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides an integer data type.
 *
 * @SearchApiDataType(
 *   id = "insightfile",
 *   label = @Translation("Insight Files"),
 *   description = @Translation("Insight Files."),
 *   default = "true"
 * )
 */
class InsightfileDataType extends DataTypePluginBase {

  /**
   * {@inheritdoc}
   */
  public function getValue($value) {
//    var_dump($value);die;
    return (array) $value;
  }

}
