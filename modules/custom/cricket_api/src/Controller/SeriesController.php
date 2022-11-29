<?php

namespace Drupal\cricket_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Type Ahead Search.
 */
class SeriesController extends ControllerBase {

  public function getTitle(Request $request){
    $title = 'Series and Tournaments';
    return $title;
  }

  public function getList(Request $request) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $series = "http://apinew.cricket.com.au/series";
    $series_json = file_get_contents($series);
    $series_list = json_decode($series_json);
//    print_r($series_list);
    $build['series_listing'] = [
      '#theme' => 'series_listing',
      '#data' => $series_list,
    ];
    $build['#attached']['library'] = 'cricket_api/cricket_api';
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  public function getMatchBySeries(Request $request, $seriesId){
    \Drupal::service('page_cache_kill_switch')->trigger();
    $series_match = "http://apinew.cricket.com.au/matches/{$seriesId}";
    $series_json = file_get_contents($series_match);
    $series_match_list = json_decode($series_json);
    $series_matches = $series_match_list->matchList->matches;
//    print_r($series_match_list); die;
    $build['series_match_listing'] = [
      '#theme' => 'series_match_listing',
      '#data' => $series_matches,
    ];
    $build['#attached']['library'] = 'cricket_api/cricket_api';
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  public function getSeriesTitle(Request $request, $seriesId){
    $series_match = "http://apinew.cricket.com.au/matches/{$seriesId}";
    $series_json = file_get_contents($series_match);
    $series_match_list = json_decode($series_json);
    $series_matches = $series_match_list->matchList->matches;
    $title = $series_matches[0]->series->name;
    return $title;
  }
}