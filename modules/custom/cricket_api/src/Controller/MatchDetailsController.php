<?php

namespace Drupal\cricket_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Type Ahead Search.
 */
class MatchDetailsController extends ControllerBase {

  public function getTitle(Request $request, $series_id = NULL, $match_id = NULL, $team_name = NULL){
    $url = "http://apinew.cricket.com.au/matches/{$match_id}/{$series_id}/live";
    $file = file_get_contents($url);
    $details = json_decode($file);
    $details = $details->liveMatch;
    $match_name = $details->meta->matchName;
    $match_type = $details->meta->matchType;
    $home_team = $details->matchDetail->homeTeam->name;
    $away_team = $details->matchDetail->awayTeam->name;
    $title = $home_team.' vs '.$away_team.', '.$match_type.', '.$match_name.'- Live Cricket Score';
    return $title;
  }

  public function getScores(Request $request, $series_id = NULL, $match_id = NULL, $team_name = NULL) {
    \Drupal::service('page_cache_kill_switch')->trigger();
    $url = "http://apinew.cricket.com.au/matches/{$match_id}/{$series_id}/live";
    $file = file_get_contents($url);
    $details = json_decode($file);
    $details = $details->liveMatch;
//    print_r($details); die;
    $match_scorecard = $details->scoreCard;
//    print_r($match_scorecard); die;

    $result['hasCompleted'] = $details->meta->hasCompleted;
    if($result['hasCompleted'] == 1){
      $result['homeTeamName'] = $details->matchDetail->homeTeam->name;
      $result['homeTeamScore'] = $details->matchDetail->scores->homeScore;
      $result['homeTeamOvers'] = $details->matchDetail->scores->homeOvers;
      $result['awayTeamName'] = $details->matchDetail->awayTeam->name;
      $result['awayTeamScore'] = $details->matchDetail->scores->awayScore;
      $result['awayTeamOvers'] = $details->matchDetail->scores->awayOvers;
      $result['mom'] = $details->awards->manOfTheMatchName;
      $result['most_runs_player'] = $details->awards->mostRunsAward->name;
      $result['most_runs'] = $details->awards->mostRunsAward->runs;
      $result['most_runs_balls'] = $details->awards->mostRunsAward->ballsFaced;
      $result['most_wicket_player'] = $details->awards->mostWicketsAward->name;
      $result['most_wicket'] = $details->awards->mostWicketsAward->wickets;
      $result['toss'] = $details->matchDetail->tossMessage;
    }

    $result['isLive'] = $details->meta->isMatchLive;
    $result['istobeplayed'] = $details->meta->isToBePlayed;
    $result['series_name'] = $details->meta->series->name;
    $result['venue_name'] = $details->meta->venue->name;
    $result['summary'] = $details->matchDetail->matchSummaryText;
    $result['status'] = $details->matchDetail->status;
    $start_date = $details->matchDetail->startDateTime;
    $result['matchTime'] = date("d-M-Y g:i a", strtotime($start_date));

    $currentBatters = $details->currentBatters;
    $i=0;
    $summary = [];
    foreach ($currentBatters as $value){
      $summary[$i]['currentBatsmanName'] = $value->name;
      $summary[$i]['currentBatsmanRuns'] = $value->runs;
      $summary[$i]['currentBatsmanBalls'] = $value->ballsFaced;
      $summary[$i]['currentBatsmanFours'] = $value->fours;
      $summary[$i]['currentBatsmanSix'] = $value->sixers;
      $summary[$i]['currentBatsmanstrikeRate'] = $value->strikeRate;
      $i++;
    }
//    print_r($summary); die;
    $currentBowl = $details->currentbowler;

    $currentBowler['name'] = $currentBowl->name;
    $currentBowler['maiden'] = $currentBowl->maiden;
    $currentBowler['bowlerOver'] = $currentBowl->bowlerOver;
    $currentBowler['economy'] = $currentBowl->economy;
    $currentBowler['runsAgainst'] = $currentBowl->runsAgainst;
    $currentBowler['wickets'] = $currentBowl->wickets;

    $commentry = "http://apinew.cricket.com.au/comments/{$match_id}/{$series_id}/?overLimit=8";
    $comments_details = file_get_contents($commentry);
    $comment = json_decode($comments_details);
    $comments = $comment->commentary;
//    print_r($comments);
    $build['match_details'] = [
      '#theme' => 'match_details',
      '#data' => $result,
      '#summary' => $summary,
      '#currentBowler' => $currentBowler,
      '#details' => $details,
      '#comments' => $comments,
    ];
    $build['#attached']['library'] = 'cricket_api/cricket_api';
    $build['#cache']['max-age'] = 0;
    return $build;
  }
}
