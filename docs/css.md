# OshiLog の CSS を読む ── 初心者向けガイド

対象ファイル: [`resources/css/app.css`](../resources/css/app.css)

このファイルは約1400行あります。全部覚える必要はありません。  
「色の辞書」と「Flux の上書きの書き方」が分かれば、あとは今触っている画面だけ見れば足ります。

---

## 一文でいうと

この CSS は、**Flux の部品を、メンカラ付きの漫画テーマで上書きするファイル**です。

Flux はボタン・サイドバー・見出しなどを最初から用意してくれる UI 部品です。  
OshiLog では、その見た目をアメコミ風（太い黒枠・クリーム地・メンカラ）に塗り直しています。

---

## 今回の整理で何を変えたか

見た目はほぼそのままです。変えたのは **勝ち方** です。

以前は、Flux を漫画風にするために指定の末尾へ `!important` が約140個ありました。  
これは「この指定を無理に勝たせる」印です。多いと、あとから直す人に「なぜ勝っているのか」が分かりにくくなります。

原因は、上書きが `@layer components` の中にあったことです。

```css
/* 以前: 層の中に書くと、Tailwind の utility（flex や bg-white など）に負けやすい */
@layer components {
    .comic-theme [data-flux-button] {
        border: 2px solid var(--color-comic-ink) !important;
    }
}
```

Tailwind の class は別の層にあり、層のルールでは utility の方が強いので、こちらが負けていました。  
その負けを `!important` で打ち消していた、という状態です。

対応は次の2つです。

1. 漫画テーマの上書きを `@layer` の外へ出す
2. 不要な `!important` を外す

```css
/* 今: 層の外なら、同じ指定でも後勝ちになる */
.comic-theme [data-flux-button] {
    border: 2px solid var(--color-comic-ink);
}
```

設計メモはファイル内にも残しています。

```css
/*
 * Flux / Tailwind の utility より後勝ちにするため、漫画テーマの上書きは @layer の外に置く。
 * レイヤー外なら !important なしでボタン・サイドバー・見出しの見た目を保てる。
 */
```

今残している `!important` は1つだけです。Alpine が起動する前に、隠したい部品が一瞬見えるのを防ぐ例外です。

```css
[x-cloak] {
    display: none !important;
}
```

---

## ファイルの地図

上から、だいたい4段です。

1. **読み込み** … Tailwind、Flux、地図の CSS を取り込む
2. **辞書** … 色とフォントを変数で持つ
3. **メンカラ** … ユーザーが選んだ色で変数を差し替える
4. **画面ごとの上書き** … サイドバー、ホーム、写真、投稿カードなど

---

## 必ず見てほしい場所

### 1. いちばん上（読み込み）

ここがアプリ全体の CSS の入口です。この1ファイルから Tailwind、Flux、Leaflet を読み込んでいます。

```css
@import 'tailwindcss';
@import '../../vendor/livewire/flux/dist/flux.css';
@import 'leaflet/dist/leaflet.css';

@source "../views";
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
```

`@import` は CSS そのものを取り込みます。

`@source` は Blade を画面に読み込む指定ではありません。  
Tailwind に「この HTML も見て、使っている class 名を拾って」と伝える指定です。

Tailwind は、あらかじめ全部の CSS を入れるのではなく、HTML / Blade の中に出てきた class（`flex` や `px-4` など）だけを CSS にします。  
ページ送りの見た目用 class は Laravel 本体の Blade に書いてあるので、そこも見ないとページネーションだけスタイルが抜けることがあります。

`vendor` はプロジェクト直下にあります。

```text
oshilog/
  vendor/          ← Composer が入れた他社のパッケージ
  resources/
    css/
      app.css      ← このファイル
```

`app.css` からの `../../vendor/...` は、「2つ上に上がって `vendor` に入る」という意味です。  
中身は `composer install` で作られます。Git には上げません。中を読むのはよいですが、直接編集しないでください。

### 2. `@theme`（色の辞書）

漫画テーマの色とフォントの辞書です。`--color-comic-ink` や `--color-comic-cream` は、このあと何度も出てきます。

```css
@theme {
    --font-sans: 'Hiragino Sans', 'Hiragino Kaku Gothic ProN', 'Yu Gothic UI', sans-serif;
    --color-comic-cream: #fff8e7;
    --color-comic-yellow: #ffd60a;
    --color-comic-red: #e63946;
    --color-comic-blue: #1d4ed8;
    --color-comic-ink: #1a1a1a;
}
```

サイト全体のインク色を変えたいときは、画面を1つずつ直すより、まずここを見ます。

### 3. メンカラ

HTML はだいたいこうなっています。

```html
<html class="comic-theme" data-member-color="pink">
```

`data-member-color` が変わると、`--comic-sidebar` などの変数がまとめて変わります。  
サイドバーが青になったりピンクになったりするのは、ここが原因です。

```css
.comic-theme {
    --comic-sidebar: #1d4ed8;
    --comic-highlight: #bfdbfe;
    --comic-cream: #eff6ff;
}

.comic-theme[data-member-color='pink'] {
    --comic-sidebar: #db2777;
    --comic-highlight: #fbcfe8;
    --comic-cream: #fff1f5;
}
```

青の基本と、ピンクに切り替わる例だけ見れば十分です。他の色も同じ型の繰り返しです。

### 4. ページ全体の背景

点々の背景と文字色は、ここ1か所です。

```css
.comic-body {
    background-color: var(--comic-cream);
    background-image: radial-gradient(circle, rgb(26 26 26 / 0.12) 1.2px, transparent 1.2px);
    background-size: 14px 14px;
    color: var(--color-comic-ink);
    font-family: var(--font-sans);
}
```

`var(--comic-cream)` は、直前のメンカラで決まったクリームを使います。

### 5. Flux 上書きの見本

`[data-flux-sidebar]` や `[data-flux-button]` は、「Flux が作った部品を漫画風に塗り直す」書き方です。同じ型がこのあとたくさん続きます。全部読まなくてよく、この2つで型がわかります。

```css
.comic-theme [data-flux-sidebar] {
    background: var(--comic-sidebar);
    background-image: none;
    color: white;
    border-color: var(--color-comic-ink);
}

.comic-theme [data-flux-button] {
    border: 2px solid var(--color-comic-ink);
    border-radius: 6px;
    box-shadow: 3px 3px 0 var(--color-comic-ink);
    font-weight: 700;
    color: var(--color-comic-ink);
}
```

### 6. 漫画の枠の見本

太い黒枠と影が「漫画っぽさ」の本体です。ホームのノート、カード、ログイン画面も、ほぼこの考え方の繰り返しです。

```css
.comic-theme .comic-panel {
    background: var(--comic-cream);
    border: 4px solid var(--color-comic-ink);
    border-radius: 12px;
    box-shadow: 8px 8px 0 var(--color-comic-ink);
    max-width: 100%;
}
```

---

## 書き方の読み方

### CSS の基本形

普通の CSS ルールを書くときは、いつも `{}` が必要です。

```css
対象 {
    プロパティ: 値;
}
```

例:

```css
[data-flux-field] {
    display: grid;
    gap: 0.5rem;
}
```

| 書き方 | 役割 |
|---|---|
| `[data-flux-field]` | 対象を選ぶ |
| `{ ... }` | 見た目の指定を書く |

`[]` は CSS の **属性セレクタ** です。  
`[data-flux-field]` は「`data-flux-field` という属性が付いている要素全部」という意味です。

Flux のフォーム部品はだいたい次のようなマークアップです。

```html
<div data-flux-field>
    ...
</div>
```

クラス名 `.foo` ではなく属性で探す書き方です。Flux が `class` ではなく `data-flux-field` を付けているので、こちらもそれに合わせています。

`{}` がいらないのは、スタイルを当てるルールではない行です。このファイルだと例えば次のようなものです。

```css
@import 'tailwindcss';
@source "../views";
```

### 空白は「入れ子」

`.comic-theme [data-flux-sidebar]` の間の空白は、「同じ要素」ではなく **入れ子** です。

- `.comic-theme` の**中にある**
- `[data-flux-sidebar]` が付いている要素

に、この指定を当てます。

```html
<html class="comic-theme" data-member-color="pink">
    ...
    <aside data-flux-sidebar>
        メニュー
    </aside>
</html>
```

```css
.comic-theme [data-flux-sidebar] {
    background: var(--comic-sidebar);
    background-image: none;
    color: white;
    border-color: var(--color-comic-ink);
}
```

各行の意味は次のとおりです。

- `background: var(--comic-sidebar);` … 背景色にメンカラの `--comic-sidebar` を使う。ピンクならピンク、青なら青
- `background-image: none;` … Flux 側の背景画像があれば消す（色だけにする）
- `color: white;` … 中の文字を白にする
- `border-color: var(--color-comic-ink);` … 枠線はメンカラではなく、インクの黒

`--comic-sidebar` 自体の色は、このルールではなく、前のほうの `.comic-theme[data-member-color='pink']` などで決まっています。ここでは「その変数を背景に使う」だけです。

### これは正規化ではない

**正規化（normalize）** は、ブラウザごとの初期スタイルの差をならすことです。  
例えば、見出しの余白やボタンの見た目を Chrome と Safari で近くする、という作業です。  
このプロジェクトでは Tailwind の初期化（preflight）や Flux の CSS が、それに近い役割をしています。

今見ているルールは **上書き（オーバーライド）** です。Flux が作ったサイドバーを、漫画テーマの色に塗り直しています。

| 用語 | 何をするか | このファイルの例 |
|---|---|---|
| 正規化 | ブラウザ差をならす | Tailwind / Flux の読み込み |
| 上書き | 既存部品を自分の見た目にする | `.comic-theme [data-flux-sidebar]` |

### `color-scheme: light` は何をしているか

```css
html.comic-theme,
html.comic-theme.dark,
.comic-theme {
    color-scheme: light;
}
```

方向としては「ダークモードでも、決めた色を出す」で合っています。  
ただしこの3行がやっているのは、画面全体をライトテーマの CSS で塗り直すことではなく、**ブラウザ標準の部品をライト扱いする**ことです。

効くのは次のような部品です。

- スクロールバー
- 日付入力のカレンダー
- `<select>` の選択肢
- 入力欄の自動補完の色

`html.comic-theme.dark` があるのは、設定の「ダーク」で `<html>` に `dark` クラスが付いたとき用です。  
そのままだとブラウザが「このページは暗い」と判断して、スクロールバーだけ黒くなることがあります。漫画テーマのクリーム地に黒い部品が乗るのを防ぎます。

ページがクリーム色なのは、この3行ではなく `.comic-body` やメンカラの変数側です。  
すぐ上のコメントは `color-scheme` 専用ではなく、このあと続くサイドバー・ボタン・見出しの上書き全体についてのメモです。

---

## 直し方の順番

1. 色を変えたい → `@theme` かメンカラの変数
2. ボタンやサイドバーが崩れた → `[data-flux-...]` の上書き
3. ある画面だけおかしい → その画面用のクラス

今は読まなくてよい場所（その画面を触るときだけ見る）:

| 場所 | 画面 |
|---|---|
| `.home-notebook` | トップ |
| `.member-color-swatch` | メンカラの丸ボタン |
| `.photo-lightbox` | 写真の拡大 |
| `.comic-auth` | ログイン |
| `.share-card` | 投稿カード |

---

## CSS の近くで出てくる別の言葉

CSS ファイル本体ではありませんが、同じ画面を読むと出てきます。

### Alpine

HTML に短い属性を書いて、画面のちょっとした動きを付ける JavaScript のライブラリです。  
このプロジェクトでは Livewire / Flux と一緒に入っています。

写真の拡大が典型です。

```html
<div x-data="{ open: false }">
    <button x-on:click="open = true">サムネイル</button>

    <div x-show="open" x-cloak>
        拡大した画像
    </div>
</div>
```

- `x-data="{ open: false }"` … 「今は閉じてる」という状態を持つ
- `x-on:click="open = true"` … クリックしたら開く
- `x-show="open"` … `open` が true のときだけ表示する
- `x-cloak` … Alpine が起動する前は隠す

`[x-cloak] { display: none !important; }` は、この最後のためです。

PHP や Livewire はサーバー側、Alpine はブラウザ側の小さな動き、という分担です。  
ページ全体の保存や一覧取得は Livewire、開く・閉じる・フェードのような瞬間の操作は Alpine です。

### Blade の `{{ }}`

`{{ }}` は HTML ではなく、**Blade（Laravel のテンプレート）** の書き方です。  
「この中は PHP の値を、画面用の文字にして埋め込む」という意味です。

普通の HTML だと画像は固定です。

```html
<img src="photo.jpg" alt="公演写真" />
```

コンポーネントでは、使うたびに写真が変わるので、値を外から受け取ります。

```blade
@props([
    'src',
    'alt' => '',
])

<img src="{{ $src }}" alt="{{ $alt }}" {{ $attributes }} />
```

呼び出し側が例えばこう書くと、

```blade
<x-photo-viewer src="/storage/photo.jpg" alt="集合写真" class="rounded" />
```

Blade がこう展開します。

```html
<img src="/storage/photo.jpg" alt="集合写真" class="rounded" />
```

- `{{ $src }}` … 画像の URL
- `{{ $alt }}` … 代替テキスト
- `{{ $attributes }}` … `class` など、props 以外で渡された属性をそのまま付ける

`{{ }}` は表示のときに HTML として危ない文字をエスケープします。`<` などがタグとして動かないようにするためです。

順番は、先に Blade が HTML を作り、そのあとブラウザで CSS と Alpine が動きます。

---

## 関連ファイル

- [`resources/css/app.css`](../resources/css/app.css) … 漫画テーマの CSS 本体
- [`resources/views/components/layouts/app/sidebar.blade.php`](../resources/views/components/layouts/app/sidebar.blade.php) … `<html class="comic-theme">` の起点
- [`resources/views/components/photo-viewer.blade.php`](../resources/views/components/photo-viewer.blade.php) … Alpine と Blade の例
- [`resources/js/member-color.js`](../resources/js/member-color.js) … メンカラを `data-member-color` に反映する JS
