<?php

namespace R94ever\Sitemap;

use DateTime;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactory;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Filesystem\Filesystem as Filesystem;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Psr\SimpleCache\InvalidArgumentException;

class Sitemap
{
    public Model $model;

    public function __construct(
        array $config,
        public CacheRepository $cache,
        protected ConfigRepository $configRepository,
        protected Filesystem $file,
        protected ResponseFactory $response,
        protected ViewFactory $view
    ) {
        $this->model = new Model($config);
    }

    /**
     * Set cache options.
     *
     * @param string|null $key
     * @param Carbon|DateTime|int|null $duration
     * @param bool $useCache
     * @return void
     */
    public function setCache(?string $key = null, Carbon|Datetime|int|null $duration = null, bool $useCache = true): void
    {
        $this->model->setUseCache($useCache);

        if (null !== $key) {
            $this->model->setCacheKey($key);
        }

        if (null !== $duration) {
            $this->model->setCacheDuration($duration);
        }
    }

    /**
     * Checks if content is cached.
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function isCached(): bool
    {
        if ($this->model->getUseCache()) {
            if ($this->cache->has($this->model->getCacheKey())) {
                return true;
            }
        }

        return false;
    }

    /**
     * Add new sitemap item to $items array.
     *
     * @param string $loc
     * @param ?string $lastmod
     * @param ?string $priority
     * @param ?string $freq
     * @param array  $images
     * @param ?string $title
     * @param array  $translations
     * @param array  $videos
     * @param array  $googlenews
     * @param array  $alternates
     * @return void
     */
    public function add(
        ?string $loc,
        ?string $lastmod = null,
        ?string $priority = null,
        ?string $freq = null,
        array $images = [],
        ?string $title = null,
        array $translations = [],
        array $videos = [],
        array $googlenews = [],
        array $alternates = []
    ): void {
        $params = [
            'loc'           => $loc,
            'lastmod'       => $lastmod,
            'priority'      => $priority,
            'freq'          => $freq,
            'images'        => $images,
            'title'         => $title,
            'translations'  => $translations,
            'videos'        => $videos,
            'googlenews'    => $googlenews,
            'alternates'    => $alternates,
        ];

        $this->addItem($params);
    }

    /**
     * Add new sitemap one or multiple items to $items array.
     *
     * @param array $params
     * @return void
     */
    public function addItem(array $params = []): void
    {
        // If is multidimensional
        if (array_key_exists(1, $params)) {
            foreach ($params as $a) {
                $this->addItem($a);
            }

            return;
        }

        // Get params
        foreach ($params as $key => $value) {
            $$key = $value;
        }

        // Set default values
        if (!isset($loc)) {
            $loc = '/';
        }
        if (!isset($lastmod)) {
            $lastmod = null;
        }
        if (!isset($priority)) {
            $priority = null;
        }
        if (!isset($freq)) {
            $freq = null;
        }
        if (!isset($title)) {
            $title = null;
        }
        if (!isset($images)) {
            $images = [];
        }
        if (!isset($translations)) {
            $translations = [];
        }
        if (!isset($alternates)) {
            $alternates = [];
        }
        if (!isset($videos)) {
            $videos = [];
        }
        if (!isset($googlenews)) {
            $googlenews = [];
        }

        // escaping
        if ($this->model->getEscaping()) {
            $loc = htmlentities($loc, ENT_XML1);

            if ($title !== null) {
                $title = htmlentities($title, ENT_XML1);
            }

            if ($images) {
                foreach ($images as $k => $image) {
                    foreach ($image as $key => $value) {
                        $images[$k][$key] = htmlentities($value, ENT_XML1);
                    }
                }
            }

            if ($translations) {
                foreach ($translations as $k => $translation) {
                    foreach ($translation as $key => $value) {
                        $translations[$k][$key] = htmlentities($value, ENT_XML1);
                    }
                }
            }

            if ($alternates) {
                foreach ($alternates as $k => $alternate) {
                    foreach ($alternate as $key => $value) {
                        $alternates[$k][$key] = htmlentities($value, ENT_XML1);
                    }
                }
            }

            if ($videos) {
                foreach ($videos as $k => $video) {
                    if (! empty($video['title'])) {
                        $videos[$k]['title'] = htmlentities($video['title'], ENT_XML1);
                    }
                    if (! empty($video['description'])) {
                        $videos[$k]['description'] = htmlentities($video['description'], ENT_XML1);
                    }
                }
            }

            if ($googlenews) {
                if (isset($googlenews['sitename'])) {
                    $googlenews['sitename'] = htmlentities($googlenews['sitename'], ENT_XML1);
                }
            }
        }

        $googlenews['sitename'] = $googlenews['sitename'] ?? '';
        $googlenews['language'] = $googlenews['language'] ?? 'en';
        $googlenews['publication_date'] = $googlenews['publication_date'] ?? date('Y-m-d H:i:s');

        $this->model->setItems([
            'loc'          => $loc,
            'lastmod'      => $lastmod,
            'priority'     => $priority,
            'freq'         => $freq,
            'images'       => $images,
            'title'        => $title,
            'translations' => $translations,
            'videos'       => $videos,
            'googlenews'   => $googlenews,
            'alternates'   => $alternates,
        ]);
    }

    /**
     * Add new sitemap to $sitemaps array.
     *
     * @param string $loc
     * @param ?string $lastmod
     * @return void
     */
    public function addSitemap(string $loc, ?string $lastmod = null): void
    {
        $this->model->setSitemaps([
            'loc'     => $loc,
            'lastmod' => $lastmod,
        ]);
    }

    /**
     * Add new sitemap to $sitemaps array.
     *
     * @param array $sitemaps
     * @return void
     */
    public function resetSitemaps(array $sitemaps = []): void
    {
        $this->model->resetSitemaps($sitemaps);
    }

    /**
     * Returns document with all sitemap items from $items array.
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, google-news)
     * @param ?string $style (path to custom xls style like '/styles/xsl/xml-sitemap.xsl')
     * @return Response
     * @throws InvalidArgumentException
     */
    public function render(string $format = 'xml', ?string $style = null): Response
    {
        // Limit size of sitemap
        if ($this->model->getMaxSize() > 0 && count($this->model->getItems()) > $this->model->getMaxSize()) {
            $this->model->limitSize($this->model->getMaxSize());
        } elseif ('google-news' == $format && count($this->model->getItems()) > 1000) {
            $this->model->limitSize(1000);
        } elseif ('google-news' != $format && count($this->model->getItems()) > 50000) {
            $this->model->limitSize();
        }

        $data = $this->generate($format, $style);

        return $this->response->make($data['content'], 200, $data['headers']);
    }

    /**
     * Generates document with all sitemap items from $items array.
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, sitemapindex, google-news)
     * @return array
     * @throws InvalidArgumentException
     */
    public function generate(string $format = 'xml'): array
    {
        // check if caching is enabled, there is a cached content and its duration isn't expired
        if ($this->isCached()) {
            'sitemapindex' === $format
                ? $this->model->resetSitemaps($this->cache->get($this->model->getCacheKey()))
                : $this->model->resetItems($this->cache->get($this->model->getCacheKey()));
        } elseif ($this->model->getUseCache()) {
            'sitemapindex' === $format
                ? $this->cache->put(
                    $this->model->getCacheKey(),
                    $this->model->getSitemaps(),
                    $this->model->getCacheDuration()
                )
                : $this->cache->put(
                    $this->model->getCacheKey(),
                    $this->model->getItems(),
                    $this->model->getCacheDuration()
                );
        }

        if (!$this->model->getLink()) {
            $this->model->setLink($this->configRepository->get('app.url'));
        }

        if (!$this->model->getTitle()) {
            $this->model->setTitle('Sitemap for '.$this->model->getLink());
        }

        $channel = [
            'title' => $this->model->getTitle(),
            'link'  => $this->model->getLink(),
        ];

        // Check if styles are enabled
        if ($this->model->getUseStyles()) {
            if (null !== $this->model->getSloc() && file_exists(public_path($this->model->getSloc().$format.'.xsl'))) {
                // use style from your custom location
                $style = $this->model->getSloc().$format.'.xsl';
            } else {
                // don't use style
                $style = null;
            }
        } else {
            // don't use style
            $style = null;
        }

        return match ($format) {
            'ror-rss' => [
                'content' => $this->view->make('sitemap::ror-rss', [
                    'items' => $this->model->getItems(),
                    'channel' => $channel,
                    'style' => $style
                ])->render(),
                'headers' => ['Content-type' => 'text/rss+xml; charset=utf-8']
            ],
            'ror-rdf' => [
                'content' => $this->view->make('sitemap::ror-rdf', [
                    'items' => $this->model->getItems(),
                    'channel' => $channel,
                    'style' => $style
                ])->render(),
                'headers' => ['Content-type' => 'text/rdf+xml; charset=utf-8']
            ],
            'html' => [
                'content' => $this->view->make('sitemap::html', [
                    'items' => $this->model->getItems(),
                    'channel' => $channel,
                    'style' => $style
                ])->render(),
                'headers' => ['Content-type' => 'text/html; charset=utf-8']
            ],
            'txt' => [
                'content' => $this->view->make('sitemap::txt', [
                    'items' => $this->model->getItems(),
                    'style' => $style
                ])->render(),
                'headers' => ['Content-type' => 'text/plain; charset=utf-8']
            ],
            'sitemapindex' => [
                'content' => $this->view->make('sitemap::sitemapindex', [
                    'sitemaps' => $this->model->getSitemaps(),
                    'style' => $style
                ])->render(),
                'headers' => ['Content-type' => 'text/xml; charset=utf-8']
            ],
            default => [
                'content' => $this->view->make('sitemap::' . $format, [
                    'items' => $this->model->getItems(),
                    'style' => $style
                ])->render(),
                'headers' => ['Content-type' => 'text/xml; charset=utf-8']
            ],
        };
    }

    /**
     * Generate sitemap and store it to a file.
     *
     * @param string $format (options: xml, html, txt, ror-rss, ror-rdf, sitemapindex, google-news)
     * @param string $filename (without file extension, may be a path like 'sitemaps/sitemap1' but must exist)
     * @param ?string $path (path to store sitemap like '/www/site/public')
     * @param ?string $style (path to custom xls style like '/styles/xsl/xml-sitemap.xsl')
     * @return void
     * @throws InvalidArgumentException
     */
    public function store(
        string $format = 'xml',
        string  $filename = 'sitemap',
        ?string $path = null,
        ?string $style = null
    ): void {
        // turn off caching for this method
        $this->model->setUseCache(false);

        // Use correct file extension
        $fe = in_array($format, ['txt', 'html'], true) ? $format : 'xml';

        if ($this->model->getUseGzip()) {
            $fe = $fe.".gz";
        }

        // Use custom size limit for sitemaps
        if ($this->model->getMaxSize() > 0 && count($this->model->getItems()) > $this->model->getMaxSize()) {
            if ($this->model->getUseLimitSize()) {
                // Limit size
                $this->model->limitSize($this->model->getMaxSize());
                $data = $this->generate($format, $style);
            } else {
                // Use sitemapindex and generate partial sitemaps
                $chunkedItems = array_chunk($this->model->getItems(), $this->model->getMaxSize());

                foreach ($chunkedItems as $key => $item) {
                    // Reset current items
                    $this->model->resetItems($item);

                    // Generate new partial sitemap
                    $this->store($format, $filename.'-'.$key, $path, $style);

                    // Add sitemap to sitemapindex
                    if ($path) {
                        // If using custom path generate relative urls for sitemaps in the sitemapindex
                        $this->addSitemap($filename.'-'.$key.'.'.$fe);
                    } else {
                        // else generate full urls based on app's domain
                        $this->addSitemap(url($filename.'-'.$key.'.'.$fe));
                    }
                }

                $data = $this->generate('sitemapindex', $style);
            }
        } elseif (('google-news' !== $format && count($this->model->getItems()) > 50_000) || ($format === 'google-news' && count($this->model->getItems()) > 1_000)) {
            $max = 'google-news' !== $format ? 50_000 : 1_000;

            // Check if limiting size of items array is enabled
            if (!$this->model->getUseLimitSize()) {
                // Use sitemapindex and generate partial sitemaps
                $chunkedItems = array_chunk($this->model->getItems(), $max);

                foreach ($chunkedItems as $key => $item) {
                    // Reset current items
                    $this->model->resetItems($item);

                    // Generate new partial sitemap
                    $this->store($format, $filename.'-'.$key, $path, $style);

                    // Add sitemap to sitemapindex
                    if ($path) {
                        // If using custom path generate relative urls for sitemaps in the sitemapindex
                        $this->addSitemap($filename.'-'.$key.'.'.$fe);
                    } else {
                        // Else generate full urls based on app's domain
                        $this->addSitemap(url($filename.'-'.$key.'.'.$fe));
                    }
                }

                $data = $this->generate('sitemapindex', $style);
            } else {
                // Reset items and use only most recent $max items
                $this->model->limitSize($max);
                $data = $this->generate($format, $style);
            }
        } else {
            $data = $this->generate($format, $style);
        }

        // Clear memory
        if ('sitemapindex' === $format) {
            $this->model->resetSitemaps();
        }

        $this->model->resetItems();

        // If custom path
        if (!$path) {
            $file = public_path().DIRECTORY_SEPARATOR.$filename.'.'.$fe;
        } else {
            $file = $path.DIRECTORY_SEPARATOR.$filename.'.'.$fe;
        }

        if ($this->model->getUseGzip()) {
            // Write file (gzip compressed)
            $this->file->put($file, gzencode($data['content'], 9));
        } else {
            // Write file
            $this->file->put($file, $data['content']);
        }
    }
}
