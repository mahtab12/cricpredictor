<?php

/**
 * @file
 * Social sharing links for nodes.
 */

namespace Drupal\cricket_api\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides the social sharing block for Farm Journal.
 *
 * @Block(
 *  id = "around_ground",
 *  admin_label = @Translation("Around Ground"),
 * )
 */
class AroundtheGroundBlock extends BlockBase {

  function build() {
    $client = \Drupal::httpClient();
    $response = $client->get('http://apinew.cricket.com.au/matches?completedLimit=2&inProgressLimit=3&upcomingLimit=3');
    $data = $response->getBody();
    $match = json_decode($data);
    $matches_list = $match->matchList->matches;
//    print_r($matches_list); die;
    $build['around_ground'] = [
      '#theme' => 'around_ground',
      '#data' => $matches_list,
    ];
    $build['#attached']['library'][] = 'cricket_api/cric_slick';
    $build['#cache']['max-age'] = 0;
    return $build;
  }

}
