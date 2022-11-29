<?php
/**
 * @file Provides a base class for forming document structure for different nodes.
 * @author Sunapu Siddharth <ssiddharth@dresources.com>
 */

namespace Drupal\insight_search;

use Drupal\content_tree\Topic\Chapter\Report\ReportInterface;
use Drupal\content_tree\Topic\Chapter\ChapterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\content_tree\Topic\Chapter\Report\Folder\FolderInterface;
use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class SearchDocumentCreation {

  public $report;
  public $chapter;
  public $entity_manager;
  public $folder;

    /**
   * Constructor function
   * @param ReportInterface  $report, EntityTypeManagerInterface $entityManager,ChapterInterface $chapter,FolderInterface $folder
   */
  public function __construct(EntityTypeManagerInterface $entityManager) {
    $this->entity_manager = $entityManager;
  }

    /**
   * Common array of fields which will be used in classes - SearchEmbedPptDocument and SearchReportDocumentCreation
   *
   * @param array $file
   * @return array
   */
  public $report_common_fields_argument = [
      'hash' => NULL,
      'index_id' => NULL,
      'parent_nodes' => NULL,
      'document_publish_date' => NULL,
      'document_nid' => NULL,
      'breadcrumb_titles' => NULL,
      'breadcrumb_links' => NULL,
      'document_sku' => NULL,
      'document_product_type_value' => NULL,
      'document_authors' => NULL,
      'topic_type' => NULL,
  ];

    /**
   * Forms the s3 bucket URL.
   *
   * @param array $filename
   * @return String $url
   */
  function get_file_s3_bucket_url($filename) {
    $s3fs_settings = \Drupal::config('s3fs.settings');
    $s3fs_region = $s3fs_settings->get('region');
    $s3fs_region = $s3fs_settings->get('region');
    $s3fs_access_key = \Drupal\Core\Site\Settings::get('s3fs.access_key');
    $s3fs_access_secret = \Drupal\Core\Site\Settings::get('s3fs.secret_key');
    $credentials = new Credentials($s3fs_access_key, $s3fs_access_secret);
    //$s3fs_bucket = 'ip-d8';
    $s3fs_bucket = $s3fs_settings->get('bucket');
    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => $s3fs_region,
        'credentials' => $credentials,
    ]);
    $url = $s3Client->getObjectUrl($s3fs_bucket, $filename, '5 minutes');
    return $url;
  }
  
    /**
    * Set meta tag manager.
    *
    * @param \Drupal\metatag\MetatagManager $metatagManager
    *   Meta tag manager.
    */
    public function setContentTreeService(ReportInterface $report, ChapterInterface $chapter, FolderInterface $folder) {
        $this->report = $report;
        $this->chapter = $chapter;
        $this->folder = $folder;
    }

}
