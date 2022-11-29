<?php

namespace Drupal\insight_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\insights_frontpage\Controller\InsightMarketThoughtLeadership;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\Component\Serialization\Json;


/**
 * Provides a My favorite editor page header.
 *
 * @Block(
 *  id = "insight_market_search_filter",
 *  admin_label = @Translation("Insight Search Filter"),
 * )
 */
class InsightMarketSearchFilter extends BlockBase
{

  /**
   * {@inheritdoc}
   */
  public function build()
  {
    // TODO: Implement build() method.

    $form = \Drupal::formBuilder()->getForm('Drupal\insight_search\Form\SearchFilterForm');
    
    return $form;
    $httpClient = \Drupal::service('insight_market_access.http_client');
    try {
      $request = $httpClient->request('get', '/api/macctaxonomytags/all', []);
    } catch (ClientException $ex) {
      throw new BadRequestHttpException('Not found');
    }
    $response = (string)$request->getBody();
    $content = Json::decode($response);

    $build = [
      "#theme" => 'insight_market_search_filter',
      '#states' => $content['states'],
      '#metrostations' => $content['metroareas'],
      '#payers' => $content['payers'],
      '#providers' => $content['providers'],
      '#products' => $content['products'],
      '#attached' => [
        'library' => ['insight_market_access/market-access-onboarding-modal', 'insight_market_access/market-access-search-unaccess'],
        'drupalSettings' => [
          'onbording' => [
            'mail' => \Drupal::currentUser()->getEmail(),
          ],
        ],
      ],
    ];
    return $build;
  }
}
