<?php
declare( strict_types=1 );
namespace knutsp;

/**
 * Plugin Name:      	Set data type in db for options/meta
 * Description:      	Demo add/updating and getting options/meta as proposed by knutsp in https://core.trac.wordpress.org/ticket/55942#comment:78.
 * Plugin URI:       	https://nettvendt.no/
 * Version:          	1.0
 * Author:           	Knut Sparhell
 * Author URI:       	https://profiles.wordpress.org/knutsp/
 * Requires at least:	6.2
 * Requires PHP:     	7.4
 * Tested up to:     	6.2
 * Text Domain:      	core-ticket-55942
 *
 * @author knutsp
 */

const TAX    = 'core-ticket';

const PREFIX = TAX . '-55942_testing_';

const TYPE_PREFIX = [ 'options' => '_option_type_', 'meta' => '_meta_type_' ];

const VALID_TYPES = [	// alias_type => debug_type or 'object'
	''        => '',
	'null'    => 'null',
	'bool'    => 'bool',
	'boolean' => 'bool',
	'int'     => 'int',
	'integer' => 'int',
	'double'  => 'float',
	'float'   => 'float',
	'str'     => 'string',
	'string'  => 'string',
	'arr'     => 'array',
	'array'   => 'array',
	'obj'     => 'object',
	'object'  => 'object',
];

include 'functions.php';

\add_action( 'admin_menu', static function(): void {
	\register_taxonomy( TAX, 'attachment' );

	if ( ! \term_exists( TAX, TAX ) ) {
		\wp_create_term( TAX, TAX );
	}

	\add_management_page( 'Demo options/meta', 'Demo options/meta', 'activate_plugins', 'core-ticket-55942', static function(): void {
		global $wpdb;

		$pfx  = PREFIX;
		$term_id = \get_term_by( 'name', TAX, TAX )->term_id;
		?>
		<style>
			table caption { margin: 1em 1ch; text-align: left;}
			table tr { margin:  .1em 1ch; }
			table th,
			table td { padding: .1em 1ch; font-size: medium; font-family: Monospace }
			label   { font-weight: bold;    }
			label * { font-weight: initial; }
			.msg { font-size: small; font-weight: bold; }
		</style>
		<div class="wrap">
			<h1><?php echo \get_admin_page_title(); ?></h1>
<?php
			if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
//			var_dump( $_POST );

				if ( \wp_verify_nonce( $_POST['_wpnonce'], '-1' ) ) {
//					var_dump( $_POST );
					$action = \sanitize_key( $_POST['action'] ?? '' );
//					echo $action;

					switch ( $action ) {
						case 'option':
						case 'meta-single':
						case 'meta-repeat':
							$type        = \sanitize_key( $_POST['type'] ?? '' );
							$exp_type    = $_POST['exp_type'] ?? '';
							$explicit    = (bool) $exp_type;
							$name        = \sanitize_key( $_POST['name'] ?? '' );
							$raw_values  = $_POST['values'] ?? [];
							$option_name = $pfx . $name;
							$meta_key    = $pfx . $name;

							switch ( $type ) {
								case 'null':
									$values = \array_fill( 0, \count( $raw_values ) - 1, '' );
								case 'bool':
								case 'boolean':
									$values = \array_map(  'boolval', $raw_values );
									break;
								case 'int':
								case 'integer':
									$values = \array_map(   'intval', $raw_values );
									break;
								case 'double':
								case 'float':
									$values = \array_map( 'floatval', $raw_values );
									break;
								case 'str':
								case 'string':
									$values = \array_map(   'strval', $raw_values );
									break;
								case 'arr':
								case 'array':
									$values = (array) $raw_values;
									break;
								case 'obj':
								case 'object':
									$values = (object) $raw_values;
									$raw_values = (object) $raw_values;
									break;
								default:
									$values = \array_map( 'sanitize_text_field', $raw_values );
							}
//							echo '<p class="msg">Posted: '; \var_dump( $values ); echo \var_export( $values, true ), '</p>';

							if ( $name ) {

								if ( \is_scalar( \end( $values ) ) && \count( (array) $values ) === 1 ) {
									$raw_values = \end( $raw_values );
									$values = \end( $values );
								}
								\wp_using_ext_object_cache( false );
								$e_type = \is_scalar( $values ) ? ' (' . ( $explicit ? 'explicit ' . $exp_type . ' ' . \var_export( $values, true ) : 'implicit ' . get_debug_type( $values ) . '/' . \gettype( $values ) ) . ')' : '';
								$a_type = $explicit ? $exp_type : null;
//								$values = $explicit ? $raw_values : $values;

								switch ( $action ) {
									case 'option':
										$type_p = '_option_type_';
										echo '<p class="msg">Save as <code>', $action, '</code> name <code>', $option_name, '</code> value: ', \var_export( $values, true ), $e_type, '</p>';
										update_option( $option_name, $values, false, $a_type );
										\wp_cache_flush();
										$value = get_option( $option_name );
										echo '<p class="msg">Get value: ', \var_export( $value, true ), ' (' . get_debug_type( $value ) . '/' . \gettype( $value ), ')</p>';
										break;
									case 'meta-single':
										$type_p = '_meta_type_';
										echo '<p class="msg">Save as <code>', $action, '</code> key <code>', $option_name, '</code> value: ', \var_export( $values, true ), $e_type, '</p>';
										update_term_meta( $term_id, $meta_key, $values, null, $a_type );
										\wp_cache_flush();
										$value = get_term_meta( $term_id, $meta_key, true );
										echo '<p class="msg">Get value: ', \var_export( $value, true ), ' (' . get_debug_type( $value ) . '/' . \gettype( $value ), ')</p>';
										break;
									case 'meta-repeat':
										$type_p = '_meta_type_';
										\delete_term_meta( $term_id, $meta_key );
										\delete_term_meta( $term_id, $type_p . $meta_key );

										foreach ( (array) $values as $value ) {
											$e_type = \is_scalar( $value ) ? ' (' . ( $explicit ? 'explicit ' . $exp_type . ' ' . \var_export( $value, true ) : 'implicit ' . get_debug_type( $value ) . '/' . \gettype( $value ) ) . ')' : '';
											echo '<p class="msg">Save as <code>', $action, '</code> key <code>', $meta_key, '</code> value: ', \var_export( $value, true ), $e_type, '</p>';
											add_term_meta( $term_id, $meta_key, $value, false );
											\wp_cache_flush();
										}
										$value = get_term_meta( $term_id, $meta_key );
										echo '<p class="msg">Get values: ', \var_export( $value, true ), '</p>';
										break;
								}
							\wp_cache_init();
							} else {
								echo '<p>Error: Missing option name</p>';
							}
							break;
						case 'delete':

							foreach ( [ 'options', 'termmeta' ] as $table ) {
								$ids = [ 'options' => 'option_id',     'termmeta' => 'meta_id'     ][ $table ];
								$col = [ 'options' => 'option_name',   'termmeta' => 'meta_key'    ][ $table ];
								$typ = [ 'options' => '_option_type_', 'termmeta' => '_meta_type_' ][ $table ];
								$del = $wpdb->get_col( $wpdb->prepare( "SELECT `{$ids}` FROM `{$wpdb->$table}` WHERE `$col` LIKE %s OR `$col` LIKE %s;", $pfx . '%', $typ . '%' ) );

								if ( \count( $del ) > 100 ) {
									\wp_die( 'Error: To many rows to delete. Something is very, wrong.' );
								}

								if ( \is_array( $del ) ) {

									foreach ( $del as $r ) {
										$wpdb->delete( $wpdb->$table, [ $ids => \intval( $r ) ], [ '%d' ] );
									}
								}
							}
							\wp_delete_term( $term_id, TAX );
							break;
					}
				} else {
					\wp_die ( '<p>Error: Invalid or missing once</p>' );
				}
			} else {
				$name = \wp_get_current_user()->user_login;
				$type = 'str';
				$explicit = false;
			}

			foreach ( [ 'options', 'termmeta' ] as $table ) {
				$col = [ 'options' => 'option_name', 'termmeta' => 'meta_key' ][ $table ];
				$typ = [ 'options' => '_option_type_', 'termmeta' => '_meta_type_' ][ $table ];
				$data = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$wpdb->$table}` WHERE `$col` LIKE %s OR `$col` LIKE %s;", $pfx . '%', $typ . '%' ) );
				$hdrs = \end( $data );

				if ( \is_array( $data ) && \count( $data ) && \is_object( $hdrs ) ) {	?>

					<table>
						<caption><code><?php echo $wpdb->$table; ?></code></caption>
						<thead>
							<tr>
	<?php
							foreach ( \array_keys( \get_object_vars( $hdrs ) ) as $colname ) {
								?>
								<th scope="col" style="text-align: left;"><?php echo $colname; ?></th>
	<?php
							}
							?>
							</tr>
						</thead>
						<tbody>
	<?php
						foreach ( $data as $row ) {
							?>
							<tr>
	<?php
							foreach ( \get_object_vars( $row ) as $colname => $values ) {
								?>
								<td<?php echo $colname === 'option_id' ? ' style="text-align: right;"' : ''; ?>><?php echo $values; ?></td>
	<?php
							}
							?>
							</tr>
	<?php
						}
						?>
						</tbody>
					</table>
	<?php
				} else {
					echo '<p> &nbsp; No <code>' . $table . '</code> data found!</p>';
				}
			}
			?>
			<hr style="height: 2em;"/>
			<form action="<?php echo \add_query_arg( [ 'page' => \sanitize_key( $_GET['page'] ) ], $_SERVER['REQUEST_URI'] ); ?>" method="post">
				<?php echo \wp_nonce_field(); ?>
				<fieldset>
					<p>
						<label title="after prefix">Name/key
							<input type="text" name="name" value="<?php echo $name; ?>" required="required"/>
						</label>
					</p>
					<p>
						<label>Value(s) <small>(check only one value for scalar typing)</small><br/>
							<input type="checkbox" name="values[]" value="" title="empty"/> '' <small>(<i>empty</i>)</small><br/>
							<input type="checkbox" name="values[]" value="0"/> 0<br/>
							<input type="checkbox" name="values[]" value="00"/> 00<br/>
							<input type="checkbox" name="values[]" value="123"/> 123<br/>
							<input type="checkbox" name="values[]" value="0123"/> 0123<br/>
							<input type="checkbox" name="values[]" value="<?php echo \pi(); ?>" title="pi"/> <?php echo \pi(); ?><br/>
							<input type="checkbox" name="values[]" value="4-four"/> '4-four'<br/>
							<input type="checkbox" name="values[]" value="E12"/> 'E12'<br/>
							<input type="checkbox" name="values[]" value="some text"/> 'some text'<br/>
						<label>
					</p>
					<p>
						<label>Value type for implicit
							<select name="type">
								<option value="null" <?php \selected( 'null',  $type ); ?>>Null</option>
								<option value="bool" <?php \selected( 'bool',  $type ); ?>>Boolean</option>
								<option value="int"  <?php \selected( 'int' ,  $type ); ?>>Integer</option>
								<option value="float"<?php \selected( 'float', $type ); ?>>Float</option>
								<option value="str"  <?php \selected( 'str',   $type ); ?>>String</option>
								<option value="arr"  <?php \selected( 'arr',   $type ); ?>>Array</option>
								<option value="obj"  <?php \selected( 'obj',   $type ); ?>>Object</option>
							</select> <small>to convert to before use (no or null type arg)</small>
						</label>
					</p>
					<p>
						<label>Value type for explicit
							<select name="exp_type">
								<option value=""     <?php \selected( '',      $exp_type ); ?>>-use implicit-</option>
								<option value="null" <?php \selected( 'null',  $exp_type ); ?>>Null</option>
								<option value="bool" <?php \selected( 'bool',  $exp_type ); ?>>Boolean</option>
								<option value="int"  <?php \selected( 'int' ,  $exp_type ); ?>>Integer</option>
								<option value="float"<?php \selected( 'float', $exp_type ); ?>>Float</option>
								<option value="str"  <?php \selected( 'str',   $exp_type ); ?>>String</option>
								<option value="arr"  <?php \selected( 'arr',   $exp_type ); ?>>Array</option>
								<option value="obj"  <?php \selected( 'obj',   $exp_type ); ?>>Object</option>
							</select> <small>to pass to update/add (type arg set)</small>
						</label>
					</p>
				</fieldset>
				<button type="submit" name="action" value="option">Add option</button>
				<button type="submit" name="action" value="meta-single">Add single meta</button>
				<button type="submit" name="action" value="meta-repeat">Add repeated meta</button>
			</form>
			<hr style="height: .1em;"/>
			<form action="<?php echo \add_query_arg( [ 'page' => \sanitize_key( $_GET['page'] ) ], $_SERVER['REQUEST_URI'] ); ?>" method="post">
				<?php echo \wp_nonce_field(); ?>
				<button type="submit" name="action" value="delete">Delete all testdata</button>
			</form>
		</div>
<?php
	}, 91 );
}, 91 );
