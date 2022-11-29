<?php

namespace Drupal\cricket_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Type Ahead Search.
 */
class PlayerDetailsController extends ControllerBase {

  public function getPlayerName(Request $request, $playerId = NULL){
    $title = 'Sachin Tendulkar';
    return $title;
  }

  public function getPlayerDetails(Request $request, $playerId = NULL) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $url = "http://apinew.cricket.com.au/players/{$playerId}/stats";
    $file = file_get_contents($url);
    $details = json_decode($file);
//    print_r($details); die;
    $build['player_details'] = [
      '#theme' => 'player_details',
      '#data' => $details,
    ];
    $build['#attached']['library'] = 'cricket_api/player_details';
    $build['#cache']['max-age'] = 0;
    return $build;
  }
}
