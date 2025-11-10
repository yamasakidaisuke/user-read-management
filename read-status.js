jQuery(document).ready(function($) {

    // 投稿ページ：自分の「読みました」チェックボックス
    $(document).on('change', '.p-read-status-input', function () {
        var $cb    = $(this);
        var postId = $cb.data('post-id');
        var status = $cb.is(':checked') ? 'read' : 'unread';
  
        $.ajax({
            url  : readStatus.ajaxUrl,
            type : 'post',
            data : {
                action        : 'update_read_status',
                post_id       : postId,
                status        : status,
                target_user_id: readStatus.currentUserId,
                security      : readStatus.ajaxNonce
            },
            success: function (response) {
                if (!response.success) {
                    alert('保存に失敗しました: ' + (response.data.message || '不明なエラー'));
                    $cb.prop('checked', ! $cb.is(':checked')); // 元に戻す
                }
            },
            error: function () {
                alert('AJAX エラーが発生しました');
                $cb.prop('checked', ! $cb.is(':checked'));     // 元に戻す
            }
        });
    });
  // 新規追加: 表セルのクリックイベント（管理者ビュー用）
  $(document).on('click', '.p-read-status-cell', function() {
      var cell = $(this);
      var userId = cell.data('user-id');
      var postId = cell.data('post-id');
      var currentStatus = cell.data('status') || 'unread'; // デフォルトは'unread'
      var newStatus = (currentStatus === 'read') ? 'unread' : 'read';

      // 確認ダイアログ
      if (confirm('このステータスを ' + (newStatus === 'read' ? '済' : '未') + ' に変更しますか？')) {
          $.ajax({
              url: readStatus.ajaxUrl,
              type: 'post',
              data: {
                  action: 'update_read_status',
                  post_id: postId,
                  status: newStatus,
                  target_user_id: userId, // 新規追加: 対象ユーザーID
                  security: readStatus.ajaxNonce
              },
              success: function(response) {
                  if (response.success) {
                      // セルを更新
                      var newDisplay = (newStatus === 'read') ? '済' : '未';
                      var newColor = (newStatus === 'read') ? 'blue' : 'red';
                      cell.text(newDisplay).css('color', newColor).data('status', newStatus);
                      console.log("Status updated to: " + newStatus);
                  } else {
                      alert('更新に失敗しました: ' + (response.data.message || '不明なエラー'));
                  }
              },
              error: function(errorThrown) {
                  console.log(errorThrown);
                  alert('AJAX エラーが発生しました');
              }
          });
      }
  });

  // CSVエクスポート機能（管理者のみ）
  $(document).on('click', '#csv-export-button', function() {
      var button = $(this);
      button.prop('disabled', true).text('エクスポート中...');

      $.ajax({
          url: readStatus.ajaxUrl,
          type: 'post',
          data: {
              action: 'export_read_status_csv',
              security: readStatus.ajaxNonce
          },
          success: function(response) {
              if (response.success) {
                  // CSVデータを生成
                  var csvContent = '';
                  response.data.data.forEach(function(row) {
                      // UTF-8 BOM付きで日本語対応
                      var rowData = row.map(function(cell) {
                          // カンマやダブルクォートをエスケープ
                          var escaped = ('' + cell).replace(/"/g, '""');
                          return '"' + escaped + '"';
                      }).join(',');
                      csvContent += rowData + '\r\n';
                  });

                  // BOM付きでダウンロード（Excel対応）
                  var bom = '\uFEFF';
                  var blob = new Blob([bom + csvContent], { type: 'text/csv;charset=utf-8;' });
                  var link = document.createElement('a');
                  var url = URL.createObjectURL(blob);
                  
                  link.setAttribute('href', url);
                  link.setAttribute('download', response.data.filename);
                  link.style.visibility = 'hidden';
                  document.body.appendChild(link);
                  link.click();
                  document.body.removeChild(link);

                  button.prop('disabled', false).text('CSVエクスポート');
                  alert('CSVファイルをダウンロードしました');
              } else {
                  alert('エクスポートに失敗しました: ' + (response.data.message || '不明なエラー'));
                  button.prop('disabled', false).text('CSVエクスポート');
              }
          },
          error: function() {
              alert('AJAX エラーが発生しました');
              button.prop('disabled', false).text('CSVエクスポート');
          }
      });
  });
});





