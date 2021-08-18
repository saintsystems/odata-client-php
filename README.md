# Get started with the OData Client for PHP

See the original repository here: https://github.com/saintsystems/odata-client-php

## DSM specific additions
### Usage

#### Constructing client
```php
<?php

use SaintSystems\OData\ODataClient;

$client = ODataClient::dsmFactory(
	'Tenant Company Id', 'Tenant Name', 'Tenant Base Url',
	'Tenant Username', 'Tenant Password', 'Tenant Api Version',
	false // Verify ssl
);
```

#### Getting data
```php
<?php
...
$result = $client->from('contacts')->where('E_Mail', 'contact email')->get();
```

#### Updating data
First you must obtain an etag from the row you want to update, then after that you can update the row like so
```php
...
$contact = $client->from("contacts(Field='123456789')")->first();

$client->patch("contacts(Field='123456789')", [
	"Other_Field" => "test"
], $contact['@odata.etag']);
```

#### Creating data
This has not been used yet

#### Deleting data
This has not been used yet
