# Fizzy

## Installation
Create a composer.json file in your project root.
```
composer init
```

Add your namespace directory to your composer.json file.
```json
"autoload": {
    "psr-4": {
        "MyNamespace\\": "src/MyNamespace"
    }
},
```

Require Fizzy.
```
composer require ethanhann/fizzy
```

## Configuration

Copy the config.dist.json to the project root. Adjust the baseUrl and namespacePrefix as appropriate.
Note that the config file can be called anything, but the name in the index.php file will need to be updated.

```json
{
  "baseUrl": "api",
  "namespacePrefix": "MyNamespace",
  "httpMethodNames": ["get", "getList", "post", "put", "delete"],
  "contentNegotiation" : {
    "priorities": ["json", "xml"]
  }
}
```


## Run the App
```php
<?php
// web/index.php
$loader = require_once __DIR__ . '/../vendor/autoload.php';
(new \Eeh\Fizzy\App('../config.json', $loader))
    ->configure()
    ->run();
```
