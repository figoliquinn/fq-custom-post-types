<?php
/**
 * @File
 * Template for rendering image elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<?php // wp_nonce_field(plugin_basename(__FILE__), 'wp_custom_attachment_nonce');?>

<label for="'.$name.'"><b><?php print $custom_field['label'];?></b></label><br/><br/>

<div class="clearfix image-field">
	<div class="fq-preview-image-wrapper" <?php if (!empty($image)):?>style="display:block;"<?php endif;?>>
		<img src="<?php if (!empty($image)):?><?php print $image;?><?php endif;?>" class="fq-preview-image">
		<button class="fq-remove-preview-image" title="remove-image">x</button>
	</div>
	
	<input type="hidden" name="<?php print $name;?>" id="<?php print $name;?>" class="meta-image" value="<?php print $value;?>" />
	<input type="button" class="meta-image-button button" value="Choose or Upload an Image" />
</div>
