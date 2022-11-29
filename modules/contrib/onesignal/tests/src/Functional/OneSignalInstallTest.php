<?php

namespace Drupal\Tests\onesignal\Functional;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Test the OneSignal module install without errors.
 *
 * @group onesignal
 */
class OneSignalInstallTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['onesignal'];

  /**
   * Assert that the onesignal module installed correctly.
   */
  public function testModuleInstalls() {
    // If we get here, then the module was successfully installed during the
    // setUp phase without throwing any Exceptions. Assert that TRUE is true,
    // so at least one assertion runs, and then exit.
    $this->assertTrue(TRUE, 'Module installed correctly.');
  }

}
