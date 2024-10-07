<?php

namespace WebScraper;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Scraper
{
    private $client;

    public function __construct($proxy = null, $userAgent = 'Mozilla/5.0')
    {
        $options = [
            'headers' => ['User-Agent' => $userAgent],
        ];

        if ($proxy) {
            $options['proxy'] = $proxy;
        }

        $this->client = new Client($options);
    }


    public function fetchPageContent($url)
    {
        $response = $this->client->get($url);
        return $response->getBody()->getContents();
    }


    public function scrapeElement($htmlContent, $selector)
    {
        $crawler = new Crawler($htmlContent);
        $elements = $crawler->filter($selector);

        $data = [];
        foreach ($elements as $element) {
            $data[] = $element->textContent;
        }

        return $data;
    }


    public function fetchMultiplePages($url, $selectorForNextPage, $selector, $pages = 5)
    {
        $allData = [];
        $currentPage = $url;

        for ($i = 0; $i < $pages; $i++) {
            $htmlContent = $this->fetchPageContent($currentPage);

            $allData[] = $this->scrapeElement($htmlContent, $selector);


            $nextPageUrl = $this->scrapeElement($htmlContent, $selectorForNextPage)[0];

            if (!$nextPageUrl) {
                break;
            }

            $currentPage = $nextPageUrl;
        }

        return $allData;
    }


    public function scrapeMetaData($htmlContent)
    {
        $crawler = new Crawler($htmlContent);
        $title = $crawler->filter('head title')->text();
        $description = $crawler->filter('meta[name="description"]')->attr('content');
        $keywords = $crawler->filter('meta[name="keywords"]')->attr('content');

        return ['title' => $title, 'description' => $description, 'keywords' => $keywords];
    }

    public function trackUpdates($url, $selector, $interval = 60)
    {
        $previousData = [];
        while (true) {
            $htmlContent = $this->fetchPageContent($url);
            $newData = $this->scrapeElement($htmlContent, $selector);

            if ($previousData !== $newData) {
                echo "Page has been updated!";
                $previousData = $newData;
            } else {
                echo "No changes detected.";
            }

            sleep($interval);
        }
    }

    public function exportToJson($data, $filename = 'output.json')
    {
        file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    }


    public function login($url, $credentials)
    {
        $response = $this->client->post($url, [
            'form_params' => $credentials,
        ]);

        return $response->getStatusCode() === 200;
    }
}