# 勤怠管理システム（attendance-system）

Laravel を使用した勤怠管理アプリケーションです。Docker を使用して開発環境を構築します。

## 本システムで利用可能な機能
本システムでは、一般ユーザが日々の勤怠登録・修正申請を行い、勤怠管理者が申請内容の確認・承認を行うことで、勤怠情報を適切に管理できます。

### 【勤怠管理者】

- **ログイン&ログアウト画面**

   管理者アカウントでログイン・ログアウトを行う。

- **勤怠一覧画面**

   全スタッフの勤怠データを日付ごとに一覧表示する。

- **勤怠詳細画面**

   選択した勤怠情報の詳細を確認する。

- **スタッフ一覧画面**

   登録されているスタッフの一覧を確認する。

- **スタッフ別勤怠一覧画面**

   スタッフごとの勤怠履歴を絞り込んで確認する。

- **申請一覧画面**

   スタッフからの修正申請を一覧で確認する。

- **修正申請承認画面**

   修正申請の内容を確認し、承認・却下を行う。

### 【一般ユーザ】

- **会員登録画面**

   一般ユーザとして利用するための新規会員登録を行う。

- **ログイン&ログアウト画面**

   一般ユーザアカウントでログイン・ログアウトを行う。

- **メール認証画面**

   登録メールアドレスの認証を行い、アカウントを有効化する。

- **出勤登録画面**

   出勤・退勤など当日の勤怠情報を登録する。

- **勤怠一覧画面**

   自身の勤怠履歴を一覧で確認する。

- **勤怠詳細画面**

   自身の勤怠データの詳細を確認する。

- **申請一覧画面**

   自身が提出した修正申請の状況を確認する。


## 画面イメージ

**出勤登録画面**

<img src="docs/出勤登録画面.png" alt="出勤登録画面" width="480">

**勤怠一覧画面**

<img src="docs/勤怠一覧画面.png" alt="勤怠一覧画面" width="480">

## 使用技術

### フロントエンド・バックエンド

- **PHP**: 8.1
- **Framework**: Laravel 8.x

### インフラ・データベース

- **Docker / Docker Compose**
- **Web Server**: Nginx
- **Database**: MySQL 8.0
- **Database Management**: phpMyAdmin

## ER 図

![データベース ER 図](docs/database_er_diagram.png)

## 環境構築手順

### 前提条件

- Docker Desktop (または Docker Engine + Docker Compose) がインストールされていること。

### 構築ステップ

1. **リポジトリのクローン**

   ローカルにディレクトリを作成のうえ、ディレクトリ上で下記コマンドを実行します。

   ```bash
   git clone git@github.com:nyomya7952-boop/attendance-system.git
   ```

2. **環境設定ファイルの作成**

   attendance-systemディレクトリ上で、`src` ディレクトリにある `.env.example` をコピーして `.env` を作成します。

   Linux/Mac:

   ```bash
   cp src/.env.example src/.env
   ```

   `src/.env` をエディタで開き、下記の通り修正します。

   ```ini
   // データベース設定（`docker-compose.yml` の設定に合わせて修正）
   DB_CONNECTION=mysql
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=laravel_db
   DB_USERNAME=laravel_user
   DB_PASSWORD=laravel_pass
   ```

   ```ini
   // メール認証設定
   MAIL_MAILER=smtp
   MAIL_HOST=mailhog
   MAIL_PORT=1025
   MAIL_USERNAME=null
   MAIL_PASSWORD=null
   MAIL_ENCRYPTION=null
   MAIL_FROM_ADDRESS="noreply@example.com"
   MAIL_FROM_NAME="${APP_NAME}"
   ```

   ※ .env ファイルは Git 管理対象外です。

3. **Docker コンテナの起動**

   プロジェクトルートで以下のコマンドを実行します。

   ```bash
   docker-compose up -d --build
   ```

4. **依存関係のインストールとセットアップ**

   PHP コンテナに入り、Composer パッケージのインストールと Laravel の初期設定を行います。

   ```bash
   docker-compose exec php bash
   ```

   コンテナ内で以下を実行します:

   ```bash
   # 依存ライブラリのインストール
   composer install

   # アプリケーションキーの生成
   php artisan key:generate

   # データベースのマイグレーション
   php artisan migrate

   # シーダーの実行
   php artisan db:seed
   ```

   完了したら `exit` でコンテナから抜けます。

5. **アプリケーションへのアクセス**

   ブラウザで以下の URL にアクセスして確認します。
   - **アプリケーション(一般ユーザ)**: [http://localhost/login](http://localhost/login)
   - **アプリケーション(管理者ユーザ)**: [http://localhost/admin/login](http://localhost/admin/login)
   - **phpMyAdmin**: [http://localhost:8080](http://localhost:8080)

   ※error で画面を開けない場合、下記コマンドを実行してください。

   ```bash
   sudo chmod -R 777 src/*
   ```

## PHPUnit テスト手順

1. **テスト用データベースの作成**

   mysql コンテナ上で下記コマンドを実行します。

   ```bash
   # 依存ライブラリのインストール
   mysql -u root -p
   CREATE DATABASE demo_test;

   # demo_testが作成されていることを確認
   SHOW DATABASES;
   ```

2. **設定ファイルの作成**

   attendance-systemディレクトリ上で、`src` ディレクトリにある `.env` をコピーして `.env.testing` を作成します。

   Linux/Mac:

   ```bash
   cp src/.env src/.env.testing
   ```

   `src/.env.testing` をエディタで開き、下記の通り修正します。

   ```ini
   APP_ENV=test
   APP_KEY=
   ```

   ※APP_KEY は空を設定する

   ```ini
   // データベース設定
   DB_CONNECTION=mysql_test
   DB_HOST=mysql
   DB_PORT=3306
   DB_DATABASE=demo_test
   DB_USERNAME=root
   DB_PASSWORD=root
   ```

   設定後、下記コマンドを実行します。

   ```bash
   php artisan key:generate --env=testing
   php artisan config:clear
   php artisan migrate --env=testing
   ```

3. **PHPUnit の実行**

   php コンテナで下記コマンドを実行します。

   ```bash
   php artisan test
   ```
