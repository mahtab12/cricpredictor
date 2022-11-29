<?php

namespace Drupal\insight_search\Plugin\search_api\processor;

use Drupal\search_api_attachments\Plugin\search_api\processor\FilesExtractor;

use Drupal\Component\Utility\Bytes;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Utility\Error;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\search_api_attachments\ExtractFileValidator;
use Drupal\search_api_attachments\TextExtractorPluginInterface;
use Drupal\search_api_attachments\TextExtractorPluginManager;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Aws\S3\S3UriParser;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\Credentials\Credentials;




/**
 * Adds an additional field containing the rendered item.
 *
 * @see \Drupal\search_api\Plugin\search_api\processor\Property\RenderedItemProperty
 *
 * @SearchApiProcessor(
 *   id = "field_file_attachment",
 *   label = @Translation("Insight File field"),
 *   description = @Translation("This field is custom field for Insight file Field Solr"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class InsightFile  extends FilesExtractor {

   
  /**
   * Name of the config being edited.
   */
 
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

      if ($datasource && $datasource->getEntityTypeId() == 'node') {
      $definition = [
          'label' => $this->t('Insight File for SOLR DOC index'),
          'description' => $this->t('This field is related to Insight File for SOLR DOC index'),
          'type' => 'string',
          'processor_id' => $this->getPluginId()
      ];
      $properties['field_file_attachment'] = new ProcessorProperty($definition);
      
      }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $files = [];
    $field = $item->getField('field_file_attachment');

    $config = $this->configFactory->get(static::CONFIGNAME);
    $extractor_plugin_id = $config->get('extraction_method');
    if ($extractor_plugin_id != '') {
      $configuration = $config->get($extractor_plugin_id . '_configuration');
      $extractor_plugin = $this->textExtractorPluginManager->createInstance($extractor_plugin_id, $configuration);
      // Get the entity.
      $entity = $item->getOriginalObject()->getValue();
      
      if($entity->bundle() == 'report' || $entity->bundle() == 'report_library' ||  $entity->bundle() == 'digital_content_library') {
         
        $property_path =  'field_file_attachment';
        // A way to load $field.
          $all_fids = [];
          // Code stared for adding the fids which are related  to the report 
          // But uploaded as a different entity
          
          $all_fids = array();
         // Defining $all_fids  variable as an array  
          if($entity->bundle() == 'report') {
                   $all_fids =  $this->getAttachmemtAgainstReport($entity->id());
          }  
          
          // For Report Library and digital content
          if(in_array($entity->bundle(),array('report_library','digital_content_library'))){
              foreach ($entity->field_file_attachments as $value) {
                  $all_fids[] = $value->target_id;
                    }
          }
         
         if(isset($all_fids) && !empty($all_fids) && count($all_fids) > 0) {
            $fids = $this->limitToAllowedNumber($all_fids);
            // Retrieve the files.
            $files = $this->entityTypeManager
              ->getStorage('file')
              ->loadMultiple($fids);
            
          if (!empty($files)) {
            $extraction = '';
            foreach ($files as $file) {
              if ($this->isFileIndexable($file, $item, $field_name)) { 
                $extraction .= $this->extractOrGetFromCache($entity, $file, $extractor_plugin);
              }
            }
            $field->addValue($extraction);
          }
         }
    }
    }
  }
  
  
  /**
   * Is File Indexable 
   * @param object $file
   * @param ItemInterface $item
   * @param string $field_name
   * @return boolean
   */
    public function isFileIndexable($file, ItemInterface $item, $field_name = NULL) {
    
    // Checking file exist or not on s3 bucket       
    $filename = str_replace('s3://', '', $file->getFileUri()); 
    $indexable = $this->checkWhetherCurrentFilesExistS3($filename);
    if (!$indexable) {
       return FALSE;
    }
     
    // File should have a mime type that is allowed.
    $all_excluded_mimes = $this->extractFileValidator->getExcludedMimes(NULL, $this->configuration['excluded_mimes']);
   //echo "sdsds";dump($all_excluded_mimes);die;
    $indexable = $indexable && !in_array($file->getMimeType(), $all_excluded_mimes);
    if (!$indexable) {
      return FALSE;
    }
    // File permanent.
    $indexable = $indexable && $file->isPermanent();
    if (!$indexable) {
      return FALSE;
    }
    // File shouldn't exceed configured file size.
    $max_filesize = $this->configuration['max_filesize'];
    $indexable = $indexable && $this->extractFileValidator->isFileSizeAllowed($file, $max_filesize);
    if (!$indexable) {
      return FALSE;
    }
    // Whether a private file can be indexed or not.
    $excluded_private = $this->configuration['excluded_private'];
    $indexable = $indexable && $this->extractFileValidator->isPrivateFileAllowed($file, $excluded_private);
    if (!$indexable) {
      return FALSE;
    }
    $result = $this->moduleHandler->invokeAll(
        'search_api_attachments_indexable', [$file, $item, $field_name]
    );
    $indexable = !in_array(FALSE, $result, TRUE);
    return $indexable;
  }


 /**
  * Get the attachment against report 
  * @param int $report_id
  * @return array
  */
  public function getAttachmemtAgainstReport($report_id){
    $con = \Drupal\Core\Database\Database::getConnection();
    $query = $con->select('node__field_content_reference', 'cr');
    $query->join('node__field_fattach_embed_attachment', 'tt','tt.entity_id = cr.field_content_reference_target_id');
    $query->join('node__field_fig_chart_file', 'file','file.entity_id = cr.field_content_reference_target_id');
    $query->fields('file', array('field_fig_chart_file_target_id'));
    $query->condition('cr.entity_id', $report_id, '=');
    $query->condition('tt.bundle', 'file_attachment', '=');
    $results = $query->execute();
    
   // Extracting the data from query result object 
    $fids = array();
    foreach($results as $chunk_data){
       $fids[] =  $chunk_data->field_fig_chart_file_target_id;
    }
    
    // Returning the file id refrences 
    if(isset($fids) && !empty($fids)){
        return $fids;
    }
    // Returning nothing if we dont have any attachmnt against the report
    return ;
  }
  
  
  
  /**
   * Check whether current files exist or not on s3 
   * @param string $filename
   * @return boolean
   */
    function checkWhetherCurrentFilesExistS3($filename) {
    $s3fs_access_key = \Drupal\Core\Site\Settings::get('s3fs.access_key');
    $s3fs_access_secret = \Drupal\Core\Site\Settings::get('s3fs.secret_key');
    $credentials = new Credentials($s3fs_access_key, $s3fs_access_secret);
    $s3fs_settings = \Drupal::config('s3fs.settings');
    $s3fs_bucket = $s3fs_settings->get('bucket');
    $s3fs_region = $s3fs_settings->get('region');
    try {
      $s3Client = new S3Client([
        'credentials' => $credentials,
        'region' => $s3fs_region,
        'version' => 'latest'
      ]);
    }
    catch (Exception $e) {
      \Drupal::logger('Error while accessing s3')->error($e->getMessage());
    }
    $bucketName = $s3fs_bucket;
    $key = $filename;
    $existFlag = $s3Client->doesObjectExist($bucketName, $key);
    if ($existFlag == 1) {
      return true;
    }
    return false;
  }
  
  
}