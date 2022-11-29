<?php

namespace Drupal\insight_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;

/**
 * Provides route responses for search indexes.
 */
class IndexController extends ControllerBase {


  /**
   * @param $report_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function addIndex($report_id) {
    if(isset($report_id) && !empty($report_id)) {

      $entity = $this->entityTypeManager()->getStorage('node')->load($report_id);
      $langcode = $entity->langcode->value;
      if ($entity instanceof ContentEntityInterface) {
        $indexes = ContentEntity::getIndexesForEntity($entity);
        $datasource_id = 'entity:' . $entity->getEntityTypeId();
        $entity_info[] = $report_id . ':' . $langcode;
        foreach ($indexes as $index) {
          $index->trackItemsUpdated($datasource_id, $entity_info);
        }
      }
    }
  }

  /**
   * Delete the report index
   * @param $report_id
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deleteIndex($report_id) {
    if(isset($report_id) && !empty($report_id)) {

      // Loading the complete entity by report id
      $entity = $this->entityTypeManager()->getStorage('node')->load($report_id);
      $langcode  = $entity->langcode->value;

      if($entity instanceof ContentEntityInterface){

        // Getting indexes by Entity
        $indexes = ContentEntity::getIndexesForEntity($entity);
        $datasource_id = 'entity:' . $entity->getEntityTypeId();
        $entity_info[] = $report_id.':'.$langcode;
        foreach ($indexes as $index) {
          // Calling the Delete method to passing the information to Search API for delete
          $index->trackItemsDeleted($datasource_id, $entity_info);
        }
      }
    }
  }

}