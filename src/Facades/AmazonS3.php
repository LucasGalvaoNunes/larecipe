<?php


namespace BinaryTorch\LaRecipe\Facades;


use Aws\S3\S3Client;

class AmazonS3
{
    private $s3Client;


    public function __construct()
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => config('larecipe.docs.s3.region')
        ]);
    }


    public static function storage()
    {
        return new static();
    }

    public function getItem(string $path)
    {
        $result = $this->s3Client->getObject([
            'Bucket' => config('larecipe.docs.s3.bucket'),
            'Key' => config('larecipe.docs.s3.key')
        ]);

        return $result;
    }

}