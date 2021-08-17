# Get started with the OData Client for PHP

See the original repository here: https://github.com/saintsystems/odata-client-php

## DSM specific additions
### Usage
#### Getting data
```php
<?php

use SaintSystems\OData\ODataClient;

$client = ODataClient::dsmFactory(
	'Tenant Company Id', 'Tenant Name', 'Tenant Base Url',
	'Tenant Username', 'Tenant Password', 'Tenant Api Version',
	false // Verify ssl
);

$client->from('contacts')->where('E_Mail', 'contact email')->get();
```
#### Updating data
#### Creating data
#### Deleting data
