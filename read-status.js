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
});





