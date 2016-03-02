<?php
/**
 * @File
 * Template for rendering image elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<div class="field">
	<label for="'.$name.'"><b><?php print $custom_field['label'];?></b></label><br/><br/>
	<input type="button" class="meta-image-gallery-button button" value="Choose or Upload an Image" />
	
	<div class="gallery">
		<ul class="clearfix">
			
			<?php foreach ($images as $id => $image): ?>
				<li>
					<div class="fq-preview-image-wrapper" <?php if (!empty($image)):?>style="display:block;"<?php endif;?>>
						<img src="<?php if (!empty($image)):?><?php print $image;?><?php endif;?>" class="fq-preview-image" data-image-id="<?php print $id;?>">
						<button type="button" class="fq-remove-preview-image" title="remove-image">x</button>
					</div>
					
				</li>
			<?php endforeach;?>
			
		</ul>
	</div>
	
	<input type="hidden" name="<?php print $name;?>" class="meta-images" value="<?php print $value;?>" />
	
	<div class="gallery-template">
		<li class="clearfix">
			<div class="fq-preview-image-wrapper" <?php if (!empty($image)):?>style="display:block;"<?php endif;?>>
				<img src="<?php if (!empty($image)):?><?php print $image;?><?php endif;?>" class="fq-preview-image">
				<button type="button" class="fq-remove-preview-image" title="remove-image">x</button>
			</div>
			
		</li>
	</div>
</div>
