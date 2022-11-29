<?php

namespace Drupal\cricket_api\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Type Ahead Search.
 */
class MatchController extends ControllerBase {

  public function getMatches() {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $client = \Drupal::httpClient();

    try {
      $response = $client->get('http://apinew.cricket.com.au/matches?completedLimit=10&inProgressLimit=10&upcomingLimit=10');
      $data = $response->getBody();
    }
    catch (RequestException $e) {
      watchdog_exception('my_module', $e->getMessage());
    }
    $match = json_decode($data);
    $matches_list = $match->matchList->matches;
//    print_r($matches_list); die;
    $i = 0;
    if(!empty( $matches_list )) {
      foreach ($matches_list as $match) {
        $result[$i]['match_id'] = $match->id;
        $result[$i]['match_num'] = $match->name;
        $result[$i]['series_image'] = $match->series->shieldImageUrl;
        $result[$i]['series_id'] = $match->series->id;
        $result[$i]['currentMatchState'] = $match->currentMatchState;
        $result[$i]['status'] = strtolower( $match->status );
        $result[$i]['venue'] = $match->venue->name;
        $result[$i]['series_name'] = $match->series->name;
        $result[$i]['away_team'] = $match->awayTeam->name;
        $result[$i]['logo_away_team'] = $match->awayTeam->logoUrl;
        $result[$i]['home_score'] = $match->scores->homeScore;
        $result[$i]['home_over'] = $match->scores->homeOvers;
        $result[$i]['away_score'] = $match->scores->awayScore;
        $result[$i]['away_over'] = $match->scores->awayOvers;
        $result[$i]['home_team'] = $match->homeTeam->name;
        $result[$i]['logo_home_team'] = $match->homeTeam->logoUrl;
        $result[$i]['match_date'] = $match->localStartDate ? $match->localStartDate : '' ;
        $result[$i]['match_time'] = $match->localStartTime ? $match->localStartTime : '';
        $result[$i]['match_date_time'] = $match->startDateTime;
        $result[$i]['home_team_color'] = $match->homeTeam->teamColour;
        $result[$i]['away_team_color'] = $match->awayTeam->teamColour;
        $result[$i]['away_short_name'] = $match->awayTeam->shortName;
        $result[$i]['home_short_name'] = $match->homeTeam->shortName;
        $result[$i]['summary_text'] = $match->matchSummaryText;
        $result[$i]['team_url'] = strtolower(str_replace(' ','-',$match->homeTeam->name)).'-vs-'.strtolower(str_replace(' ','-',$match->awayTeam->name));
        $i++;
      }
    }
//    print_r($result); die;
    $build['cricket_match'] = [
      '#theme' => 'cricket_match',
      '#data' => $result,
    ];
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  public function fetchApiScore(){
    $matches_list = \Drupal::service('cricket_api.fetchScore')->getMatchList();
    \Drupal::service('page_cache_kill_switch')->trigger();
    $build['cricket_topbar'] = [
      '#theme' => 'cricket_topbar',
      '#data' => $matches_list,
    ];
    $build['#attached']['library'][] = 'cricket_api/cric_slick';
    if (\Drupal::request()->isXmlHttpRequest()) {
//      \Drupal::logger('cricket_Score')->notice('<pre>'. print_r('Mahtab', TRUE).'</pre>');
      $resp = new AjaxResponse();

      $resp->addCommand(new ReplaceCommand('.site-slogan', $build));
      return $resp;
    }
    else {
      return $build;
    }
  }

//    $block_manager = \Drupal::service('plugin.manager.block');
//    $config = [];
//    $plugin_block = $block_manager->createInstance('cricketmatch_topbar', $config);
//
//    $render = $plugin_block->build();
//
//    \Drupal::logger("cricket")->notice("<pre>" . print_r($render, TRUE) .  "</pre>");
//
//    return $render;


}