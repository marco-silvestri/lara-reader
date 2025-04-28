<?php

namespace App\Services;

class EpubParserService
{
    protected $epubPath;
    protected $extractPath;

    public function __construct($epubPath)
    {
        $this->epubPath = $epubPath;
        $this->extractPath = storage_path('app/tmp_epub/' . uniqid());
    }

    public function extract()
    {
        $zip = new \ZipArchive();
        if ($zip->open($this->epubPath)) {
            $zip->extractTo($this->extractPath);
            $zip->close();
        } else {
            throw new \Exception('Failed to open EPUB file.');
        }
    }

    public function getChapters()
    {
        $this->extract();

        // Parse META-INF/container.xml
        $container = simplexml_load_file($this->extractPath . '/META-INF/container.xml');
        $opfPath = (string) $container->rootfiles->rootfile['full-path'];

        // Parse content.opf
        $opf = simplexml_load_file($this->extractPath . '/' . $opfPath);
        $namespaces = $opf->getNamespaces(true);

        $manifest = $opf->manifest->item;
        $chapters = [];

        foreach ($manifest as $item) {
            $href = (string) $item['href'];
            $mediaType = (string) $item['media-type'];

            if (str_contains($mediaType, 'application/xhtml+xml') || str_contains($mediaType, 'text/html')) {
                $fullPath = dirname($opfPath) . '/' . $href;
                $fullFilePath = $this->extractPath . '/' . $fullPath;

                if (file_exists($fullFilePath)) {
                    $dom = new \DOMDocument();
                    @$dom->loadHTMLFile($fullFilePath);
                    $text = $dom->textContent;
                    $chapters[] = [
                        'title' => pathinfo($href, PATHINFO_FILENAME),
                        'content' => $text,
                    ];
                }
            }
        }

        return $chapters;
    }
}
