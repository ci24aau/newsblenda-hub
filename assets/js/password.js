(function($){
    $(document).ready(function(){
        // Toggle password visibility for reset form
        $(document).on('click', '.nbe-toggle-password', function(e){
            e.preventDefault();
            var $btn = $(this);
            var $input = $btn.closest('p').find('input[type="password"], input[type="text"]');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $btn.text('Hide');
            } else {
                $input.attr('type', 'password');
                $btn.text('Show');
            }
        });
    });
})(jQuery);
