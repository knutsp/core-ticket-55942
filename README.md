# core-ticket-55942
Demo add/updating and getting options/meta as proposed by knutsp in https://core.trac.wordpress.org/ticket/55942#comment:78.

Go to wp-admin/tools.php?page=core-ticket-55942 for an UI

# add_option

Current signature:  `add_option( string $option, mixed $value = '', string $deprecated = '', string|bool $autoload = 'yes' ): bool`

Proposed signature: `add_option( string $option, mixed $value = '', string $deprecated = '', string|bool $autoload = 'yes', ?string $type = null ): bool`

Alternatively: No change, use `update_option()` to save type?

# update_option

Current signature:  `update_option( string $option, mixed $value, string|bool $autoload = null ): bool`

Proposed signature: `update_option( string $option, mixed $value, string|bool $autoload = null, ?string $type = null ): bool`

## Logic
If $type is given and not null, use as explicit $type.
else detect the type of $value, use as implicit $type.

Add or update the option value in db
If successful, add or update an extra option, with a prefixed $option as "_option_type_$option" with the value of $type.

# get_option
Current signature unchanged: `get_option( string $option, mixed $default_value = false ): mixed`

## Logic
$type is fetched from a prefixed $option as "_option_type_$option"
After the $value is fetched, and the $type is found and valid, perform `settype( $value, $type )`before returning it

# add_metadata
(todo)

# add_meta
(todo)

# update_meta
(todo)

# update_{$object_type}_meta

Current signatures:  `update_{$object_type}_meta( int $term_id, string $meta_key, mixed $meta_value, mixed $prev_value = '' ): int|bool|WP_Error`

Proposed sigantures: `update_{$object_type}_meta( int $term_id, string $meta_key, mixed $meta_value, mixed $prev_value = '', ?string $type = null ): int|bool|WP_Error`

## Logic
If $type is given and not null, use as explicit $type.
else detect the type of $value, use as implicit $type.

Update the meta value in db
If successful, add or update an extra meta_key, with a prefixed $meta_key as "_meta_type_$meta_key" with the value of $type.

# add_{$object_type}_meta

Current signatures:  `add_{$object_type}_meta( int $obj_id, string $meta_key, mixed $meta_value, bool $unique = false ): int|false|WP_Error`

Proposed signatures: `add_{$object_type}_meta( int $obj_id, string $meta_key, mixed $meta_value, bool $unique = false, ?string $type = null ): int|false|WP_Error`

## Logic
If $type is given and not null, use as explicit $type.
else check if the $type already exists
else detect the type of $value, use as implicit $type.

Add the meta value in db
If successful and ( $type did not already exist or have expplicit $type given),
add or update an extra meta_key, with a prefixed $meta_key as "_meta_type_$meta_key" with the value of $type.

In other words: To change the stored type of a meta_key that already exists, explcit type must be used, implicit type will only be added if type did not exists

# get_metadata
(todo)
