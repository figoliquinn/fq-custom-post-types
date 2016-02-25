<?php
/**
 * @File
 * Template for rendering wysiwyg elements in the meta box
 *
 * @param array $custom_field
 * @param string $name
 */
?>

<label for="'.$name.'"><b><?php print $custom_field['label'];?></b></label><br/>
<?php print wp_editor($value, $name);?>