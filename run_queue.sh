php artisan queue:restart
php artisan queue:work --queue="low" --timeout=3600 --tries=1 &
php artisan queue:work --queue="default" --timeout=3600 --tries=1 &
