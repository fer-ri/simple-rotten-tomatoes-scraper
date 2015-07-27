## Simple Rotten Tomatoes Scraper

Its really simple scraper, just return title and description from first search result, no more!

### Usage

Check `demo.php` for usage

```php
<?php

require __DIR__ . '/RottenTomatoes.php';

$rt	= new RottenTomatoes;

try {
	var_dump($rt->search('ant'));
} catch (Exception $e) {
	var_dump($e->getMessage());
}
```


