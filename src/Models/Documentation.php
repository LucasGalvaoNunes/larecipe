<?php

namespace BinaryTorch\LaRecipe\Models;

use BinaryTorch\LaRecipe\Cache;
use BinaryTorch\LaRecipe\Facades\AmazonS3;
use Illuminate\Filesystem\Filesystem;
use BinaryTorch\LaRecipe\Traits\Indexable;
use BinaryTorch\LaRecipe\Traits\HasBladeParser;
use BinaryTorch\LaRecipe\Traits\HasMarkdownParser;

class Documentation
{
    use HasMarkdownParser, HasBladeParser, Indexable;

    /**
     * The filesystem implementation.
     *
     * @var Filesystem
     */
    protected $files;

    /**
     * The cache implementation.
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Create a new documentation instance.
     *
     * @param Filesystem $files
     * @param Cache $cache
     */
    public function __construct(Filesystem $files, Cache $cache)
    {
        $this->files = $files;
        $this->cache = $cache;
    }

    /**
     * Get the documentation index page.
     *
     * @param  string  $version
     * @return string
     */
    public function getIndex($version)
    {
        return $this->cache->remember(function() use($version) {
            $path = base_path(config('larecipe.docs.path').'/'.$version.'/index.md');

            if ($this->files->exists($path)) {
                $parsedContent = $this->parse($this->files->get($path));

                return $this->replaceLinks($version, $parsedContent);
            }

            return null;
        }, 'larecipe.docs.'.$version.'.index');
    }

    /**
     * Get the given documentation page.
     *
     * @param $version
     * @param $page
     * @param array $data
     * @return mixed
     */
    public function get($version, $page, $data = [])
    {
        return $this->cache->remember(function() use($version, $page, $data) {
            $path = config('larecipe.docs.path') . '/' . $version . '/' . $page . '.md';
            $useS3 = config('larecipe.docs.driver', 'local') == 's3';

            if ($useS3) {
                return $this->getS3MarkDown($path, $version, $data);
            } else {
                $path = base_path($path);
                return $this->getLocalMarkDown($path, $version, $data);
            }

        }, 'larecipe.docs.'.$version.'.'.$page);
    }

    /**
     * @param $path
     * @param $version
     * @param array $data
     * @return false|string|null
     * @throws \Exception
     */
    protected function getLocalMarkDown($path, $version, array $data)
    {
        if ($this->files->exists($path)) {
            $parsedContent = $this->parse($this->files->get($path));

            $parsedContent = $this->replaceLinks($version, $parsedContent);

            return $this->renderBlade($parsedContent, $data);
        }

        return null;
    }

    /**
     * @param $path
     * @param $version
     * @param array $data
     * @return false|string|null
     * @throws \Exception
     */
    protected function getS3MarkDown($path, $version, array $data)
    {
        $markdown = AmazonS3::storage()->getItem($path);

        if (!is_null($markdown)) {
            $parsedContent = $this->parse($markdown);

            $parsedContent = $this->replaceLinks($version, $parsedContent);

            return $this->renderBlade($parsedContent, $data);
        }

        return null;
    }
    /**
     * Replace the version and route placeholders.
     *
     * @param  string  $version
     * @param  string  $content
     * @return string
     */
    public static function replaceLinks($version, $content)
    {
        $content = str_replace('{{version}}', $version, $content);

        $content = str_replace('{{route}}', trim(config('larecipe.docs.route'), '/'), $content);

        $content = str_replace('"#', '"'.request()->getRequestUri().'#', $content);

        return $content;
    }

    /**
     * Check if the given section exists.
     *
     * @param  string  $version
     * @param  string  $page
     * @return bool
     */
    public function sectionExists($version, $page)
    {
        return $this->files->exists(
            base_path(config('larecipe.docs.path').'/'.$version.'/'.$page.'.md')
        );
    }
}
