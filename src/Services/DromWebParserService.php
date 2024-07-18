<?php

namespace Default\DromDataMining\Services;

use Default\DromDataMining\Interfaces\DromParserInterface;
use Default\DromDataMining\Models\Car;
use ForceUTF8\Encoding;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Rct567\DomQuery\DomQuery;

class DromWebParserService implements DromParserInterface
{
    public const LIST_URL = 'https://auto.drom.ru/all/page';

    public function __construct(private Client $client)
    {
    }

    public function parse(): array
    {

        $cars = $this->processPage(1);

        return [];
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
            'pts' => 2
        ];
    }


    /**
     * Обрабатывает страницу с автомобилями
     * @param int $page
     * @return Car[]
     * @throws GuzzleException
     */
    protected function processPage(int $page): array
    {
        $response = $this->client->get(
            $this->getUrlForPage($page),
            [
                'query' => $this->getParams(),
                'headers' => [
                    'Accept-Encoding' => 'gzip, deflate',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9,ru-RU;q=0.8,ru;q=0.7',
                ]
            ]
        );

        $contents = $response->getBody()->getContents();
        $contents = mb_convert_encoding($contents, 'UTF-8','windows-1251' );

        $domDocument = new DomQuery($contents);

        $cars = [];
        foreach ($domDocument->find('[data-ftid="bulls-list_bull"]') as $domQuery) {
            $car = $this->processCar($domQuery);
            if ($car === null) {
                continue;
            }

            $cars[] = $car;
        };

        return [];
    }

    /**
     * Парсит блок на страницу автомобилей
     * @param DomQuery $domQuery
     * @return Car|null
     * @throws GuzzleException
     */
    protected function processCar(DomQuery $domQuery): ?Car
    {
        $link = $domQuery->find('a')->first()->attr('href');
        $id = $this->extractIdFromUrl($link);

        echo($domQuery->find('[data-ftid="bull_label_coming"]')->first()->html());

        $carPage = $this->client
            ->get($link)
            ->getBody()
            ->getContents();

        $domDocument = new DomQuery($carPage);
        $str = $domDocument->find('div:contains("в пути или на стоянке за границей")')->first();

        echo str_contains($carPage, 'в пути') . "\n";

        return null;
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
}
