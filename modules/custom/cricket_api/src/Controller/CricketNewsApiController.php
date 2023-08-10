<?php

namespace Drupal\cricket_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Serialization\Json;


/**
 * Returns responses for Type Ahead Search.
 */
class CricketNewsApiController extends ControllerBase {

  public function getTitle(Request $request){
    $title = 'Latest Cricket News';
    return $title;
  }

  public function getNews(Request $request) {
    $client = \Drupal::httpClient();
    try {
      $rapid_key = \Drupal::config('cricket_api.settings')->get('client_id');
     // dd($rapid_key);
      $rapid_host = \Drupal::config('cricket_api.settings')->get('client_secret');
      //dd($rapid_host);
     // $slogan = \Drupal::config('cricket_api.settings')->get('client_id');
      //dd($slogan);
      $response = \Drupal::httpClient()->get('https://cricbuzz-cricket.p.rapidapi.com/news/v1/index', [
        'headers' => [
          'X-RapidAPI-Key' => $rapid_key,
          'X-RapidAPI-Host' => $rapid_host,
        ],
      ]);
      $data = (string)$response->getBody()->getContents();
      
      $decoded = Json::decode($data);
     // dd($decoded);
      $story_list = $decoded['storyList'];
     // dd($story_list);
    } catch (\Exception $e) {

    }
    $build['series_listing'] = [
      '#theme' => 'news_list',
      '#data' => $story_list,
    ];
    $build['#attached']['library'] = 'cricket_api/cricket_api';
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  public function getNewsDetails(Request $request, $newsId){
    try {
      $response = \Drupal::httpClient()->get('https://cricbuzz-cricket.p.rapidapi.com/news/v1/detail/'.$newsId, [
        'headers' => [
          'X-RapidAPI-Key' => 'kQrkuIlXYBmsh0oaCaCuqZQw74Zap14Z8idjsnn2qB5zjCYk0j',
          'X-RapidAPI-Host' => 'cricbuzz-cricket.p.rapidapi.com',
        ],
      ]);
      $data = (string)$response->getBody()->getContents();
      $decoded = Json::decode($data);
     // dd($decoded);
      $coverImageId = $decoded['coverImage']['id'];
      $image = $this->getCricketImageById($coverImageId, 'det', 'high');
      $decoded['coverImage']['imgurl'] = $image;
    } catch (\Exception $e) {

    }

    $build['news_details'] = [
      '#theme' => 'news_details',
      '#data' => $decoded,
    ];
    $build['#attached']['library'] = 'cricket_api/cricket_api';
    $build['#cache']['max-age'] = 0;
    return $build;
  }

  public function getNewsTitle(Request $request, $newsId){
    try {
      $response = \Drupal::httpClient()->get('https://cricbuzz-cricket.p.rapidapi.com/news/v1/detail/'.$newsId, [
        'headers' => [
          'X-RapidAPI-Key' => 'kQrkuIlXYBmsh0oaCaCuqZQw74Zap14Z8idjsnn2qB5zjCYk0j',
          'X-RapidAPI-Host' => 'cricbuzz-cricket.p.rapidapi.com',
        ],
      ]);
      $data = (string)$response->getBody()->getContents();
      $decoded = Json::decode($data);
    } catch (\Exception $e) {

    }
    return $decoded['headline'];
  }

  public function getCricketImageById($imageId, $image_style, $quality) {
    $response = \Drupal::httpClient()->get('https://cricbuzz-cricket.p.rapidapi.com/img/v1/i1/c'.$imageId.'/i.jpg', [
      'headers' => [
        'X-RapidAPI-Key' => 'kQrkuIlXYBmsh0oaCaCuqZQw74Zap14Z8idjsnn2qB5zjCYk0j',
        'X-RapidAPI-Host' => 'cricbuzz-cricket.p.rapidapi.com',
      ],
      'query' => [
        'p' => $image_style,
        'd' => $quality,
      ],
    ]);
    $data = (string)$response->getBody()->getContents();
    $decoded = base64_encode($data);
    $imageDataUri = "data:image/jpeg;base64," . $decoded;
    return $imageDataUri;
  }
}
