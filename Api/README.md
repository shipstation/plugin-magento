# ShipStation
 
From Magento root folder : 
- Create app\code\Auctane\Api folder
- Extract the zip file into app\code\Auctane\Api
- Execute command php -f bin/magento setup:upgrade

## Authentication

- Generate an API key in Stores -> Configuration -> Shipstation -> Generate
  and save api key. 
  - If you need to setup ShipStation for multiple stores, a key needs to be generated for each stores by changing the [Magento store scope](https://docs.magento.com/user-guide/configuration/scope.html).
- In ShipStation, specify the api key for each store you want to manage.
