php artisan queue:restart
php artisan queue:work --queue="low" --timeout=10800 --tries=1 &
php artisan queue:work --queue="default" --timeout=10800 --tries=1 &
