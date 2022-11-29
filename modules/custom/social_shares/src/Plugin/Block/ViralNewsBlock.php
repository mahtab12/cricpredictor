<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides a 'Powered' Block.
 *
 * @Block(
 *   id = "viral_news_block",
 *   admin_label = @Translation("Viral News"),
 *   category = @Translation("Viral News"),
 * )
 */
class ViralNewsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    $get_top_content['top'] = $this->getTopContent();
    $get_top_content['list'] = $this->getListContent();

    $build['viral_news'] = [
      '#theme' => 'viral_news',
      '#data' => $get_top_content,
    ];
    return $build;
  }

  public function getTopContent(){
//    print_r("Here");
    $conn = Database::getConnection();
    $query = $conn->select('node_field_data', 'n');
    $query->fields('n', array('nid', 'title'));
    $query->fields('nfi', array('field_image_target_id'));
    $query->leftJoin('node__field_article_type', 'fsc', 'fsc.entity_id = n.nid');
    $query->leftJoin('node__field_image', 'nfi', 'nfi.entity_id = n.nid');
    $query->condition('n.type', 'article');
    $query->condition('fsc.field_article_type_value', 'viral story');
    $query->condition('n.status', 1);
    $query->orderBy('n.created' , 'DESC');
    $query->range(0, 1);
    $result = $query->execute()->fetchAll();
    $data['nid'] = \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$result[0]->nid);
    $data['title'] = $result[0]->title;
    $fid = $result[0]->field_image_target_id;//The file ID
    $file = \Drupal\file\Entity\File::load($fid);
    $style = \Drupal::entityTypeManager()->getStorage('image_style')->load('featured_right');
    $data['path'] = $style->buildUrl($file->uri->value);

    return $data;
  }

  public function getListContent(){
    $conn = Database::getConnection();
    $query = $conn->select('node_field_data', 'n');
    $query->fields('n', array('nid', 'title'));
    $query->leftJoin('node__field_article_type', 'fsc', 'fsc.entity_id = n.nid');
    $query->condition('n.type', 'article');
    $query->condition('fsc.field_article_type_value', 'viral story');
    $query->condition('n.status', 1);
    $query->orderBy('n.created' , 'DESC');
    $query->range(1, 4);
    $result = $query->execute()->fetchAll();

    $i = 0;
    foreach ($result as $datas){
      $data[$i]['listnid'] = \Drupal::service('path_alias.manager')->getAliasByPath('/node/'.$datas->nid);
      $data[$i]['listtitle'] = $datas->title;
      $i++;
    }

    return $data;
  }

}