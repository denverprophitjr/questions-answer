jQuery(document).ready(function ($) {
    var dwqa__attachments_max_files_upload = $('#dwqa-attachments-max-files-upload').val();
    var dwqa__attachments_files_uploaded = $('.dwqa-attachments-item-wrap').length;
    if (dwqa__attachments_files_uploaded >= dwqa__attachments_max_files_upload) {
        $('input.dwqa-attachments-button-upload').remove();
    }
    $('#dwqa-attachments-add-button-upload').on('click', function () {
        // console.log('abc');
        var files_uploaded = $('.dwqa-attachments-item-wrap').length;
        var current_files_upload = $('input.dwqa-attachments-button-upload').length;
        if (current_files_upload < (dwqa__attachments_max_files_upload - files_uploaded)) {
            $('#dwqa-attachments-wrap-button-upload').append('<br><input type="file" name="dwqa_upload[]" class="dwqa-attachments-button-upload"/>');
        } else {
            alert('Max files to upload: ' + dwqa__attachments_max_files_upload);
        }
    });

    $('.dwqa-attachments-remove-item').on('click', function () {

        if (confirm("Are you sure?")) {
            var attachment_id = $(this).data("id");
            var post_id = $(this).data("post");
            var parent = $(this).closest(".dwqa-attachments-item-wrap");
            console.log('abc');
            $.ajax({
                url: dwqa.ajax_url,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'dwqa-attachments-remove-item',
                    nonce: $('input#dwqa_attachments_remove_items_nonce').val(),
                    attachment_id: attachment_id,
                    post_id: post_id,
                },
                success: function (data) {
                    console.log(data);
                    if (data.success) {
                        parent.remove();
                    }
                }
            });
        }
    });

});
