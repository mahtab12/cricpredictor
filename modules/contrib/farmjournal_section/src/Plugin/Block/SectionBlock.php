<?php

namespace Drupal\farmjournal_section\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media_entity\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\TermStorage;
use Drupal\image\Entity\ImageStyle;
use Drupal\bynder\BynderApi;
use Bynder\Api\BynderApiFactory;


/**
 * Provides a 'SectionBlock' block.
 *
 * @Block(
 *  id = "section_block",
 *  admin_label = @Translation("Section block"),
 * )
 */
class SectionBlock extends BlockBase
{
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration()
  {
    return [
        'category_one' => $this->t(''),
        'category_two' => $this->t(''),
        'category_three' => $this->t(''),
        'ad_one' => $this->t(''),
        'ad_two' => $this->t(''),
        'ad_three' => $this->t(''),
        'cat_one_label' => $this->t(''),
        'cat_two_label' => $this->t(''),
        'cat_three_label' => $this->t(''),
        'cat_one_child' => $this->t(''),
        'cat_two_child' => $this->t(''),
        'cat_three_child' => $this->t(''),
      ] + parent::defaultConfiguration();

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state)
  {
    $options = [];
    $terms = \Drupal::service('entity_type.manager')
      ->getStorage("taxonomy_term")
      ->loadTree('category', 0, NULL, TRUE);
    /** @var Term $term */
    foreach ($terms as $term) {
      //      $parents = array_values($term->parents);
      $options[$term->id()] = str_repeat('- ', $term->depth) . $term->label();
    }
    $config = \Drupal::config('farmjournal_dfp.settings');
    if ($config) {
      $website = $config->get('website_selection');
      $form['cat_1'] = [
        '#type' => 'fieldset',
        '#title' => t('Category 1 Settings'),
        '#weight' => 5,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];
      $form['cat_1']['category_one'] = [
        '#type' => 'select',
        '#title' => $this->t('Select category One'),
        '#description' => $this->t('Select 1st Category for Site Section'),
        '#weight' => '0',
        '#options' => $options,
        '#default_value' => $this->configuration['category_one']
      ];
      $form['cat_1']['ad_one'] = array(
        '#type' => 'select',
        '#title' => t('Select Ad'),
        '#description' => t('Select Ad to be added to the Category 1 Block'),
        '#default_value' => $this->configuration['ad_one'],
        '#options' => (function_exists("_farmjournal_dfp_get_ads_for_blocks"))?_farmjournal_dfp_get_ads_for_blocks($website):""
      );
      $form['cat_1']['cat_one_label'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom Heading'),
        '#description' => t('Give Custom Heading. If Empty then selected category will be the Heading'),
        '#default_value' => $this->configuration['cat_one_label'],
      );
      $form['cat_1']['cat_one_child'] = array(
        '#type' => 'checkbox',
        '#title' => t('Include Child Terms'),
        '#description' => t('Check this to include Child Terms'),
        '#default_value' => $this->configuration['cat_one_child']
      );
      $form['cat_2'] = [
        '#type' => 'fieldset',
        '#title' => t('Category 2 Settings'),
        '#weight' => 5,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];
      $form['cat_2']['category_two'] = [
        '#type' => 'select',
        '#title' => $this->t('Select category Two'),
        '#description' => $this->t('Select 2nd Category for Site Section'),
        '#weight' => '0',
        '#options' => $options,
        '#default_value' => $this->configuration['category_two']
      ];
      $form['cat_2']['ad_two'] = array(
        '#type' => 'select',
        '#title' => t('Select Ad'),
        '#description' => t('Select Ad to be added to the Category 2 Block'),
        '#default_value' => $this->configuration['ad_two'],
        '#options' => (function_exists("_farmjournal_dfp_get_ads_for_blocks"))?_farmjournal_dfp_get_ads_for_blocks($website):""
      );
      $form['cat_2']['cat_two_label'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom Heading'),
        '#description' => t('Give Custom Heading. If Empty then selected category will be the Heading'),
        '#default_value' => $this->configuration['cat_two_label'],
      );
      $form['cat_2']['cat_two_child'] = array(
        '#type' => 'checkbox',
        '#title' => t('Include Child Terms'),
        '#description' => t('Check this to include Child Terms'),
        '#default_value' => $this->configuration['cat_two_child']
      );
      $form['cat_3'] = [
        '#type' => 'fieldset',
        '#title' => t('Category 3 Settings'),
        '#weight' => 5,
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];
      $form['cat_3']['category_three'] = [
        '#type' => 'select',
        '#title' => $this->t('Select category Three'),
        '#description' => $this->t('Select 3rd Category for Site Section'),
        '#weight' => '0',
        '#options' => $options,
        '#default_value' => $this->configuration['category_three']
      ];
      $form['cat_3']['ad_three'] = array(
        '#type' => 'select',
        '#title' => t('Select Ad'),
        '#description' => t('Select Ad to be added to the Category 3 Block'),
        '#default_value' => $this->configuration['ad_three'],
        '#options' => (function_exists("_farmjournal_dfp_get_ads_for_blocks"))?_farmjournal_dfp_get_ads_for_blocks($website):""
      );
      $form['cat_3']['cat_three_label'] = array(
        '#type' => 'textfield',
        '#title' => t('Custom Heading'),
        '#description' => t('Give Custom Heading. If Empty then selected category will be the Heading'),
        '#default_value' => $this->configuration['cat_three_label'],
      );
      $form['cat_3']['cat_three_child'] = array(
        '#type' => 'checkbox',
        '#title' => t('Include Child Terms'),
        '#description' => t('Check this to include Child Terms'),
        '#default_value' => $this->configuration['cat_three_child']
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state)
  {
    $cat_1 =$form_state->getValue('cat_1');
    $cat_2 =$form_state->getValue('cat_2');
    $cat_3 =$form_state->getValue('cat_3');
    $this->setConfigurationValue('category_one', $cat_1['category_one']);
    $this->setConfigurationValue('ad_one', $cat_1['ad_one']);
    $this->setConfigurationValue('cat_one_label',$cat_1['cat_one_label']);
    $this->setConfigurationValue('cat_one_child',$cat_1['cat_one_child']);

    $this->setConfigurationValue('category_two', $cat_2['category_two']);
    $this->setConfigurationValue('ad_two', $cat_2['ad_two']);
    $this->setConfigurationValue('cat_two_label',$cat_2['cat_two_label']);
    $this->setConfigurationValue('cat_two_child',$cat_2['cat_two_child']);

    $this->setConfigurationValue('category_three', $cat_3['category_three']);
    $this->setConfigurationValue('ad_three', $cat_3['ad_three']);
    $this->setConfigurationValue('cat_three_label',$cat_3['cat_three_label']);
    $this->setConfigurationValue('cat_three_child',$cat_3['cat_three_child']);
  }

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    $build = $data = $image_data = [];
    $categories = [
      $this->configuration['category_one'],
      $this->configuration['category_two'],
      $this->configuration['category_three']
    ];
    foreach ($categories as $key => $category) {
      switch($key) {
        case 0:
          $ad = (function_exists("_farmjournal_dfp_get_ads_for_blocks"))?_farmjournal_dfp_get_ads_for_blocks($website):"";
          $label = $this->configuration['cat_one_label'];
          $child = $this->configuration['cat_one_child'];
          break;
        case 1:
          $ad = (function_exists("_farmjournal_dfp_get_ads_for_blocks"))?_farmjournal_dfp_get_ads_for_blocks($website):"";
          $label = $this->configuration['cat_two_label'];
          $child = $this->configuration['cat_two_child'];
          break;
        case 2:
          $ad = (function_exists("_farmjournal_dfp_get_ads_for_blocks"))?_farmjournal_dfp_get_ads_for_blocks($website):"";
          $label = $this->configuration['cat_three_label'];
          $child = $this->configuration['cat_three_child'];
          break;
      }
      $term = Term::load($category);
      if(!empty($term)) {
        $node_data = $this->getContents($term, 1, 0, TRUE, $child);
        $image_data = [];
        if (!empty($node_data)) {
          $image_data = $this->getNodeData($node_data);
        }
        else {
          // fetching normal content. not featured
          $node_data = $this->getContents($term, 1, 0, FALSE, $child);
          if (!empty($node_data)) {
            $image_data = $this->getNodeData($node_data, FALSE);
          }
        }
        $data[$term->label()] = [
          'label' => ($label != '') ? $label : $term->label(),
          'tid' => $term->id(),
          'nodes' => $this->getContents($term, 4, 1, FALSE, $child),
          'imageData' => $image_data,
          'ad' => $ad
        ];
      }
    }
    $build['#theme'] = 'farmjournal_section_block';
    $build['#data'] = $data;
    $build['#cache'] = [
      'max-age' => 0,
    ];
    return $build;

  }

  /**
   * Load Node Data
   * @param $node
   * @param bool $featured
   * @return array
   */
  public function getNodeData($node,$featured = TRUE)
  {
    $imageStyle = '';
    $nodeData = Node::load($node[0]['nid']);
    $default_image_path = 'public://placeholder.jpg';
    if($featured == TRUE) {
      //load featured image
      $featured = $nodeData->get('field_featured_image')->getValue();
      if (!empty($featured)) {
        //      $file = File::load($featured[0]['target_id']);
        //      if($file->getFileUri() != NULL) {
        //        $imageStyle = ImageStyle::load('site_section')
        //          ->buildUrl($file->getFileUri());
        //      }
        //        $image = Media::load($featured[0]['target_id']);
        /*$teaser = $nodeData->get('field_teaser_image')->getValue();
        if (!empty($teaser)) {
          $image = Media::load($teaser[0]['target_id']);
          if (isset($image->field_image->entity)) {
            $imageStyle = ImageStyle::load('site_section')
              ->buildUrl($image->field_image->entity->getFileUri());
          }

        }
        else {
          $primary_image = $nodeData->get('field_image')->getValue();
          if (!empty($primary_image)) {
            $image = Media::load($primary_image[0]['target_id']);
            if (isset($image->field_image->entity)) {
              $imageStyle = ImageStyle::load('site_section')
                ->buildUrl($image->field_image->entity->getFileUri());
            }
          }
          else {
            $imageStyle = ImageStyle::load('site_section')->buildUrl($default_image_path);
          }
        }*/
        $featured = $nodeData->get('field_featured_image')->getValue();
//        print '<pre>'; print_r($featured);exit;
        if(!empty($featured)) {
          //print 'if';
          $image = Media::load($featured[0]['target_id']);
//          print '<pre>'; print_r($featured[0]['target_id']);exit;
          if($image->bundle() == 'bynder') {
            $bynder_id = $this->getBynderId($featured[0]['target_id']);
            $imageStyle = $this->getBynderImage($bynder_id);
          }
          if($image->bundle() == 'image') {
            if (isset($image->field_image->entity)) {
              $imageStyle = ImageStyle::load('site_section')
                ->buildUrl($image->field_image->entity->getFileUri());

            }
          }
        }
        else {
          $primary_image = $nodeData->get('field_image')->getValue();
          if (!empty($primary_image)) {
            $image = Media::load($primary_image[0]['target_id']);
            if($image->bundle() == 'bynder') {
              $bynder_id = $this->getBynderId($primary_image[0]['target_id']);
              $imageStyle = $this->getBynderImage($bynder_id);
            }
            if($image->bundle() == 'image') {
              if (isset($image->field_image->entity)) {
                $imageStyle = ImageStyle::load('site_section')
                  ->buildUrl($image->field_image->entity->getFileUri());
              }
            }
          }
          else {
            $imageStyle = ImageStyle::load('site_section')->buildUrl($default_image_path);
          }
        }
      }
      else {
        $teaser = $nodeData->get('field_teaser_image')->getValue();
        if (!empty($teaser)) {
          $image = Media::load($teaser[0]['target_id']);
          if($image->bundle() == 'bynder') {
            $bynder_id = $this->getBynderId($teaser[0]['target_id']);
            $imageStyle = $this->getBynderImage($bynder_id);
          }
          if($image->bundle() == 'image') {
            if (isset($image->field_image->entity)) {
              $imageStyle = ImageStyle::load('site_section')
                ->buildUrl($image->field_image->entity->getFileUri());
            }
          }
        }
        else {
          $primary_image = $nodeData->get('field_image')->getValue();
          if (!empty($primary_image)) {
            $image = Media::load($primary_image[0]['target_id']);
            if($image->bundle() == 'bynder') {
              $bynder_id = $this->getBynderId($primary_image[0]['target_id']);
              $imageStyle = $this->getBynderImage($bynder_id);
            }
            if($image->bundle() == 'image') {
              if (isset($image->field_image->entity)) {
                $imageStyle = ImageStyle::load('site_section')
                  ->buildUrl($image->field_image->entity->getFileUri());
              }
            }
          }
          else {
            $imageStyle = ImageStyle::load('site_section')->buildUrl($default_image_path);
          }
        }

      }
    }
    else {
      //load teaser image
      $teaser = $nodeData->get('field_teaser_image')->getValue();
      if (!empty($teaser)) {
        $image = Media::load($teaser[0]['target_id']);
        if($image->bundle() == 'bynder') {
          $bynder_id = $this->getBynderId($teaser[0]['target_id']);
          $imageStyle = $this->getBynderImage($bynder_id);
        }
        if($image->bundle() == 'image') {
          if (isset($image->field_image->entity)) {
            $imageStyle = ImageStyle::load('site_section')
              ->buildUrl($image->field_image->entity->getFileUri());
          }
        }
      }
      else {
        $imageStyle = ImageStyle::load('site_section')->buildUrl($default_image_path);
      }
      //      else{
      //        $primary_image = $nodeData->get('field_image')->getValue();
      ////        print_r($primary_image);exit;
      //        if(!empty($primary_image)){
      //          $image = Media::load($primary_image[0]['target_id']);
      //          if (isset($image->field_image->entity)) {
      //            $imageStyle = ImageStyle::load('site_section')
      //              ->buildUrl($image->field_image->entity->getFileUri());
      //          }
      //        }
      //      }
    }
    $nodeArray = [
      'nid' => $node[0]['nid'],
      'title' => ucfirst($nodeData->getTitle()),
      'img_link' => $imageStyle
    ];
    return $nodeArray;
  }

  /**
   * @param $term
   * @param int $limit
   * @param int $range
   * @param boolean $featured
   * @param boolean $child
   * Get Content Based on term
   */
  public function getContents($term, $limit = NULL, $range = NULL, $featured = TRUE, $child = FALSE)
  {
    $db = \Drupal::database();

    //    $query = $db->select('node_field_data', 'nfd');
    //    $query->fields('nfd', ['nid', 'created', 'title']);
    //    $query->leftJoin('node__field_category', 'nfc', 'nfd.nid=nfc.entity_id');
    //    if($featured == TRUE) {
    //      $query->leftJoin('node__field_featured', 'nff', 'nfd.nid=nff.entity_id');
    //      $query->where('nfc.field_category_target_id = :tid AND nfd.status = 1 AND nfd.type = :type AND nff.field_featured_value = 1', [':tid' => $term->id(), 'type' => 'article']);
    //    }
    //    else {
    //      $query->where('nfc.field_category_target_id = :tid AND nfd.status = 1 AND nfd.type = :type', [':tid' => $term->id(), 'type' => 'article']);
    //    }
    //    $query->orderBy('nfd.created', 'DESC');
    //    $query->range($range, $limit);
    //
    //    $nodes = $query->execute()->fetchAll();
    //    return $nodes;
    $node_list = $tids =[];
    if ($child == 1) {
      //      print 'child';
      //do not delete
      //getting parent, if no parent push to array
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadParents($term->id());
      if (!empty($terms)) {
        $parent = [];
        foreach ($terms as $t) {
          array_push($tids, $t->tid->value);
          $childs = \Drupal::entityTypeManager()
            ->getStorage('taxonomy_term')
            ->loadChildren($t->tid->value);
          if (!empty($childs)) {
            foreach ($childs as $children) {
              array_push($tids, $children->tid->value);
            }
          }
          else
            array_push($tids, $t->tid->value);
        }
      }
      else {
        //        print 'no-parent';
        //no parent, find children and add to array
        array_push($tids, $term->id());
        $childs = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadChildren($term->id());
        if (!empty($childs)) {
          foreach ($childs as $children) {
            array_push($tids, $children->tid->value);
          }
        }
        else
          array_push($tids, $term->id());
      }
    }
    else {
      //      print 'no child';
      $tids[] = $term->id();
    }
    //Query to get nodes
    if (!empty($tids)) {
      $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('type', 'article')
        ->condition('field_category', $tids, 'IN')
        ->range($range,$limit)
        ->sort('created','DESC');
      $nids = $query->execute();

      $nodes = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->loadMultiple($nids);
      foreach ($nodes as $node) {
        $node_list[] = [
          'nid' => $node->id(),
          'title' => $node->get('title')->value,
          'created' => $node->get('created')->value
        ];
      }
    }

    return $node_list;
  }
  
    /**
   * Get image from Bynder ID
   * @param $bynder_id
   * @return string
   */
  public function getBynderImage($bynder_id) {
    $settings = [
      'consumerKey' => \Drupal::config('bynder.settings')->get('consumer_key'),
      'consumerSecret' => \Drupal::config('bynder.settings')->get('consumer_secret'),
      'token' => \Drupal::config('bynder.settings')->get('token'),
      'tokenSecret' => \Drupal::config('bynder.settings')->get('token_secret'),
      'baseUrl' =>\Drupal::config('bynder.settings')->get('account_domain'),
    ];
    try {
      $bynderApi = BynderApiFactory::create($settings);
      $assetBankManager = $bynderApi->getAssetBankManager();
      $mediaItemPromise = $assetBankManager->getMediaInfo($bynder_id);
      $mediaItem = $mediaItemPromise->wait();
      if (!empty($mediaItem)) {
        $style_type = 'Site Section';
        return $mediaItem['thumbnails'][$style_type];
      }
    }
    catch(\Exception $e) {
      //No Message added
    }
  }

  /**
   * Get Bynder ID for getting Image
   * @param $entity_id
   * @return string
   */
  public function getBynderId($entity_id) {
    $bynder_id = '';
    $db_conn = \Drupal::database();
    $query = $db_conn->select('media__field_bynder_id','ap');
    $query->addField('ap','field_bynder_id_value');
    $query->condition('entity_id',$entity_id);
    $bynder_id =  $query->execute()->fetchField();
    return $bynder_id;
  }
}
