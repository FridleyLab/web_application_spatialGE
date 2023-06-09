php artisan queue:restart
start /B php artisan queue:work --queue="low" --timeout=1200 --tries=1
start /B php artisan queue:work --queue="default" --timeout=1200 --tries=1
