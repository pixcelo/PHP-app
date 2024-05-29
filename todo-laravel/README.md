# Todoアプリ他、動作確認用

Docker起動確認
```bash
$ docker compose up -d
$ docker compose ps

NAME               IMAGE                          COMMAND                   SERVICE      CREATED        STATUS          PORTS
mysql_test         mysql:5.7.36                   "docker-entrypoint.s…"   db           11 hours ago   Up 34 seconds   0.0.0.0:3306->3306/tcp, 33060/tcp
nginx_test         nginx:latest                   "/docker-entrypoint.…"   nginx        11 hours ago   Up 32 seconds   0.0.0.0:80->80/tcp
node14.18-alpine   node:14.18-alpine              "docker-entrypoint.s…"   node         11 hours ago   Up 34 seconds
php-fpm            todo-laravel-php               "docker-php-entrypoi…"   php          11 hours ago   Up 34 seconds   9000/tcp
phpmyadmin_test    phpmyadmin/phpmyadmin:latest   "/docker-entrypoint.…"   phpmyadmin   11 hours ago   Up 32 seconds   0.0.0.0:8080->80/tcp
```

laravelインストール
```bash
$ docker compose exec php bash
$ composer create-project "laravel/laravel=9.*" LaravelTestProject --prefer-dist
```

## 調べること
- Laravelにおけるオートロードとは

## Reference
[Laravel9をDockerで導入してみよう!](https://zenn.dev/eguchi244_dev/articles/laravel-and-docker-introduction-20230822)