# Docker + Laravel + nginx + MySQL

## php.ini
`php.ini`にはphpの動作を制御するための設定を記述
```ini
;例外発生時に引数の値を表示するかどうかを設定。offの場合は引数の値が表示する
zend.exception_ignore_args = off

; HTTPヘッダーに「X-Powered-By: PHP」を追加するかどうかを設定
expose_php = on

; スクリプトの最大実行時間を30秒に設定
max_execution_time = 30

; 許容する最大の入力変数の数を1000に設定
max_input_vars = 1000

; アップロードできるファイルの最大サイズを64MBに設定
upload_max_filesize = 64M

; POST送信データの最大サイズを128MBに設定
post_max_size = 128M

; PHPスクリプトが使用可能なメモリ量の上限を256MBに設定
memory_limit = 256M

; すべてのエラーを報告するよう設定
error_reporting = E_ALL

; エラーをブラウザで表示するよう設定
display_errors = on

; PHPの起動時(スクリプト実行前)に発生したエラーをブラウザに表示するかどうかを制御
display_startup_errors = on

; エラーをログに記録するよう設定
log_errors = on

; エラーログの出力先を標準エラー出力に設定
error_log = /dev/stderr

; デフォルトの文字コードをUTF-8に設定
default_charset = UTF-8

; デフォルトのタイムゾーンをAsia/Tokyoに設定
[Date]
date.timezone = Asia/Tokyo

; MySQL Native Driverのメモリ使用統計を有効にする
[mysqlnd]
mysqlnd.collect_memory_statistics = on

; Zendアサーションを有効にする
[Assertion]
zend.assertions = 1

; マルチバイト文字列の言語をJapaneseに設定
[mbstring]
mbstring.language = Japanese
```

## Dockerfile
このDockerfileを使うと、PHP8.1の実行環境にComposerと必要な拡張モジュールがインストールされ、/dataディレクトリ以下でPHPアプリケーションを実行できる状態になる
```Dockerfile
# PHP 8.1の最新バージョンのFPMイメージ、DebianベースのBusterイメージを使用
FROM php:8.1-fpm-buster

# Composerがスーパーユーザーでインストールできるよう設定し、Composerのホームディレクトリを指定
ENV COMPOSER_ALLOW_SUPERUSER=1 \
  COMPOSER_HOME=/composer

# Composer公式イメージからComposerバイナリをコピー
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# RUN apt-get update && \: aptパッケージリポジトリを更新し、以下のパッケージをインストール
# git unzip libzip-dev libicu-dev libonig-dev: Git、Zipアーカイブの操作、国際化サポート、マルチバイト文字列の操作に必要なライブラリ
# docker-php-ext-install intl pdo_mysql zip bcmath: PHPの拡張モジュール(国際化、MySQL接続、ZIP圧縮/解凍、BCMath算術拡張)をインストール
# apt-get clean && rm -rf /var/lib/apt/lists/*: インストール時に生成された一時ファイルを削除し、イメージサイズを小さくする
RUN apt-get update && \
  apt-get -y install --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/* && \
  docker-php-ext-install intl pdo_mysql zip bcmath

# ビルド時のコンテキストからPHPの設定ファイルをコピー
COPY ./php.ini /usr/local/etc/php/php.ini

# コンテナの作業ディレクトリを/dataに設定
WORKDIR /data
```

### コンテナに入る
```bash
docker compose exec app bash
```

### コンテナの情報を取得
```bash
php -v      # phpバージョン情報
composer -v # composerバージョン情報
php -m      # インストール済みの拡張機能の一覧
```

## Nginx
- [Laravelにおけるngixの設定](https://readouble.com/laravel/8.x/ja/deployment.html)

`default.conf`は、仮想ホスト example.com の設定を定義。<br/>
ポート 80 でリクエストを待ち受け、ドキュメントルート /data/public 以下の静的ファイルを配信する。<br/>
PHP ファイルは app:9000 にある FastCGI プロセスに処理を委託。<br/>
セキュリティ対策やエラー処理なども設定。

```conf
server {
    # リッスン設定
    listen 80;               # ポート80でリクエストを待ち受ける
    server_name example.com; # ホスト名 example.com に対するリクエストのみ処理
    root /data/public;

    # セキュリティヘッダー
    add_header X-Frame-Options "SAMEORIGIN";     # X-Frame-Options ヘッダーを追加し、example.com ドメイン以外のフレーム内でのページ表示を禁止
    add_header X-XSS-Protection "1; mode=block"; # X-XSS-Protection ヘッダーを追加し、クロスサイトスクリプティング攻撃対策を有効にする
    add_header X-Content-Type-Options "nosniff"; # X-Content-Type-Options ヘッダーを追加し、ブラウザによるコンテンツタイプの推測を無効にする

    # インデックスファイル
    index index.php; # ディレクトリにアクセスした場合、最初に index.php ファイルを探すように設定

    # 文字エンコーディング
    charset utf-8; # レスポンスヘッダーの Content-type に utf-8 を指定し、文字エンコーディングを UTF-8 に設定

    # ファイル処理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 静的ファイル
    # favicon.ico, robots.txt にアクセスした場合、アクセスログとエラーログを出力しないように設定
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # エラー処理
    error_page 404 /index.php;

    # 静的ファイル
    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # その他のファイル
    location ~ /\.(?!well-known).* { # 拡張子が . で始まり、well-known ディレクトリ以下のファイルではないものに対してアクセスを拒否
        deny all;
    }
}

```

nginxの設定を記述後、再度`up`
```bash
$ docker compose up -d
```

Ports の項目が、appコンテナは9000/tcp でwebコンテナは 0.0.0.0:8080->80/tcp と表示形式が異なっている<br/>
ホスト上の8080番ポートをコンテナの80番ポートへ割り当てている
```bash
NAME                           IMAGE                        COMMAND                   SERVICE   CREATED         STATUS         PORTS
docker-laravel-handson-app-1   docker-laravel-handson-app   "docker-php-entrypoi…"   app       3 minutes ago   Up 3 minutes   9000/tcp
docker-laravel-handson-web-1   nginx:1.25.4-alpine          "/docker-entrypoint.…"   web       3 minutes ago   Up 3 minutes   0.0.0.0:8080->80/tcp
```

```bash
$ docker compose exec web nginx -v
nginx version: nginx/1.25.4
```

## Laravelをインストール

※bashにコマンドをコピーするときは、`shift + Insert`

```bash
$ docker compose exec app bash

$ composer create-project --prefer-dist "laravel/laravel=9.*" .
$ chmod -R 777 storage bootstrap/cache
$ php artisan -V
Laravel Framework 9.1.0

$ exit
```

## MySQL
Windows環境でボリュームマウントを行うと、ファイルパーミッションが777となる<br/>
my.cnf に書き込み権限が付いてるとMySQLの起動時にエラーが発生するため、<br/>
ボリュームマウントではなくDockerfileを作成して my.cnf ファイルコピー、読み取り専用に権限変更
```Dockerfile
FROM mysql/mysql-server:8.0

ENV MYSQL_DATABASE=laravel \
    MYSQL_USER=phper \
    MYSQL_PASSWORD=secret \
    MYSQL_ROOT_PASSWORD=secret \
    TZ=Asia/Tokyo

COPY ./my.cnf /etc/my.cnf
RUN chmod 644 /etc/my.cnf
```

```cnf
[mysqld]

# default
skip-host-cache            # DNSルックアップを行わず、名前解決をバイパスすることで起動時間を短縮する
skip-name-resolve          # 上記と同様の効果がある

datadir = /var/lib/mysql   # データディレクトリのパスを指定する
socket = /var/lib/mysql/mysql.sock  # ソケットファイルのパスを指定する 
secure-file-priv = /var/lib/mysql-files # LOAD DATA INFILEやSELECT ... INTO OUTFILE時の制限ディレクトリを設定

user = mysql   # MySQLサーバープロセスを実行するユーザーを指定

pid-file = /var/run/mysqld/mysqld.pid  # プロセスIDを格納するファイルのパスを指定

# character set / collation  
character_set_server = utf8mb4   # デフォルトの文字セットをutf8mb4に設定
collation_server = utf8mb4_ja_0900_as_cs_ks  # デフォルトの照合順序を日本語対応の順序に設定  

# timezone
default-time-zone = SYSTEM  # OSのタイムゾーンに合わせる
log_timestamps = SYSTEM   # ログのタイムスタンプをOSのタイムゾーンに合わせる

# Error Log
log-error = mysql-error.log  # エラーログの出力先を指定

# Slow Query Log  
slow_query_log = 1   # スロークエリログを有効化
slow_query_log_file = mysql-slow.log  # スロークエリログの出力先を指定    
long_query_time = 1.0  # スロークエリの閾値を1秒に設定
log_queries_not_using_indexes = 0  # インデックスを使わない問い合わせをログに出力しない

# General Log
general_log = 1    # 全ての問い合わせをログに出力する
general_log_file = mysql-general.log  # 全問い合わせログの出力先を指定

[mysql]
default-character-set = utf8mb4  # MySQL clientで使うデフォルトの文字セットをutf8mb4に設定

[client] 
default-character-set = utf8mb4 # その他のクライアントで使うデフォルトの文字セットをutf8mb4に設定
```

MySQL接続
```bash
mysql -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE
```

```bash
> show databases;
> show tables;
> desc users;
> SELECT * FROM users;
```

## laravel-debugbar
デバッグを便利にしてくれるライブラリ<br/>
`.env`の`APP_DEBUG=true`のときに表示される
```bash
composer require barryvdh/laravel-debugbar --dev
```

## Laravelの設定の変更が反映されないとき
```bash
php artisan config:clear
php artisan cache:clear
```

## モデル作成
```bash
php artisan make:model {model_name}
```

`php artisan make:model Test`と入力した場合、`app/Models/Test.php`が作成される
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;
}
```
コントローラーとマイグレーションも同時生成する場合
```bash
php artisan make:model Test -mc
```

## マイグレーション
`databases/migrations`<br/>
ファイル作成
```bash
php artisan make:migration create_tests_table
php artisan migrate // DBに反映
```

```bash
php artisan migrate:fresh   // テーブルを全て削除して再生成
php artisan migrate:refresh // ロールバックして再生成
```

## コントローラー作成
```bash
php artisan make:controller {contoller_name}
```
`php artisan make:controller TestController`と入力した場合、`app/Http/Controllers/TestController.php`が作成される
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TestController extends Controller
{
    //
}
```

## ルーティング
### ルーティング設定
`routes/web.php`
```php
use App\Http\Controllers\TestController; // ファイル内で使えるようにする
Route::get('tests/test', [TestController::class, 'index']);
```
### コントローラー設定
`App/Http/Controller/TestController.php`
```php
public function index()
{
    return view('tests.test');  // view()は、Laravelのヘルパ関数（フォルダ名.ファイル名で書く）
}
```
### View設定
`resouces/views/tests/test.blade.php`

## コントローラーでモデルを取得する
`App/Http/Controller/TestController.php`
```php
use App\Models\Test;

public function index()
{
    $models = Test::all(); // 全件取得
    
    dd($models); // die + var_dump 処理を止めて内容を確認できる

    return view('tests.test', compact($models));
}
```

## Reference
- [【超入門】20分でLaravel開発環境を爆速構築するDockerハンズオン](https://qiita.com/ucan-lab/items/56c9dc3cf2e6762672f4)
- [laravel-debugbar](https://github.com/barryvdh/laravel-debugbar)