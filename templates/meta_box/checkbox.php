<?php
/**
 * @File
 * Template for rendering radio or checkbox elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<label><b><?php print $custom_field['label'];?></b></label><br/>

<?php $count = 0;?>
<?php foreach($custom_field['options'] as $val => $label): ?>
	<?php 
		$count++;
		if($custom_field['type']=="checkbox") {
			$checked = in_array($val,$value) ? ' checked="checked" ' : '';
		} else {
			$checked = ($val==$value) ? ' checked="checked" ' : '';
		}
	?>
	
	<label for="<?php print $name;?>_<?php print $count;?>">
		<input name="<?php print $name . ($custom_field['type'] == "checkbox" ? "[]" : ""); ?>" id="<?php print $name;?>_<?php print $count;?>" type="<?php print $custom_field['type'];?>" <?php print $checked;?> value="<?php print $val;?>" />
		<?php print $label;?>
	</label>
	<?php print $custom_field['inline'] ? '&nbsp;&nbsp;&nbsp;' : '<br>';?>
<?php endforeach;?>