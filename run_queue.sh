#!/bin/bash

# Load environment variables from the .env file
if [ -f .env ]; then
    source .env
else
    echo ".env file not found."
    exit 1
fi

chown -R apache:apache storage/
sudo -u apache php artisan queue:restart
#sudo -u apache php artisan queue:work --queue="low" --timeout=259200 --tries=1 &
#sudo -u apache php artisan queue:work --queue="low" --timeout=259200 --tries=1 &

if [ "$APP_ENV" == "production" ]; then
    WORKERS=8
else
    WORKERS=2
fi

for ((i=1; i<=WORKERS; i++)); do
    sudo -u apache php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
done
