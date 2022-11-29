<?php

namespace Drupal\social_shares\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Foolow Us' Block.
 *
 * @Block(
 *   id = "follow_us",
 *   admin_label = @Translation("Follow Us"),
 *   category = @Translation("Follow Us"),
 * )
 */
class FollowUs extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
  	$markup = '<h4>FOLLOW US</h4><ul id="social"><li><div class="my-twitter"><a target="_blank" href="http://twitter.com/PredictorCric"><i class="fa fa-twitter fa-2x"></i> </a></div></li> 
	 	<li><div class="my-face">
	 		<a target="_blank" href="http://facebook.com/cricpredicto"><i class="fa fa-facebook fa-2x"></i> </a>
	 	</div></li>	
	 	<li><div class="my-insta">
	 		<a target="_blank" href="https://www.instagram.com/cricpredicto"><i class="fa fa-instagram fa-2x"></i> </a>
	 	</div></li>															 
	 </ul>';																									
    return array(
      '#type' => 'markup',
      '#markup' => $markup,
    );
  }
}