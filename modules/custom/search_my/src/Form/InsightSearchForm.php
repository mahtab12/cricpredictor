<?php

namespace Drupal\insight_search\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Html;
use Drupal\insights_frontpage\Controller;

/**
 * Class SearchForm.
 */
class InsightSearchForm extends FormBase
{


  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'insight_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

      //Getting page_type for report library page
      $query_params = \Drupal::request()->query;
      $page_type = $this->insight_platform_search_page_type($query_params);

      $search_keyword = NULL;
      if($query_params->has('query')) {
        $search_keyword = $query_params->get('query');
      }
      $user_entity = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $user_last_visited = isset($user_entity->get('field_last_visited_business_unit')->value) ? $user_entity->get('field_last_visited_business_unit')->value : 'biopharma';
      $form['#theme'] = 'insight_search_form';

      $form['#action'] = '/search';
      $form['#method'] = 'get';

      $form['#attributes'] = [
                'class' => ["navbar-form has-feedback no-label navbar-collapse collapse in navbar-responsive-search $user_last_visited"]
      ];

      //$form['business_unit'] = $user_last_visited;
      $options = [
        'biopharma' => 'Biopharma <span class="smalltext">Disease, Drug & Company</span>',
        'medtech' => 'Medtech <span class="smalltext">By Millennium Research Group</span>',
        'digital' => 'Digital <span class="smalltext">By Manhattan Research</span>',
        'usma' => 'US Market Access <span class="smalltext">By HealthLeaders-InterStudy</span>',
      ];
      
      
      
      if ($page_type == 'library') {
        $options['library'] = 'Report Library';
        $user_last_visited = 'library';
        $user_selected_tab = 'biopharma';

        $user_last_visited_bu = $user_entity->get('field_last_visited_business_unit')->value;
        if (!empty($user_selected_tab) && $user_last_visited_bu !== $user_selected_tab) {
          $userService = \Drupal::service('insights_service.user')->saveUserLastVisitedBU($user_selected_tab);
          // $user_entity->set('field_last_visited_business_unit', $user_selected_tab);
          // $user_entity->save();
        }
      }

      $current_url = \Drupal::request()->getRequestUri();
      $business_unit =  array_filter(explode('/',$current_url));
      $placeholder_text = preg_replace('/<span[^>]*>.*?<\/span>/i', '', $options[$user_last_visited]);
      if (in_array(arg(0), homepage_urls) && !arg(1)) {
        $placeholder_padding = (($user_last_visited === 'biopharma' || $user_last_visited === '') ? 212 : (($user_last_visited === 'medtech') ? 157 : 212));
      }
      else {
        $placeholder_padding = ($user_last_visited === 'library' ? 120 : (($user_last_visited === 'biopharma' || $user_last_visited === '') ? 157 : (($user_last_visited === 'medtech') ? 139 : 125)));
        if ($user_last_visited === 'usma') {
          $placeholder_padding = 125;
        }
        if(count($business_unit) == 1 && current($business_unit) == 'usma') { //in_array(current($business_unit), homepage_urls)
          $placeholder_padding = 172;
        }
      }
      $placeholder = preg_replace('/<span[^>]*>.*?<\/span>/i', '', $options[$user_last_visited]);

      $form['query'] = array(
        '#id' => Html::getUniqueId('insight_platform_search'),
        '#type' => 'textfield',
        '#default_value' => !is_null($search_keyword) ? $search_keyword : '',
        '#size' => 30,
        '#maxlength' => 128,
        '#theme_wrappers' => array(),
        '#attributes' => array(
          'class' => array('auto_submit', 'form-control', 'hasclear', 'nav-search-box', 'form-text','form-autocomplete'),
          'placeholder' => t('Search within ' . $placeholder),
          'id' => 'insight-platform-search',
          'style' => 'padding-left: ' . $placeholder_padding . 'px;',
          'autocomplete' => 'off'
        ),
        '#suffix' => '<span class="clearer fa fa-times-circle fa-close advance-search-clear form-control-feedback hide"></span>',
      );

    //Autocomplete settings
    $search_storage = \Drupal::entityTypeManager()
      ->getStorage('search_api_autocomplete_search');
    $search = $search_storage->loadBySearchPlugin('insight_search_autocomplete');

    if ($search && $search->status()) {
      \Drupal::getContainer()
        ->get('search_api_autocomplete.helper')
        ->alterElement($form['query'], $search);
    }
    
    
    // Adding condition for for report library
          if(in_array($page_type, array('library','digital')))
          {
           $business_unit =  \Drupal::request()->query->get('search_bunit');
            if(!in_array($business_unit, array('biopharma','medtech'))) {
              $form['category'] = [
                '#type' => 'hidden',
                '#default_value' => 'library',
               ];
            }
          }

      $form['business_filters'] = [
        '#type' => 'select',
        '#options' => $options,
        '#default_value' => $user_last_visited,
        '#theme_wrappers' => [],
        '#placeholder' => $placeholder,
        '#attributes' => [
          'id' => 'edit-select',
          'class' => ['nav-search-select'],
        ],
      ];
     

      $form['search_bunit'] = array(
        '#type' => 'hidden',
        '#required' => FALSE,
        '#default_value' => $user_last_visited,
        '#attributes' => [
          'id' => 'search_bunit'
        ]
      );

      $form['search_autocomplete'] = array(
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'id' => 'search_autocomplete'
        ]
      );

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
          '#type' => 'button',
          '#name' => '',
          '#attributes' => [
              'class' => ['btn', 'btn-primary', 'nav-search-button']
          ],
      ];

      $form['#attached']['library'][] = 'insight_search/autosearch_header';
      $form['#attached']['library'][] = 'insight_search/insight_auto_suggestion';
      $form['#cache'] = ['max-age' => 0];


      return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

  }

  /**
   * Insight platform search page type.
   *
   * @param array $params
   *
   * @return string $page_type
  */
  public function insight_platform_search_page_type($params) {
    $page_type = NULL;
    $library = $params->get('category');
    if (isset($library) && $library == 'library') {
      $page_type = 'library';
    }
    return $page_type;
  }

}