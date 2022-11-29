<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\insight_search\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Description of SearchControllerSubscriber
 *
 * @author sakreddy
 */
class SearchControllerSubscriber implements EventSubscriberInterface {

  protected $controllerCallables = [
      'usma' => ['\Drupal\insight_search\Controller\UsmaSearchController', 'render'],
      'biopharma' => ['\Drupal\insight_search\Controller\BiopharmaSearchController', 'search'],
      'medtech' => ['\Drupal\insight_search\Controller\MedtechSearchController', 'search'],
  ];

  //put your code here
  public static function getSubscribedEvents(): array {
    return [KernelEvents::CONTROLLER => 'resolveController'];
  }

  public function resolveController(FilterControllerEvent $event) {
    $route = \Drupal::routeMatch();
    if ($route->getRouteName() == 'insight_search.search') {
      $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
      $cookie = $user->get('field_last_visited_business_unit')->value;

      $report_lib = \Drupal::request()->get('category');
      if($report_lib == 'library'){
          $cookie = 'library';
      }
      if($cookie!='library') {
//      $originalController = $event->getController();
      if (isset($this->controllerCallables[$cookie])) {
        $callable = (function($cla) {
                  return [new $cla[0], $cla[1]];
                })($this->controllerCallables[$cookie]);
        if (is_callable($callable)) {
          $event->setController($callable);
        }
      }
    }
      
    }
  }

}
