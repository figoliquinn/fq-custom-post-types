# fq_custom_post_types
simple wordpress class for creating custom post type

### Example
```php
$type = new FQ_Custom_Post_Type( 'post_type' );
$type->register();


$type->add_category('fart',array('show_admin_column'=>false));
$type->add_tag('turd');
$type->add_category('poop');
$type->delete_all(1);
$type->add_custom_fields();
$type->custom_fields = array();
```