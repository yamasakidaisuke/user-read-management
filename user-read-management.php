<?php
/**
 * Plugin Name: User Read Management
 * Description: A plugin to manage the read status for each user
 * Version: 1.7
 * Author: Daisuke Yamasaki
 */

// プラグイン有効化時にカスタムロールを作成
function urm_create_custom_roles() {
    // bbPress登録ユーザーの権限を取得
    $bbpress_caps = array();
    $bbp_participant = get_role( 'bbp_participant' );
    
    if ( $bbp_participant ) {
        // bbPress登録ユーザーの権限をコピー
        $bbpress_caps = $bbp_participant->capabilities;
    } else {
        // bbPressがない場合の基本権限
        $bbpress_caps = array(
            'read' => true,
        );
    }
    
    // 獣医師ロール（bbPress権限付き）
    add_role( 'veterinarian', '獣医師（bbPress権限）', $bbpress_caps );
    
    // 看護師ロール（bbPress権限付き）
    add_role( 'nurse', '看護師（bbPress権限）', $bbpress_caps );
    
    // 管理室ロール（bbPress権限付き）
    add_role( 'admin_office', '管理室（bbPress権限）', $bbpress_caps );
    
    // 病院ロール（bbPress権限付き）
    add_role( 'hospital', '病院（bbPress権限）', $bbpress_caps );
}
register_activation_hook( __FILE__, 'urm_create_custom_roles' );

// プラグイン無効化時にカスタムロールを削除
function urm_remove_custom_roles() {
    remove_role( 'veterinarian' );
    remove_role( 'nurse' );
    remove_role( 'admin_office' );
    remove_role( 'hospital' );
}
register_deactivation_hook( __FILE__, 'urm_remove_custom_roles' );

// チェックボックスを表示したい投稿カテゴリーと、チェックボックスを非表示にしたいユーザーのIDを配列として定義
$show_checkbox_categories = array('manuals', 'medical-information'); // ここにチェックボックスを表示したいカテゴリースラッグを設定
$hide_checkbox_users = array(1, 10); // ここにチェックボックスを非表示にしたいユーザーのIDを設定

// 'manuals' の子カテゴリーを取得して配列に追加する関数
function add_child_categories_to_array($parent_slug) {
    $child_categories = get_terms(array(
        'taxonomy' => 'category', // カテゴリータクソノミーを指定
        'parent' => get_category_by_slug($parent_slug)->term_id, // 親カテゴリーのIDを指定
        'hide_empty' => false, // 空のカテゴリーも取得
    ));

    $child_slugs = array();
    foreach ($child_categories as $category) {
        $child_slugs[] = $category->slug; // 子カテゴリーのスラッグを配列に追加
    }

    return $child_slugs;
}

// 'manuals' の子カテゴリーのスラッグを取得して $show_checkbox_categories に追加
$manuals_child_slugs = add_child_categories_to_array('manuals');
$show_checkbox_categories = array_merge($show_checkbox_categories, $manuals_child_slugs);

// Add checkbox and read status text to each post
function add_read_status_to_content( $content ) {
  global $show_checkbox_categories, $hide_checkbox_users;

  if ( is_single() && is_user_logged_in() ) {
      $user_id = get_current_user_id();
      $post_id = get_the_ID();

      // ユーザーがチェックボックスを非表示にするリストに含まれているか確認
      if (in_array($user_id, $hide_checkbox_users)) {
          return $content;
      }

      // 記事がチェックボックスを表示するカテゴリーリストに含まれているか確認
      $categories = get_the_category($post_id);
      $category_slugs = array_column($categories, 'slug');
      if (count(array_intersect($show_checkbox_categories, $category_slugs)) == 0) {
          return $content;
      }

      $status = get_user_meta( $user_id, "read_status_$post_id", true );
      $output = '<div class="p-read-status">';
      $output .= '<input class="p-read-status-input" type="checkbox" id="read-status-checkbox" data-post-id="' . $post_id . '" ' . ( $status === 'read' ? 'checked' : '' ) . ' />';
      $output .= '<label class="p-read-status-label" for="read-status-checkbox">' . ( $status === 'read' ? '読みました' : '読みました' ) . '</label>';
      $output .= '</div>';
      echo $output;  // 直接出力 https://chat.openai.com/share/0fccc1e5-09e8-4425-9d97-46f7e5dc97d5
  }
}

remove_filter( 'the_content', 'add_read_status_to_content' );  // 既存のフィルターを削除
add_action( 'snow_monkey_append_entry_content', 'add_read_status_to_content' );  // 新しいアクションにフックアップ

// Update the read status when Ajax request is received
function update_read_status() {
  // Verify nonce for security
  check_ajax_referer( 'read_status_action', 'security' );

  // Get post ID, status, target user ID, and current user
  $post_id = intval( $_POST['post_id'] );
  $status = $_POST['status'];
  $target_user_id = intval( $_POST['target_user_id'] ); // 新規追加: 対象ユーザーID
  $current_user_id = get_current_user_id();

  // ★ ここを修正：自分自身を更新する場合は権限不要
  if ( $current_user_id !== $target_user_id && ! current_user_can( 'administrator' ) ) {
      wp_send_json_error( array( 'message' => '権限がありません' ) );
      wp_die();
  }

  // Update the read status in user meta data
  $result = update_user_meta( $target_user_id, "read_status_$post_id", $status ); // 対象ユーザーのmetaを更新

  // 変更ログの記録
  $log_message = date( 'Y-m-d H:i:s' ) . " - ユーザー $current_user_id が ユーザー $target_user_id の 投稿 $post_id を '$status' に変更";
  error_log( $log_message );

  // Debug: print the result
  error_log( "Update result for user $target_user_id and post $post_id is: $result" );

  // 成功レスポンスを返す
  wp_send_json_success( array( 'new_status' => $status ) );
  wp_die();
}
add_action( 'wp_ajax_update_read_status', 'update_read_status' );

// CSVエクスポート機能（管理者のみ）
function export_read_status_csv() {
  // 管理者チェック
  if ( ! current_user_can( 'administrator' ) ) {
      wp_send_json_error( array( 'message' => '権限がありません' ) );
      wp_die();
  }

  global $exclude_user_ids, $exclude_post_ids;

  // ユーザーの取得（獣医師のみ）
  $users = get_users( array(
      'role' => 'veterinarian', // 獣医師ロールのみ取得
      'fields' => array( 'ID', 'display_name' )
  ) );

  // CSVデータの生成
  $csv_data = array();

  // ヘッダー行
  $header = array( 'カテゴリー', '投稿タイトル' );
  foreach ( $users as $user ) {
      $header[] = $user->display_name;
  }
  $csv_data[] = $header;

  // カテゴリーごとの設定
  $categories = array(
      'manuals' => '診療マニュアル',
      'medical-information' => '医療情報'
  );

  foreach ( $categories as $category_slug => $category_name ) {
      // カテゴリーごとの投稿を取得
      $args = array(
          'post_type' => 'post',
          'posts_per_page' => -1,
          'category_name' => $category_slug,
      );
      $posts = get_posts( $args );

      // データ行
      foreach ( $posts as $post ) {
          // 除外する記事のスキップ
          if ( in_array( $post->ID, $exclude_post_ids ) ) {
              continue;
          }

          $row = array( $category_name, get_the_title( $post->ID ) );
          
          foreach ( $users as $user ) {
              $status = get_user_meta( $user->ID, 'read_status_' . $post->ID, true );
              $row[] = ( $status === 'read' ) ? '済' : '未';
          }
          
          $csv_data[] = $row;
      }
  }

  // タイムスタンプ付きファイル名
  $timestamp = date( 'YmdHis' );
  $filename = $timestamp . '_end-of-reading-management.csv';

  // CSVレスポンスを返す
  wp_send_json_success( array(
      'filename' => $filename,
      'data' => $csv_data
  ) );
  wp_die();
}
add_action( 'wp_ajax_export_read_status_csv', 'export_read_status_csv' );


function enqueue_read_status_script() {
  wp_register_script( 'read-status-script', plugins_url( '/read-status.js', __FILE__ ), array('jquery'), '1.2', true );

  $data_array = array(
      'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
      'ajaxNonce'     => wp_create_nonce( 'read_status_action' ),
      'currentUserId' => get_current_user_id(),   // 追加
  );
  wp_localize_script( 'read-status-script', 'readStatus', $data_array );

  wp_enqueue_script( 'read-status-script' );
}
add_action( 'wp_enqueue_scripts', 'enqueue_read_status_script' );


// 一般ユーザー用：読みましたチェックボックス
// $(document).on('change', '.p-read-status-input', function () {
//     const $cb     = $(this);
//     const postId  = $cb.data('post-id');
//     const status  = $cb.is(':checked') ? 'read' : 'unread';

//     $.ajax({
//         url  : readStatus.ajaxUrl,
//         type : 'post',
//         data : {
//             action        : 'update_read_status',
//             post_id       : postId,
//             status        : status,
//             target_user_id: readStatus.currentUserId,   // 自分自身
//             security      : readStatus.ajaxNonce
//         },
//         success(response) {
//             if (!response.success) {
//                 alert('保存に失敗しました: ' + (response.data.message || '不明なエラー'));
//                 // 失敗したら元に戻す
//                 $cb.prop('checked', ! $cb.is(':checked'));
//             }
//         },
//         error() {
//             alert('AJAX エラーが発生しました');
//             $cb.prop('checked', ! $cb.is(':checked'));
//         }
//     });
// });


// 除外したいユーザーと記事のIDを配列として定義
$exclude_user_ids = array(1,10); // ここに除外したいユーザーのIDを設定
$exclude_post_ids = array(0); // ここに除外したい記事のIDを設定

// ショートコードの追加
add_shortcode('read_status_overview', 'display_read_status_overview');

function display_read_status_overview() {
    global $exclude_user_ids, $exclude_post_ids;
    ob_start();

    $current_user_id = get_current_user_id(); // 現在のユーザーIDを取得

    // ユーザーの取得（獣医師のみ）
    $users = get_users(array(
        'role' => 'veterinarian', // 獣医師ロールのみ取得
        'fields' => array('ID', 'display_name')
    ));

    // 表の開始（スタイルを先に出力）
    echo '<style>
        .p-read-status-section { 
            margin-bottom: 40px; 
        }
        
        .p-read-status-section-title { 
            font-size: 24px; 
            font-weight: bold; 
            margin-bottom: 15px; 
            padding: 15px 20px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .p-read-status-table { 
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 20px; 
            width: 100%;
            box-shadow: 0 2px 15px rgba(0,0,0,0.08);
            border-radius: 8px;
            overflow: hidden;
        }
        
        /* ヘッダー行のスタイル */
        .p-read-status-table thead th { 
            position: -webkit-sticky;
            position: sticky; 
            top: 0; 
            z-index: 10; 
            background: #2c3e50 !important;
            color: #fff;
            padding: 15px 12px;
            text-align: center;
            font-weight: 600;
            border: 1px solid #34495e;
            font-size: 14px;
        }
        
        /* 通常のセル */
        .p-read-status-table td { 
            padding: 12px; 
            border: 1px solid #e0e0e0; 
            text-align: center;
            font-size: 14px;
            transition: background-color 0.2s ease;
        }
        
        /* 投稿タイトル列（左端） */
        .p-read-status-table td:first-child { 
            text-align: left; 
            font-weight: 500;
        }
        
        /* シマシマ背景（奇数行） */
        .p-read-status-table tbody tr:nth-child(odd) td { 
            background-color: #f8f9fa; 
        }
        
        /* シマシマ背景（偶数行） */
        .p-read-status-table tbody tr:nth-child(even) td { 
            background-color: #ffffff; 
        }
        
        /* 行全体のホバー効果 */
        .p-read-status-table tbody tr:hover td { 
            background-color: #e3f2fd !important; 
        }
        
        /* 先頭列を固定 */
        .p-read-status-table tbody td:first-child,
        .p-read-status-table thead th:first-child { 
            position: -webkit-sticky;
            position: sticky; 
            left: 0; 
            z-index: 5; 
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }
        
        /* 先頭列のヘッダーセル（左上） */
        .p-read-status-table thead th:first-child { 
            z-index: 15 !important;
            background: #2c3e50 !important;
            position: -webkit-sticky;
            position: sticky;
            left: 0 !important;
            top: 0 !important;
        }
        
        /* 先頭列のデータセル背景（シマシマ維持） */
        .p-read-status-table tbody tr:nth-child(odd) td:first-child { 
            background-color: #f8f9fa !important; 
        }
        
        .p-read-status-table tbody tr:nth-child(even) td:first-child { 
            background-color: #ffffff !important; 
        }
        
        /* 先頭列のホバー時の背景 */
        .p-read-status-table tbody tr:hover td:first-child { 
            background-color: #e3f2fd !important; 
        }
        
        /* 編集可能なセル */
        .p-read-status-table .p-read-status-cell { 
            cursor: pointer;
        }
        
        /* 編集可能なセルのホバー効果 */
        .p-read-status-table .p-read-status-cell:hover { 
            background-color: #fff3cd !important;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            font-weight: bold;
        }
        
        /* 読了済みセル */
        .p-read-status-table td[data-status="read"] { 
            color: #28a745; 
            font-weight: 600;
        }
        
        /* 未読セル */
        .p-read-status-table td[data-status="unread"],
        .p-read-status-table td[data-status=""] { 
            color: #dc3545; 
            font-weight: 600;
        }
        
        /* 表示のみのセル */
        .p-read-status-table .p-read-status-cell-readonly { 
            opacity: 0.7; 
            cursor: default; 
        }
        
        /* スクロールコンテナ */
        .p-read-status-scroll-container { 
            overflow: auto; 
            max-height: 900px;
            width: 100%;
            border-radius: 8px;
            background: #fff;
            position: relative;
            display: block;
        }
    </style>';

    // カテゴリーごとの表示設定
    $categories = array(
        'manuals' => '診療マニュアル',
        'medical-information' => '医療情報'
    );

    foreach ($categories as $category_slug => $category_name) {
        // カテゴリーごとの投稿を取得
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => -1,
            'category_name' => $category_slug,
        );
        $posts = get_posts($args);

        // 投稿がない場合はスキップ
        if (empty($posts)) {
            continue;
        }

        // セクション見出し
        echo '<div class="p-read-status-section">';
        echo '<h2 class="p-read-status-section-title">' . esc_html($category_name) . '</h2>';

        // 表の開始
        echo '<div class="p-read-status-scroll-container">';
        echo '<table class="p-read-status-table">';
        echo '<thead>';

        // ヘッダーとして全ユーザー名の出力
        echo '<tr class="p-read-status-header"><th>投稿タイトル / ユーザー名</th>';
        foreach ($users as $user) {
            echo '<th class="p-read-status-user-name">' . $user->display_name . '</th>';
        }
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // 各投稿ごとに行を追加
        foreach ($posts as $post) {
            // 除外する記事のスキップ
            if (in_array($post->ID, $exclude_post_ids)) {
                continue;
            }

            // 投稿タイトルとリンクの出力
            $post_title = get_the_title($post->ID);
            $post_link = get_permalink($post->ID);
            echo '<tr>';
            echo '<td><a href="' . esc_url($post_link) . '">' . esc_html($post_title) . '</a></td>';

            // 各ユーザーの読了状態をセルとして追加
            foreach ($users as $user) {
                $status = get_user_meta($user->ID, 'read_status_' . $post->ID, true);
                $display_text = ($status == 'read') ? '済' : '未';
                
                // 管理者または自分自身のセルの場合はクリック可能に、それ以外は表示のみ
                $is_editable = (current_user_can('administrator') || $user->ID == $current_user_id);
                $cell_class = $is_editable ? 'p-read-status-cell' : 'p-read-status-cell-readonly';
                
                echo '<td class="' . $cell_class . '" data-user-id="' . $user->ID . '" data-post-id="' . $post->ID . '" data-status="' . $status . '">' . $display_text . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';

        // 表の終了
        echo '</table>';
        echo '</div>';
        echo '</div>'; // セクション終了
    }

    // 管理者のみCSVエクスポートボタンを表示
    if (current_user_can('administrator')) {
        echo '<div style="margin-top: 20px;">';
        echo '<button id="csv-export-button" class="button button-primary" style="padding: 10px 20px; cursor: pointer;">CSVエクスポート</button>';
        echo '</div>';
    }

    return ob_get_clean();
}