# おしログの地図機能を作った話 ── 初心者向けにコードと用語を解説

## はじめに

この記事では、Laravel アプリ「おしログ」に実装した **会場の地図機能** について、  
プログラミング初心者や中学生の方でも読めるように解説します。

「地図が表示される仕組み」だけでなく、  
コードに出てくる **英単語の意味** や **`resolveGeocode` って何？** といった疑問にも答えます。

---

## 地図機能は何をしているの？

一言で言うと、こういう機能です。

> **会場名や住所を入力すると、場所を自動で探して、地図にピンを刺す**

Google マップで場所を検索するイメージに近いです。  
ただし、このアプリでは **Google マップではなく、無料の OpenStreetMap** を使っています。

### ユーザーから見た流れ

1. 会場登録画面で「横浜アリーナ」などと入力する
2. 少し待つと、地図にピンが表示される
3. 緯度・経度（数字）も自動で入る
4. 「登録する」を押すと、場所の情報ごと保存される

---

## 登場するファイル一覧

地図機能は、主に **4つのファイル** で動いています。

| ファイル | 言語 | 役割 |
|----------|------|------|
| `resources/views/livewire/venues/form.blade.php` | PHP | 入力フォーム。場所を探す処理の起点 |
| `app/Services/GeocodingService.php` | PHP | 会場名・住所 → 座標に変換 |
| `resources/views/components/venue-map.blade.php` | HTML | 地図を置く「空の箱」 |
| `resources/js/venue-map.js` | JavaScript | 実際に地図を描画 |

たとえるなら：

- **form.blade.php** … 受付窓口
- **GeocodingService.php** … 住所辞典
- **venue-map.blade.php** … 額縁（地図を入れる枠）
- **venue-map.js** … 地図を描く係

---

## 全体の流れ（図解）

```
【画面】会場名「横浜アリーナ」を入力
    ↓
【form.blade.php】updatedName() が動く
    ↓
【form.blade.php】resolveGeocode() が場所を探す
    ↓
【GeocodingService】Nominatim API に問い合わせ
    ↓
【返事】緯度 35.51、経度 139.62
    ↓
【form.blade.php】dispatchVenueMapUpdate() で JS に合図
    ↓
【venue-map.js】地図のピンを移動
    ↓
【ユーザー】「登録する」をクリック
    ↓
【DB】venues テーブルに座標を保存
```

---

## 用語集（先に読んでおくとスムーズ）

コードを読む前に、よく出てくる単語を押さえておきましょう。

### 地図・位置に関する言葉

| 用語 | 読み方 | 意味 |
|------|--------|------|
| **緯度（latitude）** | いど | 南北の位置を表す数字。例: `35.51` |
| **経度（longitude）** | けいど | 東西の位置を表す数字。例: `139.62` |
| **座標** | ざひょう | 緯度と経度のセット。地図上の「点」の位置 |
| **ジオコーディング** | geocoding | 住所や名前を、座標（数字）に変換すること |
| **マーカー** | marker | 地図上のピン。「ここ！」という印 |

### 技術・サービスに関する言葉

| 用語 | 意味 |
|------|------|
| **OpenStreetMap（OSM）** | 世界中の人が作った無料の地図データ。地図の「素材」 |
| **Nominatim** | OSM のデータを使った **場所検索 API**。住所 → 座標の変換に使う |
| **API** | プログラム同士がやり取りする「窓口」。質問を送ると返事が返ってくる |
| **Leaflet** | Web ページに地図を表示するための JavaScript ライブラリ |
| **Livewire** | Laravel で、入力フォームなどをリアルタイムに更新できる仕組み |
| **Blade** | Laravel の HTML テンプレート。`.blade.php` ファイル |

### コードの書き方に関する言葉

| 用語 | 意味 |
|------|------|
| **関数（function）** | 決まった処理をまとめたもの。名前を付けて何度でも呼べる |
| **メソッド（method）** | クラスの中にある関数のこと |
| **private** | 「このクラスの中だけで使う」という意味。外から直接呼べない |
| **dispatch** | Livewire から JavaScript へ「合図」を送ること |
| **wire:ignore** | Livewire に「この部分は書き換えないで」と伝える指定 |
| **debounce（デバウンス）** | 入力が止まってから一定時間後に処理する。打鍵のたびに検索しない |

---

## ① 入力フォーム（form.blade.php）

### 会場名・住所の入力欄

```html
<flux:input wire:model.live.debounce.750ms="name" label="会場名" required />
<flux:input wire:model.live.debounce.750ms="address" label="住所" required />
```

**`wire:model.live.debounce.750ms` とは？**

- `wire:model` … 入力欄と PHP の変数（`$name` など）をつなぐ
- `live` … 入力するたびにサーバーへ通知する
- `debounce.750ms` … **750ミリ秒（0.75秒）入力が止まってから** 処理する

なぜ待つのか？  
1文字打つたびにインターネットへ問い合わせると、遅くなるし、相手のサーバーにも負担がかかるからです。

### 地図部品の配置

```html
<x-venue-map
    id="venue-form-map"
    :latitude="$latitude"
    :longitude="$longitude"
    :name="$name"
/>
```

**`<x-venue-map>` とは？**

Blade コンポーネント（部品）です。  
`venue-map.blade.php` の中身を、ここに差し込んでいます。

- `:latitude="$latitude"` … 今わかっている緯度を渡す
- `id="venue-form-map"` … 地図の HTML 要素につける名前（後で JS から探す）

---

## ② `updatedName` / `updatedAddress` とは？

```php
public function updatedAddress(GeocodingService $geocoding): void
{
    $this->resolveGeocode($geocoding);
}

public function updatedName(GeocodingService $geocoding): void
{
    $this->resolveGeocode($geocoding);
}
```

### 名前のルール（Livewire の決まり）

Livewire には、こんな **命名ルール** があります。

| 変数名 | 自動で呼ばれるメソッド |
|--------|------------------------|
| `$name` | `updatedName()` |
| `$address` | `updatedAddress()` |

つまり、ユーザーが **会場名を変えたら `updatedName()`**、  
**住所を変えたら `updatedAddress()`** が自動で実行されます。

### なぜ2つとも `resolveGeocode` を呼ぶの？

会場名でも住所でも、「場所を探す処理」は同じだからです。  
同じ処理を2か所に書くと長くなって読みにくいので、  
**`resolveGeocode` にまとめた** というわけです。

---

## ③ `resolveGeocode` をやさしく読む

ここが記事のメインです。  
`resolveGeocode` は **「場所を探して、結果をフォームと地図に反映する」** 処理です。

### 名前の意味

| 部分 | 意味 |
|------|------|
| **resolve** | 「解決する」「決定する」 |
| **Geocode** | ジオコーディング（住所 → 座標変換）の略 |

合わせると **「ジオコーディングを実行して、座標を決める」** という意味です。

### コード全文

```php
private function resolveGeocode(GeocodingService $geocoding): void
{
    $this->geocodeError = '';

    if (trim($this->name) === '' && trim($this->address) === '') {
        return;
    }

    $result = $geocoding->geocodeVenue($this->name, $this->address);

    if ($result === null) {
        $this->geocodeError = '会場名・住所から位置を特定できませんでした。...';
        return;
    }

    $this->latitude = (string) $result['latitude'];
    $this->longitude = (string) $result['longitude'];

    $this->dispatchVenueMapUpdate();
}
```

### 1行ずつ解説

#### `private function resolveGeocode(...)`

- `private` … このクラスの **中だけ** で使う関数
- `GeocodingService $geocoding` … 場所を探す専門クラスを受け取る（Laravel が自動で用意）

#### `$this->geocodeError = '';`

前回のエラーメッセージを **消す** 処理です。  
新しく検索し直すたびに、エラー表示をリセットします。

#### `if (trim($this->name) === '' && trim($this->address) === '')`

- `trim()` … 文字の前後の空白（スペース）を取り除く
- 会場名も住所も **空っぽなら、検索しない** で終了

#### `$result = $geocoding->geocodeVenue(...)`

GeocodingService に「この会場名と住所で場所を探して」と依頼します。

#### `if ($result === null)`

`null` は **「何も見つからなかった」** という意味です。  
その場合はエラーメッセージを表示して終了します。

#### `$this->latitude = ...`

見つかった座標を、フォームの **緯度・経度の入力欄** にセットします。

#### `$this->dispatchVenueMapUpdate()`

座標が決まったら、**地図を更新する処理** を呼びます（次の節）。

---

## ④ `dispatchVenueMapUpdate` ── PHP から JavaScript へ合図

```php
private function dispatchVenueMapUpdate(): void
{
    $this->dispatch(
        'venue-map-updated',
        lat: (float) $this->latitude,
        lng: (float) $this->longitude,
        name: $this->name,
        id: 'venue-form-map',
    );
}
```

### なぜこれが必要？

- **PHP** … サーバー側で動く（場所を探す、DB に保存する）
- **JavaScript** … ブラウザ側で動く（地図を描く）

地図は JavaScript（Leaflet）で描いているので、  
PHP が座標を見つけたあと、**JavaScript に「地図を更新して！」と知らせる** 必要があります。

### `dispatch` とは？

Livewire の **イベント送信** 機能です。

- イベント名: `venue-map-updated`（「地図を更新して」という合図）
- 一緒に送るデータ: 緯度、経度、会場名、地図の ID

たとえるなら、PHP が JavaScript に **メモを渡す** イメージです。

---

## ⑤ GeocodingService ── 場所を探す専門クラス

### 会場名 → 住所の順で探す

```php
public function geocodeVenue(string $name, string $address): ?array
{
    $name = trim($name);
    $address = trim($address);

    foreach (array_filter([$name, $address]) as $query) {
        $result = $this->geocode($query);

        if ($result !== null) {
            return $result;
        }
    }

    return null;
}
```

**なぜ会場名を先に？**

OpenStreetMap では、

- `横浜アリーナ`（会場名）→ **見つかる**
- `神奈川県横浜市西区新宿2-8-12`（住所だけ）→ **見つからないことが多い**

という違いがあるためです。  
有名な会場は **名前の方が登録されている** ことが多いのです。

### Nominatim API への問い合わせ

```php
$response = $this->client()->get('https://nominatim.openstreetmap.org/search', [
    'q' => $query,
    'format' => 'json',
    'limit' => 1,
    'countrycodes' => 'jp',
]);
```

| パラメータ | 意味 |
|------------|------|
| `q` | 検索したい文字（会場名や住所） |
| `format` | 返事の形式（JSON = プログラムが読みやすい形式） |
| `limit` | 最大何件返すか（1件だけで十分） |
| `countrycodes` | 国を限定（`jp` = 日本） |

返ってきた `lat` と `lon` を、緯度・経度として使います。

---

## ⑥ 地図を置く HTML（venue-map.blade.php）

```html
<div
    wire:ignore
    id="{{ $id }}"
    class="h-64 w-full rounded-lg border ..."
    data-lat="{{ $latitude }}"
    data-lng="{{ $longitude }}"
    data-name="{{ $name }}"
    data-venue-map
></div>
```

### ポイント

| 属性 | 意味 |
|------|------|
| `wire:ignore` | Livewire がこの部分を書き換えない。地図が壊れないようにする |
| `data-lat` / `data-lng` | 座標を HTML にメモしておく |
| `data-venue-map` | 「ここは地図だよ」という目印。JS がこれを探す |

中身は空っぽの `<div>` です。  
**実際の地図は JavaScript が後から描き込みます。**

---

## ⑦ 地図を描く JavaScript（venue-map.js）

### 地図の背景を表示

```javascript
const map = L.map(element);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
}).addTo(map);
```

- `L.map` … Leaflet で地図を作る
- `tileLayer` … OpenStreetMap の **地図タイル**（小さな地図の絵の集まり）を重ねる

### ピンを刺す

```javascript
map.setView([latitude, longitude], 15);
marker = L.marker([latitude, longitude]).addTo(map);
marker.bindPopup(name);
```

| コード | 意味 |
|--------|------|
| `setView` | 地図の中心をその場所に移動。`15` は拡大レベル |
| `L.marker` | ピン（マーカー）を刺す |
| `bindPopup` | ピンをクリックすると会場名が出る |

### PHP からの合図を受け取る

```javascript
Livewire.on('venue-map-updated', ({ lat, lng, name, id }) => {
    const element = document.getElementById(id);

    if (element) {
        updateVenueMap(element, lat, lng, name ?? '');
    }
});
```

1. PHP が `venue-map-updated` という合図を送る
2. JavaScript がそれを聞く（`Livewire.on`）
3. `updateVenueMap` でピンの位置を動かす

---

## ⑧ 保存するとき

地図そのものは DB には保存しません。  
**緯度・経度の数字** を `venues` テーブルに保存します。

```php
'latitude' => ['required', 'numeric', 'between:-90,90'],
'longitude' => ['required', 'numeric', 'between:-180,180'],
```

あとで会場詳細ページを開くと、保存した座標を `<x-venue-map>` に渡して、  
また同じ仕組みで地図が表示されます。

---

## うまく表示されないとき

| 状況 | 対処法 |
|------|--------|
| 会場名が略称 | 正式名称で入力（例: `横浜アリーナ`） |
| 住所だけでは見つからない | 会場名も一緒に入力する |
| 小さな会場 | OSM に未登録の可能性あり。緯度・経度を手入力 |
| 地図は出ないが登録できる | 緯度・経度を手で入れれば保存可能 |

---

## まとめ

| 処理 | 担当 | やっていること |
|------|------|----------------|
| 入力を受け取る | `form.blade.php` | 会場名・住所が変わったら `resolveGeocode` を呼ぶ |
| 場所を探す | `GeocodingService` | Nominatim API で座標を取得 |
| 地図を更新する合図 | `dispatchVenueMapUpdate` | PHP → JavaScript へイベント送信 |
| 地図を描く | `venue-map.js` | Leaflet でピンを表示・移動 |
| 保存 | `save()` | 緯度・経度を DB に保存 |

**`resolveGeocode` の役割を一言で言うと：**

> 「会場名と住所から座標を探し、見つかったらフォームと地図に反映する司令塔」

---

## 参考：関連ファイルのパス

```
app/Services/GeocodingService.php
resources/views/livewire/venues/form.blade.php
resources/views/components/venue-map.blade.php
resources/js/venue-map.js
resources/js/app.js          ← venue-map.js を読み込んでいる
config/services.php          ← Nominatim の設定
```

---

## おわりに

地図機能は、一見複雑に見えますが、やっていることはシンプルです。

1. **文字（会場名・住所）を座標（数字）に変える**
2. **座標をもとに地図にピンを刺す**
3. **座標を保存する**

コードの英単語は、だいたい「何をする処理か」が名前になっています。  
分からない単語が出てきたら、この記事の用語集に戻ってみてください。
