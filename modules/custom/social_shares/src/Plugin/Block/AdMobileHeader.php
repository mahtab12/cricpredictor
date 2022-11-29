<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Mobile Header Ad' Block.
 *
 * @Block(
 *   id = "mobile_header_ad",
 *   admin_label = @Translation("Mobile Header Ad"),
 *   category = @Translation("Mobile Header Ad"),
 * )
 */
class AdMobileHeader extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build['mobile_header'] = [
      '#theme' => 'mobile_header',
      '#data' => null,
    ];
    return $build;
  }
}