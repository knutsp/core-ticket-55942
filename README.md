# core-ticket-55942
Demo add/updating and getting options/meta as proposed by knutsp in https://core.trac.wordpress.org/ticket/55942#comment:78.

Go to Tools - Demo options/meta (wp-admin/tools.php?page=core-ticket-55942) for an UI

# The problem (as I have understood it)

Storing metadata does not preserve the scalar type of data saved. `get_option/get_metadata` returns strings, even when the saved data originally was one of bool|int|float, unless data is cached, the original typing is preserved. And maybe unless registered. With more (strict) comparison, typing of parametres and properties in PHP this can lead to errors, even fatal ones. If saved data is not scalar, then the data is serialized before saving. On retrieving data `maybe_unserialize` is called to ensure serialized data is unswerialized. This is know to be slow.
 1. Non scalar incnosistency
 2. Bad performance having tp check all data for serialization and maybe do unserialization

# Possible solutions

1, Always use `register_settings` or `register_meta`. Curently thse registers is not quite compatible with PHP typing. See emarly comments by @azaozz on the ticket.
2. Always cast the value after return from these functions, before strict comparison, passing on to methods/functions or to class properties. Need to know the initial intended type when saved, or loose som informations held by the type
3. Add a $type argument to the `get` functions, whcich will internally cast the return value
4. Add an extra column in the options and all meta db-tables to hold type, cast on return. Such table changes will be quite heavy, have to be the only sane way to do this.
5. Add an extra row in the db-tables, in case data is not saved as string (default). This opens for two variants
 - Implicit type. Type is determid by 'gettype()' or `get_debug_type()`, and an extra option or meta is saved, unless it's string. 
 - Explicit type. Type is given as an extra $type argument (as string, defaults to 'string' or ''). Must be sanitized, but may allw for shorthand names ('bool', 'str', 'arr', 'obj')
 - Implicit OR explicit type, by allowing $type to be null for implicit type. This my proposal.

# add_option

Current signature:  `add_option( string $option, mixed $value = '', string $deprecated = '', string|bool $autoload = 'yes' ): bool`

Proposed signature: `add_option( string $option, mixed $value = '', string $deprecated = '', string|bool $autoload = 'yes', ?string $type = null ): bool`

Alternatively: No change, use `update_option()` to save type?

# add_site_option

Current signature:  `add_site_option( string $option, mixed $value ): bool`

Proposed signature: `add_site_option( string $option, mixed $value, ?string $type = null ): bool`

# update_option

Current signature:  `update_option( string $option, mixed $value, string|bool $autoload = null ): bool`

Proposed signature: `update_option( string $option, mixed $value, string|bool $autoload = null, ?string $type = null ): bool`

## Logic
If $type is given and not null, use as explicit $type.
else detect the type of $value, use as implicit $type.

Add or update the option value in db
If successful, add or update an extra option, with a prefixed $option as "_option_type_$option" with the value of $type.

# update_site_option

Current signature:  `update_site_option( string $option, mixed $value ): bool`

Proposed signature: `update_site_option( string $option, mixed $value, ?string $type = null ): bool`

See `update_option`

# get_option
Current signature unchanged: `get_option( string $option, mixed $default_value = false ): mixed`

## Logic
$type is fetched from a prefixed $option as "_option_type_$option"
After the $value is fetched, and the $type is found and valid, perform `settype( $value, $type )`before returning it

# add_metadata

Current signature:  `add_metadata( string $meta_type, int $object_id, string $meta_key, mixed $meta_value, bool $unique = false ): int|false`

Proposed signature: `add_metadata( string $meta_type, int $object_id, string $meta_key, mixed $meta_value, bool $unique = false, ?string $type = null ): int|false`

# update_meta

Current signature:  `update_meta( int $meta_id, string $meta_key, string $meta_value ): bool`

Proposed signature: `update_meta( int $meta_id, string $meta_key, string $meta_value, ?string $type = null ): bool`

## Logic
If $type is given and not null, use as explicit $type.
else detect the type of $value, use as implicit $type.

Update the meta value in db
If successful, add or update an extra meta_key, with a prefixed $meta_key as "_meta_type_$meta_key" with the value of $type.

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
