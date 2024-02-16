<?php 

namespace App\Service;

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Exception;

class AwsUploader {
    private $s3Client;

    public function __construct() {

        $this->s3Client = new S3Client([
            'region'  => $_ENV['REGION'],
            'scheme'  => 'http',
            'credentials' => [
                'key' => $_ENV['ACCESSKEY'],
                'secret' => $_ENV['SECRETACCESSKEY']
            ]
        ]);
    }

    public function uploadPhotoUser($file, $fileName) {
        try {
            $folder = $_ENV['FOLDERIMGUSERS'];

            return $this->uploadFile($file, $fileName, $folder);

        } catch (S3Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function uploadFile($file, $fileName, $folder) {
        try {
            $fileType = $file->getClientMimeType();
            $mime = explode('/', $fileType);
            $s3ObjectKey = $folder .'/'. $fileName . '.' . $mime[1];

            $this->s3Client->putObject([
                'Bucket' => $_ENV['BUCKET'],
                'Key' => $s3ObjectKey,
                'Body' => fopen($file->getPathname(), 'r'),
                'ContentType' => $mime[1]
            ]);

            return $this->s3Client->getObjectUrl($_ENV['BUCKET'], $s3ObjectKey);
        } catch (S3Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}