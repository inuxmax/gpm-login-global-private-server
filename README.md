# Latest release and update
- [Download](https://github.com/ngochoaitn/gpm-login-global-private-server/releases/tag/latest)

# For dev
## Run server test
```
composer update
composer install
php artisan serve --port=8081
```

## Test docker
```
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

## Docker builder
```
docker buildx create --name mybuilder --use --driver docker-container
docker buildx inspect --bootstrap

docker build -t ngochoaitn/gpm-login-global-private-server:latest .
docker push ngochoaitn/gpm-login-global-private-server:latest
```

## Docker publish
```
docker buildx build --platform linux/amd64,linux/arm64 -t ngochoaitn/gpm-login-global-private-server:latest --push .
```

## Create file update
`py create-zip-file.py`