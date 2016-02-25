<?php
/**
 * @File
 * Template for rendering time,date, text elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<label for="'.$name.'"><b><?php print $custom_field['label'];?></b></label><br/><br/>
<input type="text" id="<?php print $name;?>" name="<?php print $name;?>" value="<?php print $value;?>" style="width:100%;" />