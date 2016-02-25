<?php
/**
 * @File
 * Template for rendering textarea elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<label for="'.$name.'"><b><?php print $custom_field['label'];?></b></label><br/>
<textarea id="<?php print $name;?>" name="<?php print $name;?>" style="width:100%;" rows="5"><?php print $value;?></textarea>