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

## Reference
- [【超入門】20分でLaravel開発環境を爆速構築するDockerハンズオン](https://qiita.com/ucan-lab/items/56c9dc3cf2e6762672f4)