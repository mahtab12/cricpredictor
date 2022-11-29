<?php

/**
 * @file
 * Social sharing links for nodes.
 */

namespace Drupal\cricket_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\cricket_api\Controller\MatchController;

/**
 * Provides the social sharing block for Farm Journal.
 *
 * @Block(
 *  id = "cricketmatch_topbar",
 *  admin_label = @Translation("cricketmatch topbar"),
 * )
 */
class CricketApi extends BlockBase {

  function build() {
    $matchController = new MatchController();
    $datas = $matchController->fetchApiScore();
    return $datas;
//    $matches_list = \Drupal::service('cricket_api.fetchScore')->getMatchList();
//    \Drupal::service('page_cache_kill_switch')->trigger();
//    $build['cricket_topbar'] = [
//      '#theme' => 'cricket_topbar',
//      '#data' => $matches_list,
//    ];
//    $build['#attached']['library'][] = 'cricket_api/cric_slick';
//
//    return $build;
  }

}
