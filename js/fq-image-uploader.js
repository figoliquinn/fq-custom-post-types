(function($) {


	$(document).ready(function() {

		imageUploader();
		galleryUploader();
	
	
	});
	
	
	
	
	
	function imageUploader() {
		// Instantiates the variable that holds the media library frame.
	    var meta_image_frame;

	
	    // Runs when the image button is clicked.
	    $('.meta-image-button').click(function(e){
	        // Prevents the default action from occuring.
	        e.preventDefault();
	        
		    var button = $(this);
		    var fieldLabel = $(button).closest('.meta-field').find('label').text();
	
	        // Sets up the media library frame
	        meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
	            title: fieldLabel,
	            button: { text:  'Update ' + fieldLabel },
	            library: { type: 'image' }
	        });
	
	        // Runs when an image is selected.
	        meta_image_frame.on('select', function(){
	
	            // Grabs the attachment selection and creates a JSON representation of the model.
	            var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
	            
	            // Sends the attachment URL to our custom image input field.
	            $(button).closest('.meta-field').find('.meta-image').val(media_attachment.id);
	            
	            // Add an image preview
	            $(button).closest('.meta-field').find('img').attr('src', media_attachment.sizes.thumbnail.url);
	            $(button).closest('.meta-field').find('.fq-preview-image-wrapper').show();
	        });
	
	        // Opens the media library frame.
	        meta_image_frame.open();
	    });
	    
	    
	    // Removes the image
	    $('.image-field .fq-remove-preview-image').click(function(e) {
			e.preventDefault();
			 
			var imageWrapper = $(this).closest('.fq-preview-image-wrapper');
			$(imageWrapper).find('img').attr('src', '');
			$(imageWrapper).hide();
			$(imageWrapper).nextAll('.meta-image').val('');
	    });
	}
	
	
	
	
	function galleryUploader() {
		// Instantiates the variable that holds the media library frame.
	    var meta_image_frame;

	
	    // Runs when the image button is clicked.
	    $('.meta-image-gallery-button').click(function(e){
	        // Prevents the default action from occuring.
	        e.preventDefault();
	        
		    var button = $(this);
		    var fieldLabel = $(button).closest('.meta-field').find('label').text();
	
	        // Sets up the media library frame
	        meta_image_frame = wp.media.frames.meta_image_frame = wp.media({
	            title: fieldLabel,
	            button: { text:  'Add to ' + fieldLabel },
	            library: { type: 'image' }
	        });
	
	        // Runs when an image is selected.
	        meta_image_frame.on('select', function(){
	
	            // Grabs the attachment selection and creates a JSON representation of the model.
	            var media_attachment = meta_image_frame.state().get('selection').first().toJSON();
	            
				// Get the template and clone it
				var template = $(button).closest('.field').find('.gallery-template').clone();
				
				// Add in the appropriate fields
				$(template).find('img').attr('src', media_attachment.sizes.thumbnail.url);
				$(template).find('img').data('image-id', media_attachment.sizes.thumbnail.url);
	            $(template).find('.fq-preview-image-wrapper').show();
	            $(button).closest('.field').find('.meta-images').val($(button).closest('.field').find('.meta-images').val() + media_attachment.id + ',');
	            $(button).closest('.field').find('.gallery ul').append($(template).html());
	            
	        });
	
	        // Opens the media library frame.
	        meta_image_frame.open();
	    });
	    
	    
	    // Removes the image
	    $('body').on('click', '.gallery .fq-remove-preview-image', function(e) {
			e.preventDefault();
			var field = $(this).closest('.field');
			var button = $(this);
			var id = $(button).closest('li').find('img').data('image-id');
			$(button).closest('li').fadeOut('fast').delay(500).remove();
			
			var newString = '';
			$(field).find('.gallery li').each(function() {
				newString += $(this).find('img').data('image-id') + ',';
			});
			
			$(field).find('.meta-images').val(newString);
	    });
	}
	
	
})(jQuery)