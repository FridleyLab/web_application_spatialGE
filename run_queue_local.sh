#!/bin/bash

# Load environment variables from the .env file
if [ -f ./.env ]; then
    source ./.env
else
    echo ".env file not found."
    exit 1
fi

php artisan queue:restart

if [ "$APP_ENV" == "production" ]; then
    WORKERS=8
else
    WORKERS=2
fi

for ((i=1; i<=WORKERS; i++)); do
    php artisan queue:work --queue="default" --timeout=259200 --tries=1 &
done
