<?php

namespace Drupal\insight_search\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
//use Drupal\search_api\Plugin\search_api\processor\Property\RenderedItemProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;

/**
 * Adds an additional field containing the rendered item.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Property\RenderedItemProperty
 *
 * @SearchApiProcessor(
 *   id = "children",
 *   label = @Translation("Deeply nested Documents"),
 *   description = @Translation("Add all the nested entities referenced deeply by a field."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class NestedChildren extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];
//dump($datasource->getEntityTypeId());
    if ($datasource && $datasource->getEntityTypeId() == 'node') {
      $definition = [
          'label' => $this->t('Nested Children(DRG custom)'),
          'description' => $this->t('Update Later'),
          'type' => 'children',
          'processor_id' => $this->getPluginId(),
          'is_list' => true,
      ];
      $properties['children'] = new \Drupal\insight_search\Plugin\search_api\processor\Property\ChildDocumentProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    $field = $item->getField('children');
    $config = $field->getConfiguration();
    $this->item = $item;
    $data = $this->getAllreportChildNodes($node, $config['field']);
    if (!empty($data)) {
      $field->addValue($data);
    }
  }

  public function getAllreportChildNodes(\Drupal\node\NodeInterface $report, $refField) {
    $contents = $this->loadChild($report->get($refField)->referencedEntities(), $refField, 0);
    return $contents;
  }

  private function loadChild(array $nodes, $refField, $level = 0, $l1 = null, $l2 = null) {
    $return = [];
    foreach ($nodes as $node) {
      //For TOC search
      $l1 = $level == 0 ? $node : $l1;
      $l2 = $level == 1 ? $node : $l2;
      if ($node->bundle() == 'folder') {
        $contents = $this->loadChild($node->get($refField)->referencedEntities(), $refField, $level + 1, $l1, $l2);
        if ($contents) {
          $folder = $this->getDisplay($node, $level + 1, $l1, $l2);
          $folder['children'] = $contents;
          $return[] = $folder;
        }
      } else {
        $return[] = $this->getDisplay($node, $level + 1, $l1, $l2);
      }
    }
    return $return;
  }

  private function getDisplay($entity, $level, $l1, $l2) {
    $display = \Drupal::entityTypeManager()
            ->getStorage('entity_view_display')
            ->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.search_index');
    if (!$display) {
      $display = \Drupal::entityTypeManager()
              ->getStorage('entity_view_display')
              ->load($entity->getEntityTypeId() . '.' . $entity->bundle() . '.default');
    }

    foreach ($display->getComponents() as $field => $c) {
      if (!$entity->hasField($field)) {
        continue;
      }
      $log['id'] = $this->item->getId() . '-' . $entity->id();
      $log['ss_type'] = $entity->bundle();
      $log['its_level'] = $level;
      $log['its_nid'] = $entity->id();
      $log['its_report_id'] = $this->item->getOriginalObject()->getValue()->id();
      $log['is_field_csg_nid'] = $this->item->getOriginalObject()->getValue()->id();
      $log['hash'] = \Drupal\search_api_solr\Utility\Utility::getSiteHash();
      $index = $this->item->getIndex();
      $log['index_id'] = $index->id();
      $server = $index->getServerInstance()->getBackend();
      $log[$server->getSolrFieldNames($index)['search_api_datasource']] = $this->item->getDatasourceId();
//Considering only the level 1 and 2 for toc search.
      if ($l1) {
        $log['is_l1_nid'] = $l1->id();
      }
      if ($l2) {
        $log['is_l1_nid'] = $l1->id();
        $log['is_l2_nid'] = $l2->id();
      }
      if ($entity->bundle() != 'folder') {
        $log['is_cs_nid'] = $entity->id();
      }
      if (!$entity->get($field)->isEmpty()) {
        $fieldConf = $entity->get($field);
        if ($fieldConf->getFieldDefinition()->getType() == 'entity_reference') {
//        $log['sm_'.$field] = array_map(function($ent) {
//          return $ent->label();
//        }, $fieldConf->referencedEntities());
        } else {
          $value = array_map(function($ent) {
            return isset($ent['value']) ? $ent['value'] : false;
          }, $fieldConf->getValue());
//          $name = count($value) > 1 ? 'twm_' . $field : 'tws_' . $field;
//          $name = 'tf_'.$field;
          $name = $this->fieldMap('tf_' . $field);
          $log[$name] = $value;
        }
      }
    }
    return $log;
  }

  private function fieldMap(string $field) {
    $fields = [
        'tf_field_filtered_body' => 'tf_body_field',
    ];
    return isset($fields[$field]) ? $fields[$field] : $field;
  }

}
