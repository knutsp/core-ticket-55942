<?php
declare( strict_types=1 );
namespace knutsp;

/**
 * @see https://github.com/symfony/polyfill/blob/main/src/Php80/Php80.php
 */

function get_debug_type( $value ): string {

	switch ( true ) {
		case \is_null(   $value ): return 'null';
		case \is_bool(   $value ): return 'bool';
		case \is_string( $value ): return 'string';
		case \is_array(  $value ): return 'array';
		case \is_int(    $value ): return 'int';
		case \is_float(  $value ): return 'float';
		case \is_object( $value ): break;
		case $value instanceof \__PHP_Incomplete_Class: return '__PHP_Incomplete_Class';
		default:

			if ( null === $type = @\get_resource_type( $value ) ) {
				return 'unknown';
			}

			if ( $type === 'Unknown' ) {
				$type = 'closed';
			}

			return "resource ($type)";
	}

	$class = \get_class( $value );

	if ( \strpos( $class, '@' ) === false ) {
		return $class;
	}

	return ( \get_parent_class($class) ?: \key( \class_implements( $class ) ) ?: 'class' ) . '@anonymous';
}

function get_valid_type( ?string $type ): string {

	if ( \array_key_exists( $type, VALID_TYPES ) ) {
		return VALID_TYPES[ $type ];
	}
	return '';
}

function update_option( string $option, $value, $autoload = null, ?string $type = null ): bool {
	$type_prefix = TYPE_PREFIX[ 'options' ];

	if ( \is_null( $type ) ) {
		$type = get_debug_type( $value );
		$type = get_valid_type( $type );
	}

	if ( $type === 'null' ) {
		$value = '';
	}
	$upd = \update_option( $option, $value, $autoload );

	if ( $upd && $type ) {
		\update_option( $type_prefix . $option, $type, $autoload );
	}
	return (bool) $upd;
}

function get_option( string $option, $default_value = false ) {
	$type_prefix = TYPE_PREFIX[ 'options' ];
	$type  = (string) \get_option( $type_prefix . $option );
	$type  = get_valid_type( $type );
	$value = \get_option( $option, $default_value );

	if ( $type ) {
		\settype( $value, $type );
	}
	return $value;
}

function add_term_meta( int $term_id, string $meta_key, $meta_value, bool $unique = false, ?string $type = null ) {
	$type_prefix = TYPE_PREFIX[ 'meta' ];

	if ( \is_null( $type ) ) {
		$type = get_debug_type( $meta_value );
		$type = get_valid_type( $type );
	}

	if ( $type === 'null' ) {
		$meta_value = '';
	}
	$res = \add_term_meta( $term_id, $meta_key, $meta_value );

	if ( ! \is_wp_error( $res ) && $type ) {
		\update_term_meta( $term_id, $type_prefix . $meta_key, $type );
	}
	return $res;
}

function update_term_meta( int $term_id, string $meta_key, $meta_value, ?string $prev_value = '', ?string $type = null ) {
	$type_prefix = TYPE_PREFIX[ 'meta' ];

	if ( \is_null( $type ) ) {
		$type = get_debug_type( $meta_value );
		$type = get_valid_type( $type );
	}

	if ( $type === 'null' ) {
		$meta_value = '';
	}
	$res = \update_term_meta( $term_id, $meta_key, $meta_value, $prev_value );

	if ( ! \is_wp_error( $res ) && $type ) {
		\update_term_meta( $term_id, $type_prefix . $meta_key, $type );
	}
	return $res;
}

function get_term_meta( int $term_id, string $key = '', bool $single = false ) {
	$type_prefix = TYPE_PREFIX[ 'meta' ];
	$type  = (string) \get_term_meta( $term_id, $type_prefix . $key, true );
	$type  = get_valid_type( $type );
	$value = \get_term_meta( $term_id, $key, $single );

	if ( $type && $single ) {
		\settype( $value, $type );
	}
	return $value;
}
