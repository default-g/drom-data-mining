<?php

namespace Default\DromDataMining\Services;

use Default\DromDataMining\Interfaces\DromParserInterface;
use Default\DromDataMining\Models\Car;
use DOMDocument;
use DOMElement;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Rct567\DomQuery\DomQuery;
use Symfony\Component\DomCrawler\Crawler;

class DromWebParserService implements DromParserInterface
{
    public const LIST_URL = 'https://auto.drom.ru/all/page';

    public function __construct(private Client $client)
    {
    }

    /**
     * @return Generator
     * @throws GuzzleException
     */
    public function parse(): Generator
    {
        $page = 1;
        while ($cars = $this->processPage($page++)) {
            foreach ($cars as $car) {
                yield $car;
            }
        }
    }

    /**
     * Строит URL для страницы
     * @param int $page
     * @return string
     */
    protected function getUrlForPage(int $page): string
    {
        return self::LIST_URL . $page;
    }

    /**
     * Возвращает необходимые GET параметры, подходящие по заданию
     * @return array
     */
    protected function getParams(): array
    {
        return [
            'multiselect' => ['9_4_15_all', '9_4_16_all'],
            'unsold' => 1,
            'cid' => ['23', '170'],
            'pts' => 2,
            'damaged' => 2,
        ];
    }


    /**
     * Обрабатывает страницу с автомобилями
     * @param int $page
     * @return array
     * @throws GuzzleException
     */
    protected function processPage(int $page): array
    {
        $cars = [];
        $webCrawler = $this->getCrawlerForUrl($this->getUrlForPage($page));
        /**
         * @var DOMElement $element
         */
        $webCrawler->filter('div [data-ftid="bulls-list_bull"]')->each(function (Crawler $element) use (&$cars) {
            $car = $this->processCar($element);

            if ($car === null) {
                return;
            }

            $cars[] = $this->processCar($element);
        });

        return $cars;
    }

    /**
     * Парсит блок со страницы
     * со списком автомобилей
     *
     * @param Crawler $crawler
     * @return Car|null
     * @throws GuzzleException
     */
    protected function processCar(Crawler $crawler): ?Car
    {
        $doesCarHasComingLabel = (bool) $crawler
            ->filter('[data-ftid="bull_label_coming"]')
            ->count();

        if ($doesCarHasComingLabel) {
            return null;
        }

        $link = $this->parseLink($crawler);
        $id = $this->extractIdFromUrl($link);
        if (!$id) {
            return null;
        }
        $title = $this->parseTitle($crawler);

        [$mark, $model, $year] = $this->getMarkModelYearFromTitle($title);

        $price = $this->parsePrice($crawler);

        $priceRating = $this->parsePriceRating($crawler);

        $carPageCrawler = $this->getCrawlerForUrl($link);

        $generation = $carPageCrawler
            ->filter('[data-ga-stats-name="generation_link"]')
            ->text();

        return new Car(
            $id,
            $link,
            $model,
            $mark,
            $price,
            $priceRating,
            $generation
        );
    }


    /**
     * Достает ID объявления из ссылки
     * @param string $url
     * @return string|null
     */
    protected function extractIdFromUrl(string $url): ?string
    {
        preg_match('/(\d+)\.html$/', $url, $matches);
        return $matches[1] ?? null;
    }


    /**
     * Создает веб кроулер для страницы
     *
     * @param string $url
     * @return Crawler
     * @throws GuzzleException
     */
    protected function getCrawlerForUrl(string $url): Crawler
    {
        $response = $this->client->get(
            $url,
            [
                'query' => $this->getParams(),
                'headers' => [
                    'Accept-Encoding' => 'gzip, deflate',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9,ru-RU;q=0.8,ru;q=0.7',
                ]
            ]
        );

        $rawContents = $response->getBody()->getContents();

        $metaCharsetPatterns = [
            '/<meta charset="windows-1251"\s*\/?>/i',
            '/<meta http-equiv="Content-Type" content="text\/html; charset=windows-1251"\s*\/?>/i'
        ];
        $contents = preg_replace($metaCharsetPatterns, '<meta charset="utf-8">', $rawContents);
        $contents = mb_convert_encoding($contents, 'UTF-8', 'Windows-1251');

        $webCrawler = new Crawler();
        $webCrawler->addHtmlContent($contents, 'UTF-8');

        return $webCrawler;
    }


    /**
     * Делит строку вида
     * Toyota Corolla, 2020
     * На отдельные части
     *
     * @param string $title
     * @return array
     */
    protected function getMarkModelYearFromTitle(string $title): array
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', ' ', $title);
        $splitString = explode(' ', $title);

        return [
            $splitString[0] ?? null,
            $splitString[1] ?? null,
            $splitString[2] ?? null
        ];
    }


    /**
     * Убирает лишние символы из цены и приводит к float
     *
     * @param string|null $price
     * @return float|null
     */
    protected function formatPrice(?string $price): ?float
    {
        if ($price === null) {
            return null;
        }

        return (float)preg_replace('/[^0-9]/', '', $price);
    }

    /**
     * Получает ссылку на объявление
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseLink(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('[data-ftid="bull_title"]')
                ->attr('href');
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Получает название объявления (марку, модель и год)
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseTitle(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('[data-ftid="bull_title"]')
                ->text();
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Парсинг цены
     *
     * @param Crawler $crawler
     * @return float|null
     */
    protected function parsePrice(Crawler $crawler): ?float
    {
        try {
            $parsedPrice = $crawler
                ->filter('[data-ftid="bull_price"]')
                ->text();

            return $this->formatPrice($parsedPrice);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    protected function parsePriceRating(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('.ejipaoe0')
                ->text();
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }
}
