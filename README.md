# OshiLog

<p align="center">
  <img src="public/img/logo/main.png" alt="OshiLog" width="280">
</p>

<p align="center">
  <strong>推し活の思い出を、まるごと記録。</strong><br>
  会場・天気・写真・感想まで、1日の推し活をアメコミ風のログに残すアプリです。
</p>

個人開発のポートフォリオ作品です。Laravel 12 / Livewire で、記録する楽しさが残る UI を目指しています。

---

## なにができるか

推し活の1日を、ばらばらのメモではなく **1つのログ** にまとめます。

| 残せるもの | 内容 |
|---|---|
| 公演ログ | ライブ / 舞台 / ミュージカル / イベント / ロケ地。日付・会場・天気・同行者・感想 |
| 会場 | 住所検索、地図ピン、最寄り駅、定番会場 |
| 写真 | 服装・ネイル・うちわ・その他。各カテゴリ4枚まで。拡大表示あり |
| 投稿カード | SNS用。スクラップブック / チケットの2スタイル |
| 持ち物 | 公演タイプごとの持ち物リスト |
| メンカラ | 推しの色でサイドバーや画面の雰囲気を切り替え |

使い方の流れはホームと同じです。

1. 会場を登録する  
2. 公演を記録する（日付と会場から天気が入る）  
3. 写真と持ち物を残す  
4. 公演一覧で月ごとに見返す  

---

## 見た目と、コードで工夫したところ

Flux UI の部品を、太い黒枠とクリーム地の漫画テーマで上書きしています。  
ユーザーが選んだメンカラは `data-member-color` と CSS 変数で画面全体に伝わります。

公開用に CSS も整理しました。Flux の上書きを `@layer` の外に置き、`!important` に頼らずテーマを保つようにしています。読み方は [docs/css.md](docs/css.md) にまとめています。

ほかに、コードを追うときのノートもあります。

- [会場の地図（Nominatim + Leaflet）](docs/venue-map-implementation.md)
- [天気の自動取得（Open-Meteo）](docs/weather-resolveWeather-part1.md)

---

## 技術構成

| 役割 | 採用 |
|---|---|
| サーバー | PHP 8.2+ / Laravel 12 |
| UI | Livewire Volt / Flux UI / Alpine.js |
| CSS | Tailwind CSS 4 / Vite |
| 地図 | Leaflet / OpenStreetMap（Nominatim） |
| 天気 | Open-Meteo |
| DB（ローカル） | SQLite |

認証あり。トップとログイン以外の記録画面はログイン後に使います。

---

## 動かし方

PHP、Composer、Node.js が必要です。

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

別のターミナルで:

```bash
php artisan serve
```

[http://127.0.0.1:8000](http://127.0.0.1:8000) を開きます。

`migrate --seed` すると、確認用ユーザーが入ります。

- メール: `oshi@example.com`
- パスワード: `password`

このユーザーはローカル確認用です。本番では使わないでください。

テスト:

```bash
php artisan test
```

---

## リポジトリに含めていないもの

公開しても問題ない状態にするため、次は入れていません。

- `.env` と `APP_KEY`
- SQLite の実データ
- アップロードした写真
- 本番サーバーの接続情報

デプロイ用スクリプト本体は `scripts/deploy.ps1` です。  
ホスト名・SSH鍵・パスは `scripts/deploy.local.ps1.example` をコピーして、各自の `deploy.local.ps1` に書きます。このファイルは git 対象外です。

---

## ライセンス

コードは MIT ライセンスです。ロゴ画像は OshiLog 用に作成したものです。
