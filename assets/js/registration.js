(function($){
    $(document).ready(function(){
        // Basic client-side validation could be added here later.
        // For now we provide file preview for profile photo
        $(document).on('change', '#profile_photo', function(){
            var input = this;
            if ( input.files && input.files[0] ) {
                var reader = new FileReader();
                reader.onload = function(e){
                    var img = $('<img/>',{src:e.target.result,css:{maxWidth:'120px',display:'block',marginTop:'8px'}});
                    $('#profile_photo').next('.nbe-photo-preview').remove();
                    $('#profile_photo').after(img);
                };
                reader.readAsDataURL(input.files[0]);
            }
        });
    });
})(jQuery);
