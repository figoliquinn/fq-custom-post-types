<?php
/**
 * @File
 * Template for rendering relationships into select elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<label for="'.$name.'"><b><?php print $custom_field['label'];?></b></label><br/><br/>
<select id="<?php print $name;?>" name="<?php print $name;?>">
		<option>None</option>
	<?php foreach($posts as $post): ?>
		<option <?php if ($value == $post->ID):?>selected<?php endif;?> value="<?php print $post->ID;?>"><?php print $post->post_title;?></option>
	<?php endforeach;?>

</select>