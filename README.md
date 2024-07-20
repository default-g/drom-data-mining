# Тестовое задание для Дром
Изначально я хотел попробовать выполнить парсинг через мобильное API и выявил похожие запросы с помощью mitmproxy. Для этого я декомпилировал приложение и пересобрал его без Certificate Pinning, чтобы прокси могло дешифровать трафик.Пересобранное приложение находится в директории `bin`
```
api.drom.ru/v1.2/bulls/search?colorId[]=4&colorId[]=16&colorId[]=12&colorId[]=1&colorId[]=7&colorId[]=9&multiselect[]=9_4_16_all&multiselect[]=9_4_15_all&stickyRegionId[]=25&cityId[]=23&cityId[]=170&sortBy=enterdate&revertSort=true&withoutDocuments=2&mainPhotoWidth[]=320&mainPhotoWidth[]=original&onlyWithBulletinsCount=false&page=0&pretty=true&thumbnailsWidth[]=320&thumbnailsWidth[]=600&version=3&withModelsCount=true&recSysDeviceId=1d8c789e776d96d1edb13a3da74c343f&recSysRegionId=54&recSysCityId=109&app_id=p32&timestamp=1721306553918&secret=1701919390bcc337f1fc96307b95a73c9d3bebf36b03f88f02134aecc4a4c345
```

Позже я понял, что необходимо каким-то образом подобрать параметр secret. Чтобы найти алгоритм, пришлось изучать обфусцированный код.
Для генерации secret используется некоторым образом сконкатенированные параметры запроса и некоторое значение (соль), после чего это хэшируется по алгоритму SHA256. Наработки находятся в классах `DromApiParserService` и `DromSecretBuilderService`

Впоследствии задание было выполнено парсингом веб-интерфейса
