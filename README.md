# User Read Management

WordPressプラグイン - ユーザーごとに投稿の読了状態を管理するシステム

## 📋 概要

User Read Managementは、ユーザーが各投稿を読んだかどうかを追跡・管理できるWordPressプラグインです。特定のカテゴリーの投稿に「読みました」チェックボックスを表示し、読了状態を一覧表で確認できます。

## ✨ 主な機能

### 1. 投稿ページでの読了チェックボックス
- 特定カテゴリー（`manuals`, `medical-information`）の投稿にチェックボックスを自動表示
- ログインユーザーが「読みました」をチェック可能
- AJAXによるリアルタイム保存

### 2. 読了状態一覧表示（ショートコード）
- **カテゴリー別表示**: 
  - 「診療マニュアル」（manuals）
  - 「医療情報」（medical-information）
  - 2つの独立した表で見やすく整理
- **全ユーザー**: 全員分の読了状態をマトリックス表で確認可能
- **編集権限**: 
  - 自分自身の状態: 誰でも変更可能
  - 他ユーザーの状態: 管理者のみ変更可能
- セルクリックで状態を切り替え（済 ⇔ 未）
- 固定ヘッダー・固定列でスクロール時も見やすい

### 3. CSVエクスポート機能（管理者のみ）
- 読了状態一覧をCSVファイルでダウンロード可能
- ファイル名: `YYYYMMDDHHmmss_end-of-reading-management.csv`
- カテゴリー列付きで分類しやすい
- Excel対応（UTF-8 BOM付き）
- タイムスタンプ付きで記録管理に最適

### 4. セキュリティ
- WordPress nonce による CSRF 対策
- 権限チェック（自分または管理者のみ編集可能）
- エラーログによる変更履歴の記録

## 🚀 インストール方法

### 方法1: 手動インストール

1. このリポジトリをダウンロード
```bash
git clone https://github.com/yamasakidaisuke/user-read-management.git
```

2. `user-read-management` フォルダを `wp-content/plugins/` にアップロード

3. WordPress管理画面の「プラグイン」メニューから「User Read Management」を有効化

### 方法2: 直接アップロード

1. ZIPファイルとしてダウンロード
2. WordPress管理画面 > プラグイン > 新規追加 > プラグインのアップロード
3. ZIPファイルを選択してインストール・有効化

## 📖 使い方

### ショートコードの使用

固定ページや投稿に以下のショートコードを追加：

```
[read_status_overview]
```

これにより、カテゴリーごとに分かれた読了状態マトリックス表が表示されます：

1. **診療マニュアル** セクション
2. **医療情報** セクション

各セクションに全ユーザー×投稿のマトリックス表が表示されます。

### 投稿ページでの表示

特定カテゴリー（`manuals`, `medical-information`およびその子カテゴリー）に属する投稿を表示すると、投稿コンテンツの下に「読みました」チェックボックスが自動的に表示されます。

### CSVエクスポート（管理者のみ）

読了状態一覧表の下部に「CSVエクスポート」ボタンが表示されます（管理者のみ）。

1. ボタンをクリック
2. CSVファイルが自動ダウンロード
3. ファイル名: `20251110123456_end-of-reading-management.csv`（タイムスタンプ付き）
4. Excelで開くとそのまま日本語が正しく表示されます

## ⚙️ カスタマイズ設定

プラグインファイル（`user-read-management.php`）内で以下の設定を変更できます：

### 対象カテゴリーの設定
```php
$show_checkbox_categories = array('manuals', 'medical-information');
```

### チェックボックスを非表示にするユーザーID
```php
$hide_checkbox_users = array(1, 10);
```

### 一覧表示から除外するユーザーID
```php
$exclude_user_ids = array(1, 10);
```

### 一覧表示から除外する投稿ID
```php
$exclude_post_ids = array(0);
```

## 🔧 技術仕様

### 環境要件
- WordPress 5.0 以上
- PHP 7.0 以上
- jQuery（WordPress同梱版）

### データ構造
読了状態は WordPress の `user_meta` テーブルに保存されます：

```
meta_key: read_status_{post_id}
meta_value: 'read' または 'unread'
```

### ファイル構成
```
user-read-management/
├── user-read-management.php  # メインプラグインファイル（PHP）
├── read-status.js             # フロントエンド JavaScript
└── README.md                  # このファイル
```

## 🎨 表示のカスタマイズ

### CSSクラス

以下のクラスでスタイルをカスタマイズ可能：

- `.p-read-status` - チェックボックスコンテナ
- `.p-read-status-input` - チェックボックス要素
- `.p-read-status-label` - ラベル要素
- `.p-read-status-table` - 一覧表テーブル
- `.p-read-status-cell` - 編集可能なセル
- `.p-read-status-cell-readonly` - 表示のみのセル

## 📊 使用例

### スタッフ向けマニュアル管理
- 社内マニュアルを投稿として作成
- スタッフが各マニュアルを読了したかを追跡
- 管理者が全員の読了状況を一覧で確認

### 医療情報の確認管理
- 重要な医療情報を投稿
- 医療スタッフの既読状況を管理
- コンプライアンス対応の記録として活用

## 🔒 セキュリティ

- WordPress nonce による CSRF 対策
- 権限チェック（`current_user_can`）
- サニタイゼーション・エスケープ処理
- エラーログによる監査証跡

## 🐛 トラブルシューティング

### チェックボックスが表示されない
1. ログインしているか確認
2. 投稿が対象カテゴリーに属しているか確認
3. 自分のユーザーIDが除外リストに含まれていないか確認

### 状態が保存されない
1. ブラウザのコンソールでJavaScriptエラーを確認
2. WordPress の `wp_ajax` が正常に動作しているか確認
3. サーバーのエラーログを確認

## 📝 変更履歴

### Version 1.2
- 読了状態一覧表を2つのカテゴリー別に分割表示
  - 「診療マニュアル」（manuals）
  - 「医療情報」（medical-information）
- CSVエクスポートにカテゴリー列を追加
- 見やすいセクション見出しを追加

### Version 1.1
- CSVエクスポート機能を追加（管理者のみ）
- タイムスタンプ付きファイル名でダウンロード
- Excel対応（UTF-8 BOM付き）

### Version 1.0
- 初回リリース
- 読了チェックボックス機能
- 読了状態一覧表示機能
- 管理者による他ユーザー状態編集機能
- 全ユーザーが全員分の読了状態を閲覧可能

## 👤 作者

**Daisuke Yamasaki**

- GitHub: [@yamasakidaisuke](https://github.com/yamasakidaisuke)

## 📄 ライセンス

このプロジェクトは GPL v2 以降のライセンスの下で公開されています。

## 🤝 貢献

バグ報告や機能リクエストは、[GitHub Issues](https://github.com/yamasakidaisuke/user-read-management/issues)にてお願いします。

プルリクエストも歓迎します！

## 🔗 リンク

- リポジトリ: https://github.com/yamasakidaisuke/user-read-management
- 問題報告: https://github.com/yamasakidaisuke/user-read-management/issues

---

Made with ❤️ for WordPress

