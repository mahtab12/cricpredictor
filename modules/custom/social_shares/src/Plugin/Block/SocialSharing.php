<?php

/**
 * @file
 * Social sharing links for nodes.
 */

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides the social sharing block for Farm Journal.
 *
 * @Block(
 *  id = "social_shares",
 *  admin_label = @Translation("social sharing"),
 * )
 */
class SocialSharing extends BlockBase {

  function build() {
    $title = $summary = $image = NULL;
    $request = \Drupal::request();
    $url = Url::fromRoute('<current>');
    $url = $request->getHttpHost() . $url->toString();
    $url = urlencode($url);
    $node = \Drupal::routeMatch()->getParameter('node');
    if(is_object($node)) {
      $title = urlencode($node->title->value);
      $summary = (isset($node->body->value) && !empty($node->body->value)) ? urlencode(strip_tags(substr($node->body->value, 0, 250))) : urlencode("Farm Journal's Milk");
      $image = (isset($node->field_image->entity) && !empty($node->field_image->entity)) ? urlencode($node->field_image->entity->url()) : NULL;
    }
    $facebook_share = 'https://www.facebook.com/sharer/sharer.php?u=' . $url . '&t=' . $title;
    $twitter_share = 'https://twitter.com/intent/tweet?source=' . $url . '&text=' . $title . ':' . $url . '&via=';
    if ($image) {
      $pinterest_share = 'https://www.pinterest.com/pin/create/button/?url=' . $url . '&media=' . $image . '&description=' . $title;
    }
    else {
      $pinterest_share = 'https://www.pinterest.com/pin/create/button/?url=' . $url . '&description=' . $title;
    }
    $google_share = 'https://plus.google.com/share?url=' . $url;
    $linkedin_share = 'https://www.linkedin.com/shareArticle?mini=true&url=' . $url . '&title=' . $title . '&source=&summary=' . $summary;
    $stumbleupon = 'http://www.stumbleupon.com/submit?url=' . $url . '&title=' . $title;
    return array(
      '#type' => 'markup',
      '#theme' => 'social_shares',
      '#facebook' => $facebook_share,
      '#twitter' => $twitter_share,
      '#pininterest' => $pinterest_share,
      '#google' => $google_share,
      '#linkedin' => $linkedin_share,
      '#stumbleupon' => $stumbleupon,
      '#attached' => array(
        'library' => 'social_shares/social_shares',
      )
    );
  }

}
