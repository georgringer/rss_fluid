<?php

declare(strict_types=1);

namespace GeorgRinger\RssFluid\DataProcessing;

use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * This file is part of the "rss_fluid" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */
class RssParserProcessor implements DataProcessorInterface
{
    /** @var \SimplePie */
    protected $rssParser;

    protected $importPath = '';

    public function __construct()
    {
        require_once ExtensionManagementUtility::extPath('rss_fluid') . 'Resources/Private/Php/SimplePie.compiled.php';
        $this->rssParser = new \SimplePie();
    }

    /**
     * @param ContentObjectRenderer $cObj
     * @param array $contentObjectConfiguration
     * @param array $processorConfiguration
     * @param array $processedData
     * @return array
     */
    public function process(ContentObjectRenderer $cObj, array $contentObjectConfiguration, array $processorConfiguration, array $processedData): array
    {
        $this->importPath = $processorConfiguration['import_path'] ?? '';

        $settings = $this->getConfiguration($cObj->data['pi_flexform']);
        $feed = $this->getFeed($settings['url'] ?? '');

        $as = $processorConfiguration['as'] ?? 'feed';

        $processedData[$as] = $feed;
        return $processedData;
    }

    protected function getFeed(string $url): array
    {
        if (empty($url)) {
            return [];
        }
        $this->rssParser->set_feed_url($url);
        $success = $this->rssParser->init();
        $error = $this->rssParser->error();

        $data = $this->rssParser->get_items();

        $feed = [
            'title' => $this->rssParser->get_title(),
            'link' => $this->rssParser->get_links(),
            'categories' => $this->rssParser->get_categories(),
            'description' => $this->rssParser->get_description(),
            'language' => $this->rssParser->get_language(),
            'copyright' => $this->rssParser->get_copyright(),
            'permalink' => $this->rssParser->get_permalink(),
        ];

        $items = [];
        foreach ($data as $l) {
            $items[] = [
                'guid' => $l->get_id(),
                'date' => $l->get_date(),
                'title' => $l->get_title(),
                'authors' => $l->get_authors(),
                'content' => $l->get_content(),
                'description' => $l->get_description(),
                'categories' => $this->transformCategories((array)$l->get_categories()),
                'thumbnail' => $l->get_thumbnail(),
                'links' => $l->get_links(),
                'permalink' => $l->get_permalink(),
                'enclosure' => $this->transformEnclosures((array)$l->get_enclosures()),
            ];
        }
        $feed['items'] = $items;
        return $feed;
    }

    /**
     * @param \SimplePie_Enclosure[] $enclosures
     * @return array
     */
    protected function transformEnclosures(array $enclosures)
    {
        $items = [];

        foreach ($enclosures as $line) {
            $items[] = [
                'link' => $line->get_link(),
                'localAsset' => $this->downloadMedia($line->get_link()),
                'thumbnails' => $line->get_thumbnails(),
                'type' => $line->get_type(),
                'height' => $line->get_height(),
                'width' => $line->get_width(),
            ];
        }

        return $items;
    }

    protected function downloadMedia(string $link): ?File
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        try {
            $dir = $resourceFactory->getFolderObjectFromCombinedIdentifier('1:/rss_import');
            if ($dir->getStorage()->getDriverType() !== 'Local') {
                return null;
            }
            $fileInfo = pathinfo($link);
            $file = null;

            $generatedName = sprintf('%s_%s.%s', $fileInfo['filename'], md5($link), $fileInfo['extension']);
            if ($dir->hasFile($generatedName)) {
                $file = $resourceFactory->getFileObjectFromCombinedIdentifier('1:/rss_import/' . $generatedName);
            } else {
                $tmpPath = GeneralUtility::tempnam('rssfluid');
                $download = GeneralUtility::getUrl($link);
                if ($download) {
                    GeneralUtility::writeFile($tmpPath, $download);

                    $file = $dir->addFile($tmpPath, $generatedName);
                }
                GeneralUtility::unlink_tempfile($tmpPath);
            }
            return $file;
        } catch (FolderDoesNotExistException $e) {

        }
        return null;
    }

    /**
     * @param \SimplePie_Category[] $categories
     */
    protected function transformCategories(array $categories = []): array
    {
        $titles = [];
        foreach ($categories as $category) {
            $titles[] = $category->get_term();
        }
        return $titles;
    }

    protected function getConfiguration(string $flexforms): array
    {
        $service = GeneralUtility::makeInstance(FlexFormService::class)->convertFlexFormContentToArray($flexforms);
        return $service['settings'] ?? [];
    }

}
