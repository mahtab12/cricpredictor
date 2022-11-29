<?php

/**
 * @file Provides a helper class for forming document structure for Report Library nodes.
 * @author Sunapu Siddharth <ssiddharth@dresources.com>
 */


namespace Drupal\insight_search;


use Aws\CloudFront\CloudFrontClient;
use Aws\S3\S3Client;

class ReportLibraryHelper {
  
  private $cloud_front_key_pair_id = 'APKAIZCXNSCWBOU5JSFQ';
  private $cloud_front_private_key_name = 'pk-APKAIZCXNSCWBOU5JSFQ.pem';
  private $cloud_fornt_private_key_path = '../private';
  
  
  function getSignedUrl($file_name) {
      
     // return "https://ip-d8.s3.amazonaws.com/ppt_embed/treat-file-embed.pdf";
     // 
      
    // I have written this function for local system testing only
   // return "http://scms.ti/sites/default/files/test_for_attachment.pptx"; 
   // return "http://scms.ti/sites/default/files/file_example_XLSX_10.xlsx"; 
    
    
    
//    echo "here";exit;
//    $CLOUDFRONTPRIVATEKEYPATH = '../private';
    $s3fs_access_key = \Drupal\Core\Site\Settings::get('s3fs.access_key');
    $s3fs_access_secret = \Drupal\Core\Site\Settings::get('s3fs.secret_key');
    $s3fs_settings = \Drupal::config('s3fs.settings');
    $s3fs_region = $s3fs_settings->get('region');
    $s3fs_domain = $s3fs_settings->get('domain');
    $s3fs_root_folder = $s3fs_settings->get('root_folder');
    $s3fs_use_https = $s3fs_settings->get('use_https');
    $s3fs_bucket = $s3fs_settings->get('bucket');
//    echo "<pre>";print_r($s3fs_bucket);exit;

    $s3Client = new S3Client([
    'profile' => 'default',
    'region' => 'us-east-2',
    'version' => '2006-03-01',
    ]);

    $cmd = $s3Client->getCommand('GetObject', [
        'Bucket' => 'ip-d8',
        'Key' => 'M360SP0054.pdf'
    ]);

    //https://ip-d8.s3.amazonaws.com/M360SP0054.pdf
        
    $request = $s3Client->createPresignedRequest($cmd, '+20 minutes');

    print_r($cmd);exit;
//    
    $cloudFront = new CloudFrontClient([
        'credentials' => [
            'key' => $s3fs_access_key,
            'secret' => $s3fs_access_secret,
        ],
        'region' => $s3fs_region,
        'version' => '2014-11-06',
    ]);
    if ($s3fs_use_https) {
      $streamHostUrl = 'https://' . $s3fs_domain;
    } else {
      $streamHostUrl = 'http://' . $s3fs_domain;
    }
    $resourceKey = 'reportLibrary' . '/' . $file_name;
    $expires = time() + 300;
    $privateKeyFilePath = $this->cloud_fornt_private_key_path . '/' . $this->cloud_front_private_key_name;
    if (!file_exists($privateKeyFilePath)) {
      echo "no pem file exists";
      return NULL;
    }

    // Create a signed URL for the resource.
    $signedUrl = $cloudFront->getSignedUrl([
        'url' => $streamHostUrl . '/' . $resourceKey,
        'expires' => $expires,
        'private_key' => $privateKeyFilePath,
        'key_pair_id' => $this->cloud_front_key_pair_id,
    ]);
//    print_r($signedUrl);exit;
    return $signedUrl;
  }

}
