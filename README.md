# Zaveri Bazaar API

### Install and run

1. Run composer install in project root.

    ```
    php composer.phar install
    ```

2. Copy `.env.example` file to `.env` file.

    ```
    cp .env.example .env
    ```

3. Customize database configuration in `.env` file and create the database in mysql with `utf8mb4_unicode_ci` charset.

    ```
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=homestead
    DB_USERNAME=homestead
    DB_PASSWORD=secret
    ```

4. Run database migrations.

    ```
    php artisan migrate
    ```

5. Link storage public folder to `public/storage` for storing images.

    ```
    mkdir -p storage/app/public && ln -s ../storage/app/public/ public/storage
    ```

6. To serve the project on `http://0.0.0.0:8000`, run in project root.

    ```
    php -S 0.0.0.0:8000 -t public/
    ```

7. APIs are now accessible at http://localhost:8000 and your computer's ip with port 8000 (eg. http://192.168.0.100:8000)
