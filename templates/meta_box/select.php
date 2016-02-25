<?php
/**
 * @File
 * Template for rendering select elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<label for="'.$name.'"><b><?php print $custom_field['label'];?></b></label><br/>
<select id="<?php print $name;?>" name="<?php print $name;?>">

	<?php foreach($custom_field['options'] as $val => $label): ?>
		<option <?php if ($value==$val):?>selected<?php endif;?> value="<?php print $val;?>"><?php print $label;?></option>
	<?php endforeach;?>

</select>