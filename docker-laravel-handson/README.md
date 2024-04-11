# Docker + Laravel + nginx + MySQL

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