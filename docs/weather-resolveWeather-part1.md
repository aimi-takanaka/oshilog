# 天気機能のコード読解ノート（前編）── `resolveWeather` ①〜③

## はじめに

この記事は、おしログの「公演を記録」画面にある **天気自動取得** のコードを、  
初心者向けに **1行ずつ読んでいく学習ノート** です。

前編では `resolveWeather` の **①〜③** までを扱います。

| ステップ | 内容 |
|----------|------|
| **①** | Open-Meteo に問い合わせる（`fetchForDate`） |
| **②** | 失敗したときのエラー表示 |
| **③** | 成功したとき、フォームに値を入れる |
| ④以降 | 次回（`WeatherService` の中身、保存、表示など） |

---

## この記事の読み方

- コードは **実際のプロジェクト**（`resources/views/livewire/events/form.blade.php`）から抜粋しています
- **「自分の理解 → 訂正」** も載せています。間違えても OK な学び方です
- 完璧に読めなくても **7割理解で合格** です

---

## 全体像：天気取得はどこから始まる？

天気を取る処理の入口は **3つ** ありますが、**中身は全部同じ** です。

```php
public function updatedVenueId(WeatherService $weather): void
{
    $this->resolveWeather($weather);
}

public function updatedEventDate(WeatherService $weather): void
{
    $this->resolveWeather($weather);
}

public function refreshWeather(WeatherService $weather): void
{
    $this->resolveWeather($weather);
}
```

| メソッド | いつ動く？ |
|----------|------------|
| `updatedVenueId` | **会場**を変えたとき（Livewire が自動で呼ぶ） |
| `updatedEventDate` | **日付**を変えたとき（Livewire が自動で呼ぶ） |
| `refreshWeather` | **再取得**ボタンを押したとき |

### よくある誤解（Q&A）

**Q: `updatedVenueId` は「会場の天気」、`updatedEventDate` は「日付の天気」を取る？**

**A: 違います。** どちらも **同じ `resolveWeather()`** を呼びます。

天気は **「会場の位置 + 公演日」** のセットで1回取得します。

- 会場だけ変えた → 同じ日付で、新しい会場の天気を取り直す
- 日付だけ変えた → 同じ会場で、新しい日付の天気を取り直す

---

## 準備段階：①の前にやっていること

`resolveWeather` の **最初の4ブロック** は、「天気 API を呼ぶ準備ができているか」を確認する段階です。

```php
private function resolveWeather(WeatherService $weather): void
{
    $this->weatherError = '';

    if ($this->venue_id === '' || $this->event_date === '') {
        return;
    }

    $venue = Venue::query()
        ->forDefaultUser()
        ->find($this->venue_id);

    if ($venue === null) {
        return;
    }

    // ここから① fetchForDate ...
}
```

### 1行ずつ + 学習中の Q&A

#### `$this->weatherError = '';`

**自分の理解:** エラーの場合は空欄

**訂正:** **違います。** これは「エラーが出たから空にする」のではなく、  
**「天気を取り直す前に、前回のエラーメッセージを消す」** 処理です。

```
再取得や会場変更
    ↓
まずエラー表示をリセット
    ↓
API を呼ぶ
    ↓
失敗したら → 新しいエラーメッセージを入れる
```

#### 会場・日付が空のとき

```php
if ($this->venue_id === '' || $this->event_date === '') {
    return;
}
```

**自分の理解:** 会場名または日付が入っていない場合は空欄

**訂正:** **「空欄にする」ではなく「何もせず終了」** です。

- 天気欄をわざわざ空にする処理はしていない
- API も呼ばない
- 会場と日付が **両方そろって初めて** ① に進む

#### 会場を DB から取得

```php
$venue = Venue::query()
    ->forDefaultUser()
    ->find($this->venue_id);
```

**自分の理解:** デフォルトユーザーが作った会場 ID を使用

**訂正:** **ほぼ合っています。** もう少し正確には：

- フォームで選んだ **`$this->venue_id`** の会場を探す
- **`forDefaultUser()`** で、デフォルトユーザーの会場だけに絞る（他人の会場は取れない）

#### 会場が見つからないとき

```php
if ($venue === null) {
    return;
}
```

**自分の理解:** 会場が存在しない場合

**訂正:** **合っています。** ID が存在しない、または別ユーザーの会場の場合も `null` になり、何もせず終了します。

---

## ① Open-Meteo に問い合わせる

```php
$result = $weather->fetchForDate(
    (float) $venue->latitude,
    (float) $venue->longitude,
    $this->event_date,
);
```

### 何をしている？

**会場の緯度・経度と公演日** を Open-Meteo（無料の天気 API）に送り、  
その日その場所の天気データを **1回でまとめて** もらいます。

### 渡している3つの情報

| 引数 | 意味 | 例 |
|------|------|-----|
| `$venue->latitude` | **緯度**（南北の位置） | 35.6812 |
| `$venue->longitude` | **経度**（東西の位置） | 139.7671 |
| `$this->event_date` | **公演日** | 2026-08-30 |

### 学習中の Q&A

**Q: 経度・緯度・日付を元に、データをひとつずつ持ってくる？**

**A: 方向は合っていますが、2点修正：**

1. **順番** … コード上は **緯度 → 経度 → 日付**（経度が先ではない）
2. **一度に取る** … 1回の API で **天気・最高気温・最低気温・降水確率** をまとめて `$result` に入れる（1項目ずつ別々ではない）

### `(float)` とは？

DB から読んだ値を **小数の数値** として使うための変換です。

---

## ② 失敗したとき：エラーメッセージ

```php
if ($result === null) {
    $this->weatherError = '天気情報を取得できませんでした。手入力するか、再取得を試してください。';

    return;
}
```

### 何をしている？

API が失敗した、またはデータが取れなかったとき、  
`$result` は **`null`（何もない）** になります。

その場合：

1. 画面に **赤いエラーメッセージ** を表示する（`$weatherError`）
2. **`return` で処理を止める**（③ には進まない）

### 学習中の Q&A

**Q: 何も値が存在しない場合はメッセージを表示する？**

**A: 合っています。** このときユーザーは **手入力** か **再取得ボタン** で対応できます。

---

## ③ 成功したとき：フォームに値を入れる

```php
$this->weather = $result['weather'];
$this->temperature_max = (string) $result['temperature_max'];
$this->temperature_min = (string) $result['temperature_min'];
$this->precipitation_probability = $result['precipitation_probability'] !== null
    ? (string) $result['precipitation_probability']
    : null;
```

### 何をしている？

API から返ってきた `$result` の中身を、  
画面の入力欄用の変数（`$this->weather` など）に **コピー** しています。

| 変数 | 画面の項目 | 例 |
|------|------------|-----|
| `$this->weather` | 天気 | 晴れ |
| `$this->temperature_max` | 最高気温 | 28.5 |
| `$this->temperature_min` | 最低気温 | 22.1 |
| `$this->precipitation_probability` | 降水確率 | 10 |

### `(string)` とは？

数値を **入力欄用の文字列** に変換しています。

- API: `28.5`（数値）
- フォーム: `"28.5"`（文字列）

Livewire の入力欄では、文字列として持つことが多いです。

### 降水確率の `? :`（三項演算子）

```php
$this->precipitation_probability = $result['precipitation_probability'] !== null
    ? (string) $result['precipitation_probability']
    : null;
```

**読み方:**

```
降水確率の値がある？
  ├─ はい → 文字列にしてフォームに入れる
  └─ いいえ → null（空のまま）
```

### 学習中の Q&A

**Q: 天気、最高気温、最低気温を入れる？**

**A: 合っています。**

**Q: 降水量が表示される場合値が入る。`?` で表示されない場合は空欄？**

**A: `? :` の考え方は合っています。** 用語だけ修正：

| 言葉 | 意味 |
|------|------|
| **降水確率**（このコード） | 雨が降る **可能性（%）** |
| **降水量** | 実際に降った **量（mm など）** → このコードでは使っていない |

過去の公演日は、API から **降水確率が取れない** ことがあります。  
そのときは空のまま → **手入力** できます。

---

## ①〜③の流れ（図解）

```
【準備】エラーをリセット
    ↓
【準備】会場・日付は入力済み？ → NO なら終了
    ↓
【準備】会場を DB から取得 → 見つからなければ終了
    ↓
① fetchForDate（緯度 + 経度 + 日付 → Open-Meteo）
    ↓
② $result が null？ → YES ならエラー表示して終了
    ↓
③ 天気・気温・降水確率をフォームにセット
```

---

## 理解チェックリスト（前編）

自分で確認してみてください。

- [ ] 3つの入口（会場変更・日付変更・再取得）は、全部 `resolveWeather` を呼ぶ
- [ ] `$this->weatherError = ''` は、**試す前** にエラーを消す
- [ ] 会場・日付が空のときは、**何もしないで return**
- [ ] 天気は **緯度 + 経度 + 日付** で1回取得する
- [ ] `$result === null` のときはエラーメッセージ
- [ ] 成功したら `$result['weather']` などをフォームにコピー
- [ ] 降水確率は **%** で、取れないときは空で OK

---

## コード学習のコツ（このプロジェクトで実践中）

1. **一度に全部読まない** … 今日は ①〜③ だけで十分
2. **日本語メモを書く** … コードの横に「前のエラーを消す」など
3. **分からない単語リスト** … `return`, `null`, `void` など
4. **画面と結びつける** … 会場を選ぶと天気欄が変わるか試す
5. **7割合っていれば OK** … 残りは質問して直す

「全然読めない」と感じても、**質問できている時点で読み始めています。**

---

## 次回予告（④以降）

次の記事では、例えば次のような内容を扱う予定です。

- **④** `WeatherService` の中身（予報 API と履歴 API の切り替え）
- **⑤** `WeatherCode` … 数字を「晴れ」に変換
- **⑥** `save()` … DB に保存
- **⑦** `show.blade.php` … 詳細画面に表示

---

## 参考：関連ファイル

```
resources/views/livewire/events/form.blade.php  … resolveWeather 本体
app/Services/WeatherService.php                 … Open-Meteo 通信（次回）
app/WeatherCode.php                             … 天気コード → 日本語（次回）
database/migrations/2026_08_25_130000_add_weather_to_events_table.php
```

---

## おわりに

前編では **`resolveWeather` が「準備 → 問い合わせ → エラー or フォーム反映」** までを担当していることを見てきました。

> **司令塔は `resolveWeather`。①で API に聞き、②で失敗を伝え、③でフォームに入れる。**

次回、④以降を読むと「API の中で何が起きているか」がつながって、より全体像が見えやすくなります。
