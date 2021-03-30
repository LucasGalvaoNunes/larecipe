<?php


namespace BinaryTorch\LaRecipe\Facades;


use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;

class AmazonS3
{
    private $s3Client;


    public function __construct()
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('larecipe.docs.s3.region'),
            'key'    => config('larecipe.docs.s3.key'),
            'secret' => config('larecipe.docs.s3.secret')
        ]);
    }


    public static function storage()
    {
        return new static();
    }

    public function getItem(string $path)
    {
        try {
            $result = $this->s3Client->getObject([
                'Bucket' => config('larecipe.docs.s3.bucket'),
                'Key' => $path
            ]);
            $body = $result['Body'];

            return $body->getContents();
        } catch (\Exception $exception) {
            return null;
        }
    }

}
