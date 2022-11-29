<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Powered' Block.
 *
 * @Block(
 *   id = "powered_block",
 *   admin_label = @Translation("PoweredBlock"),
 *   category = @Translation("PoweredBlock"),
 * )
 */
class PoweredBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return array(
      '#type' => 'markup',
      '#markup' => 'Copyright © '.date('Y').', CricPredictor. All rights reserved.',
    );
  }

}