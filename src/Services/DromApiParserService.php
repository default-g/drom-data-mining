<?php

namespace Default\DromDataMining\Services;

use Default\DromDataMining\Exceptions\NotImplementedException;
use GuzzleHttp\Client;

use Default\DromDataMining\Interfaces\DromParserInterface;

/**
 * Сначала пробовал через реверс инженеринг и прокси
 */
class DromApiParserService implements DromParserInterface
{
    // URL API мобильного приложения
    public const URL = 'api.drom.ru';

    // Эндпоинт на получения списка автомобилей по параметрам
    public const CARS_LIST = '/v1.2/bulls/search';


    public function __construct(private readonly Client $client)
    {
        throw new NotImplementedException();
    }

    public function parse(): array
    {
        $parameters = $this->getParameters();
        $parameters['secret'] = $this->calculateSecretFromParameters($parameters);

        $response = $this->client->get(self::URL . self::CARS_LIST, [
            'query' => $parameters
        ]);

        return [];
    }


    public function calculateSecretFromParameters(array $parameters): string
    {
        $string = '';

        array_walk_recursive($parameters, function ($value, $key) use ($string) {
            $string .= $key . $value;
        });

        $string .= 'p32';
        return hash('sha256', $string);
        return 'b7f088626468195ec9905446ef06fc76fa6e01fafc3a4a56d6e357b521fd637b';
    }


    public function getParameters(): array
    {
        return [
            'multiselect' => ['9_4_16_all', '9_4_15_all'],
            'stickyRegionId' => ['25'],
            'cityId' => ['23', '170'],
            'sortBy' => 'enterdate',
            'revertSort' => 'true',
            'unsold' => '1',
            'withoutDocuments' => '2',
            'mainPhotoWidth' => ['320', 'original'],
            'onlyWithBulletinsCount' => 'false',
            'page' => '0',
            'pretty' => 'true',
            'thumbnailsWidth' => ['320', '600'],
            'version' => '3',
            'withModelsCount' => 'true',
            'recSysDeviceId' => '1d8c789e776d96d1edb13a3da74c343f',
            'recSysRegionId' => '54',
            'recSysCityId' => '109',
            'app_id' => 'p32',
            'timestamp' => '1721269249386',
        ];
    }
}
