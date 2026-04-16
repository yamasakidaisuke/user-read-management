# CLAUDE.md — user-read-management

WordPress プラグイン（v2.0）。投稿の既読管理を行う。UI は日本語のみ。

## ファイル構成
- `user-read-management.php` — メイン（フック、AJAX、ショートコード、インライン CSS）
- `read-status.js` — jQuery フロント（チェックボックス／セル操作／CSV）
- `README.md` — 日本語ドキュメント
- `.cursorrules` — WP6.8+ モダン指針（本プラグインは legacy jQuery のため参考情報）

## カスタムロール（activation 時作成）
`veterinarian`, `nurse`, `admin_office`, `hospital`
- `urm_create_custom_roles()` / `urm_remove_custom_roles()`

## 対象カテゴリ
`manuals`（子カテゴリ自動取得）, `medical-information`, `essential_readings`
- 設定配列: `$show_checkbox_categories`, `$hide_checkbox_users`, `$exclude_user_ids`, `$exclude_post_ids`

## 投稿ページのチェックボックス
- `add_read_status_to_content()` を `snow_monkey_append_entry_content` にフック
- HTML: `.p-read-status > .p-read-status-input + .p-read-status-label`
- ラベル文言: 「読みました」

## データモデル
- user_meta キー: `read_status_{post_id}` = `'read'` / `'unread'` / 空
- user_meta キー: `urm_store_number` = 整数（ソート用店舗番号、管理者が設定）

## AJAX（nonce: `read_status_action`）
- `update_read_status` — 本人は常時可、他ユーザー分は administrator のみ
- `export_read_status_csv` — administrator または `admin_office` ロール（`urm_user_can_export_read_status_csv()`）
  - veterinarian ユーザーを対象に集計
  - ファイル名: `YYYYMMDDHHmmss_end-of-reading-management.csv`（UTF-8 BOM 付）
  - 行: カテゴリー / 投稿タイトル / 各ユーザーの状態（済 or 未）

## ショートコード
- `[read_status_overview]` → `display_read_status_overview()`
  - カテゴリ表示順: 必読資料 → 診療マニュアル → 医療情報
  - ヘッダにユーザーごとの読了率 `(X/Y XX%)` を表示
  - ソート切替 UI（`?urm_sort=store_number|display_name|registered`）
  - セル編集可否: admin もしくは本人のみ（それ以外は `.p-read-status-cell-readonly`）

## ユーザープロフィール拡張
- `urm_show_store_number_field()` / `urm_save_store_number_field()` — 店舗番号フィールド（管理者のみ）
- `urm_sort_users($users, $sort_by)` — ソートヘルパー（display/CSV 共通）

## JS（`read-status.js`）
- localize: `readStatus.ajaxUrl`, `readStatus.ajaxNonce`, `readStatus.currentUserId`
- 対象セレクタ:
  - `.p-read-status-input` — change → 自分の既読トグル
  - `.p-read-status-cell` — click → 確認ダイアログ後に他ユーザー分を更新（管理者向け）
  - `#csv-export-button` — click → CSV を blob でダウンロード

## 依存
- WordPress 5.0+ / PHP 7.0+ / jQuery（WP 同梱）
- 外部ライブラリなし

## 重要な制約
- UI は日本語ハードコード（i18n 未対応、`.pot` なし）
- CSS はすべて PHP 内インライン
- 新規 UI 生成や修正を行う場合は必ず `DESIGN.md` を読むこと

## コーディング注意
- 文字列・ステータス値・ラベル（「読みました」「済」「未」等）は既存と一致させる
- `admin_office` の CSV 権限は壊さない
- セル編集の権限チェック（本人 or admin）を必ず維持
- sticky ヘッダ実装は壊れやすい（CSS 側の注意は `DESIGN.md` 参照）
