(function($){
    $(document).ready(function(){
        // Preview button opens modal with the full content from hidden element
        $(document).on('click', '.preview-button', function(){
            var target = $(this).data('target');
            var content = $(target).html() || '<p><?php echo esc_js( __( 'No preview available.', 'newsblenda-editorial' ) ); ?></p>';
            $('#nbe-modal .nbe-modal-body').html(content);
            $('#nbe-modal').fadeIn(150);
        });

        $(document).on('click', '.nbe-modal-close', function(){
            $('#nbe-modal').fadeOut(150).find('.nbe-modal-body').empty();
        });

        // Request revision and reject buttons open inline form modal
        $(document).on('click', '.request-revision-button, .reject-button', function(){
            var id = $(this).data('id');
            var form = $('#nbe-review-template').clone(true);
            form.attr('id', 'nbe-review-' + id);
            form.find('input[name="article_id"]').val(id);
            // Ensure nonce field has a unique name in case of multiple forms
            $('#nbe-modal .nbe-modal-body').html(form);
            $('#nbe-modal').fadeIn(150);
        });

        // Submit review forms inside modal
        $(document).on('submit', '#nbe-modal form.nbe-review-form', function(e){
            // Let the form submit normally (server-side handling present)
            // Optionally add client-side validation here
        });
    });
})(jQuery);
