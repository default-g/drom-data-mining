<?php

namespace Default\DromDataMining\Services;

use Default\DromDataMining\Interfaces\DromParserInterface;
use Default\DromDataMining\Models\Car;
use DOMElement;
use Generator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DomCrawler\Crawler;

class DromWebParserService implements DromParserInterface
{
    public const LIST_URL = 'https://auto.drom.ru/all/page';

    public function __construct(private Client $client)
    {
    }

    /**
     * @return Generator<Car>
     * @throws GuzzleException
     */
    public function parse(): Generator
    {
        $page = 1;
        while ($cars = $this->processPage($page)) {
            foreach ($cars as $car) {
                yield $car;
            }

            $page++;
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
     *
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
        // Начало парсинга блока с объявлением
        if ($this->checkIfCarHasComingLabel($crawler)) {
            return null;
        }

        $link = $this->parseLink($crawler);
        $id = $this->extractIdFromUrl($link);
        if (!$id) {
            return null;
        }
        $title = $this->parseTitle($crawler);
        [$mark, $model] = $this->getMarkAndModelFromTitle($title);
        $price = $this->parsePrice($crawler);
        $carWithoutRussianMileage = !$this->checkIfCarHasRussianMileage($crawler);

        // Начало парсинга данных уже со страницы автомобиля
        $carPageCrawler = $this->getCrawlerForUrl($link);
        $generation = $this->parseGeneration($carPageCrawler);
        $complectation = $this->parseComplectation($carPageCrawler);
        $mileage = $this->parseMileage($carPageCrawler);
        $color = $this->parseColor($carPageCrawler);
        $bodyType = $this->parseBodyType($carPageCrawler);
        $enginePower = $this->parseEnginePower($carPageCrawler);
        $fuelType = $this->parseFuelType($carPageCrawler);
        $engineVolume = $this->parseEngineVolume($carPageCrawler);
        $imagesLinks = $this->parseImagesLinks($carPageCrawler);
        $priceRating = $this->parsePriceRating($carPageCrawler);


        return new Car(
            id: $id,
            url: $link,
            model: $model,
            brand: $mark,
            price: $price,
            priceRating: $priceRating,
            generation: $generation,
            complectation: $complectation,
            mileage: $mileage,
            withoutRussianMileage: $carWithoutRussianMileage,
            color: $color,
            bodyType: $bodyType,
            enginePower: $enginePower,
            fuelType: $fuelType,
            engineVolume: $engineVolume,
            imageLinks: $imagesLinks
        );
    }


    /**
     * Достает ID объявления из ссылки
     *
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
     * на марку и модель
     *
     * @param string $title
     * @return array
     */
    protected function getMarkAndModelFromTitle(string $title): array
    {
        $title = preg_replace('/[^A-Za-z0-9\-]/', ' ', $title);
        $splitString = explode(' ', $title);

        return [
            $splitString[0] ?? null,
            $splitString[1] ?? null,
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


    /**
     * Парсинг оценку цены
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parsePriceRating(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('[data-ga-stats-name="good_deal_mark"]')
                ->text();
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Проверка на наличие метки "В пути"
     *
     * @param Crawler $crawler
     * @return bool
     */
    protected function checkIfCarHasComingLabel(Crawler $crawler): bool
    {
        return (bool)$crawler
            ->filter('[data-ftid="bull_label_coming"]')
            ->count();
    }

    /**
     * Проверка, есть ли у авто пробег по РФ
     *
     * @param Crawler $crawler
     * @return bool
     */
    protected function checkIfCarHasRussianMileage(Crawler $crawler): bool
    {
        return !$crawler
            ->filter('.css-1jdyedu.ejipaoe0')
            ->count();
    }


    /**
     * Парсинг поколения
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseGeneration(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('[data-ga-stats-name="generation_link"]')
                ->text();
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Парсинг комплектации
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseComplectation(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('[data-ga-stats-name="complectation_link"]')
                ->text();
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Парсинг пробега
     *
     * @param Crawler $crawler
     * @return float|null
     */
    protected function parseMileage(Crawler $crawler): ?int
    {
        try {
            $mileage = $crawler
                ->filter('tbody')
                ->filter('tr')
                ->slice(7, 1)
                ->text();

            $mileage = explode('}', $mileage)[1];

            return (int) preg_replace('/[^0-9]/', '', $mileage);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }

    }


    /**
     * Парсит цвет
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseColor(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('tbody')
                ->filter('tr')
                ->slice(6, 1)
                ->filter('td')
                ->text();

        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Парсинг типа кузова
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseBodyType(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('tbody')
                ->filter('tr')
                ->slice(5, 1)
                ->filter('td')
                ->text();

        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Парсинг мощности двигателя
     *
     * @param Crawler $crawler
     * @return int|null
     */
    protected function parseEnginePower(Crawler $crawler): ?int
    {
        try {
            $enginePower = $crawler
                ->filter('tbody')
                ->filter('tr')
                ->slice(1, 1)
                ->filter('td')
                ->text();

            $enginePower = explode('}', $enginePower)[1] ?? null;

            return preg_replace('/[^0-9]/', '', $enginePower);

        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Получение строчки с описанием параметров двигателя
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseEngineDescription(Crawler $crawler): ?string
    {
        try {
            return $crawler
                ->filter('tbody')
                ->filter('tr')
                ->slice(0, 1)
                ->filter('td')
                ->text();
        } catch (\InvalidArgumentException $exception) {
            return null;
        }
    }


    /**
     * Парсинг объема двигателя
     *
     * @param Crawler $crawler
     * @return float|null
     */
    protected function parseEngineVolume(Crawler $crawler): ?float
    {
        $engineDescription = $this->parseEngineDescription($crawler);
        if ($engineDescription === null) {
            return null;
        }

        $engineVolume = explode(' ', $engineDescription)[1] ?? null;

        return preg_replace('/[^0-9.]/', '', $engineVolume);
    }


    /**
     * Парсинг типа топлива
     *
     * @param Crawler $crawler
     * @return string|null
     */
    protected function parseFuelType(Crawler $crawler): ?string
    {
        $engineDescription = $this->parseEngineDescription($crawler);
        if ($engineDescription === null) {
            return null;
        }

        $fuelType = explode(' ', $engineDescription)[0] ?? null;

        return preg_replace('/[^A-Za-z]/', '', $fuelType);
    }


    /**
     * Парсит URLы изображений
     *
     * @param Crawler $crawler
     * @return array
     */
    protected function parseImagesLinks(Crawler $crawler): array
    {
        $links = [];
        $crawler
            ->filter('[data-ftid="bull-page_bull-gallery_thumbnails"]')
            ->filter('a')
            ->each(function (Crawler $element) use (&$links) {
                $links[] = $element->attr('href');
            });

        return $links;
    }
}
