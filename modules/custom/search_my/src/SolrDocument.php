<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Drupal\insight_search;
use Solarium\QueryType\Select\Result\Document;
/**
 * Description of SolrDocument
 *
 * @author sakreddy
 */
class SolrDocument extends Document {
  //put your code here
  public function __construct(array $fields) {
    $this->fields = SolrDocHelper::rewriteFields($fields);
  }
}