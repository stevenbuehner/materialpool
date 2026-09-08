
## Wenn der Import eines Bundles Mucken macht

Tinker öffnen:
```shell
sudo -u www-data php /var/www/wissensbase/artisan tinker
```

In Tinker:
```php
$c = app(\App\Http\Controllers\Api\BundleImportController::class);
$b = Bundle::find(3);
$c->initUpdate($b);
```

Dann die Queue laufen lassen in der Shell:
```shell
sudo -u www-data php /var/www/wissensbase/artisan queue:work --tries=21  database --queue=default,bundle_3_queue
```
