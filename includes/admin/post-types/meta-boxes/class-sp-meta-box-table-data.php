<?php
/**
 * Table Data
 *
 * @author 		ThemeBoy
 * @category 	Admin
 * @package 	SportsPress/Admin/Meta_Boxes
 * @version		2.5
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * SP_Meta_Box_Table_Data
 */
class SP_Meta_Box_Table_Data {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
		$table = new SP_League_Table( $post );
		
		//Get competition id of League Table
		$competition = get_post_meta( $post->ID, 'sp_competition', true );
		if ($competition > 0){
		$table->competition = $competition;
		}

		list( $columns, $usecolumns, $data, $placeholders, $merged ) = $table->data( true );
		$adjustments = $table->adjustments;
		$highlight = get_post_meta( $table->ID, 'sp_highlight', true );
		self::table( $table->ID, $columns, $usecolumns, $data, $placeholders, $adjustments, $highlight );
	}

	/**
	 * Save meta box data
	 */
	public static function save( $post_id, $post ) {
		update_post_meta( $post_id, 'sp_highlight', sp_array_value( $_POST, 'sp_highlight', array() ) );
		update_post_meta( $post_id, 'sp_columns', sp_array_value( $_POST, 'sp_columns', array() ) );
		update_post_meta( $post_id, 'sp_adjustments', sp_array_value( $_POST, 'sp_adjustments', array() ) );
		update_post_meta( $post_id, 'sp_teams', sp_array_value( $_POST, 'sp_teams', array() ) );
	}

	/**
	 * Admin edit table
	 */
	public static function table( $id, $columns = array(), $usecolumns = null, $data = array(), $placeholders = array(), $adjustments = array(), $highlight = null, $readonly = false ) {
		if ( is_array( $usecolumns ) )
			$usecolumns = array_filter( $usecolumns );

		$mode = sp_get_post_mode( $id );

		if ( 'player' === $mode ) {
			$show_team_logo = get_option( 'sportspress_list_show_photos', 'no' ) == 'yes' ? true : false;
			$icon_class = 'sp-icon-tshirt';
		} else {
			$show_team_logo = get_option( 'sportspress_table_show_logos', 'no' ) == 'yes' ? true : false;
			$icon_class = 'sp-icon-shield';
		}
		?>

		<?php if ( $readonly ) { ?>
			<p>
				<strong><?php echo get_the_title( $id ); ?></strong>
				<a class="add-new-h2 sp-add-new-h2" href="<?php echo esc_url( admin_url( add_query_arg( array( 'post' => $id, 'action' => 'edit' ), 'post.php' ) ) ); ?>"><?php _e( 'Edit', 'sportspress' ); ?></a>
			</p>
		<?php } else { ?>
			<input type="hidden" name="sp_highlight" value="0">
			<ul class="subsubsub sp-table-bar">
				<li><a href="#sp-table-values" class="current"><?php _e( 'Values', 'sportspress' ); ?></a></li> | 
				<li><a href="#sp-table-adjustments" class=""><?php _e( 'Adjustments', 'sportspress' ); ?></a></li>
			</ul>
		<?php } ?>

		<div class="sp-data-table-container sp-table-panel sp-table-values" id="sp-table-values">
			<table class="widefat sp-data-table sp-league-table">
				<thead>
					<tr>
						<?php if ( ! $readonly ) { ?>
							<th class="radio"><span class="dashicons <?php echo $icon_class; ?> sp-tip" title="<?php _e( 'Highlight', 'sportspress' ); ?>"></span></th>
						<?php } ?>
						<th><?php _e( 'Team', 'sportspress' ); ?></th>
						<?php foreach ( $columns as $key => $label ): ?>
							<th><label for="sp_columns_<?php echo $key; ?>">
								<?php if ( ! $readonly ) { ?>
									<input type="checkbox" name="sp_columns[]" value="<?php echo $key; ?>" id="sp_columns_<?php echo $key; ?>" <?php checked( ! is_array( $usecolumns ) || in_array( $key, $usecolumns ) ); ?>>
								<?php } ?>
								<?php echo $label; ?>
							</label></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( is_array( $data ) && sizeof( $data ) > 0 ):
						$i = 0;
						foreach ( $data as $team_id => $team_stats ):
							if ( !$team_id )
								continue;

							$default_name = sp_array_value( $team_stats, 'name', '' );
							if ( $default_name == null )
								$default_name = get_the_title( $team_id );
							?>
							<tr class="sp-row sp-post<?php if ( $i % 2 == 0 ) echo ' alternate'; ?>">
								<?php if ( ! $readonly ) { ?>
									<td><input type="radio" class="sp-radio-toggle" name="sp_highlight" value="<?php echo $team_id; ?>" <?php checked( $highlight, $team_id ); ?> <?php disabled( $readonly ); ?>></td>
								<?php } ?>
								<td>
									<?php if ( $show_team_logo ) echo get_the_post_thumbnail( $team_id, 'sportspress-fit-mini' ); ?>
									<?php if ( $readonly ) { ?>
										<?php echo $default_name; ?>
									<?php } else { ?>
										<span class="sp-default-value">
											<span class="sp-default-value-input"><?php echo $default_name; ?></span>
											<a class="dashicons dashicons-edit sp-edit" title="<?php _e( 'Edit', 'sportspress' ); ?>"></a>
										</span>
										<span class="hidden sp-custom-value">
											<input type="text" name="sp_teams[<?php echo $team_id; ?>][name]" class="name sp-custom-value-input" value="<?php echo esc_attr( sp_array_value( $team_stats, 'name', '' ) ); ?>" placeholder="<?php echo esc_attr( get_the_title( $team_id ) ); ?>" size="6">
											<a class="button button-secondary sp-cancel"><?php _e( 'Cancel', 'sportspress' ); ?></a>
											<a class="button button-primary sp-save"><?php _e( 'Save', 'sportspress' ); ?></a>
										</span>
									<?php } ?>
								</td>
								<?php foreach( $columns as $column => $label ):
									$value = sp_array_value( $team_stats, $column, '' );
									$placeholder = sp_array_value( sp_array_value( $placeholders, $team_id, array() ), $column, 0 );
									$placeholder = wp_strip_all_tags( $placeholder );
									?>
									<td><input type="text" name="sp_teams[<?php echo $team_id; ?>][<?php echo $column; ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" data-placeholder="<?php echo esc_attr( $placeholder ); ?>" data-matrix="<?php echo $team_id; ?>_<?php echo $column; ?>" data-adjustment="<?php echo esc_attr( sp_array_value( sp_array_value( $adjustments, $team_id, array() ), $column, 0 ) ); ?>" <?php disabled( $readonly ); ?> /></td>
								<?php endforeach; ?>
							</tr>
							<?php
							$i++;
						endforeach;
					else:
					?>
					<tr class="sp-row alternate">
						<td colspan="<?php $colspan = sizeof( $columns ) + ( $readonly ? 1 : 2 ); echo $colspan; ?>">
							<?php printf( __( 'Select %s', 'sportspress' ), __( 'Data', 'sportspress' ) ); ?>
						</td>
					</tr>
					<?php
					endif;
					?>
				</tbody>
			</table>
		</div>
		<div class="sp-data-table-container sp-table-panel sp-table-adjustments hidden" id="sp-table-adjustments">
			<table class="widefat sp-data-table sp-league-table">
				<thead>
					<tr>
						<th><?php _e( 'Team', 'sportspress' ); ?></th>
						<?php foreach ( $columns as $key => $label ): ?>
							<th><?php echo $label; ?></th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( is_array( $data ) && sizeof( $data ) > 0 ):
						$i = 0;
						foreach ( $data as $team_id => $team_stats ):
							if ( !$team_id )
								continue;
							?>
							<tr class="sp-row sp-post<?php if ( $i % 2 == 0 ) echo ' alternate'; ?>">
								<td>
									<?php echo get_the_title( $team_id ); ?>
								</td>
								<?php foreach( $columns as $column => $label ):
									$value = sp_array_value( sp_array_value( $adjustments, $team_id, array() ), $column, '' );
									?>
									<td><input type="text" name="sp_adjustments[<?php echo $team_id; ?>][<?php echo $column; ?>]" value="<?php echo esc_attr( $value ); ?>" placeholder="0" data-matrix="<?php echo $team_id; ?>_<?php echo $column; ?>" /></td>
								<?php endforeach; ?>
							</tr>
							<?php
							$i++;
						endforeach;
					else:
					?>
					<tr class="sp-row alternate">
						<td colspan="<?php $colspan = sizeof( $columns ) + 1; echo $colspan; ?>">
							<?php printf( __( 'Select %s', 'sportspress' ), __( 'Data', 'sportspress' ) ); ?>
						</td>
					</tr>
					<?php
					endif;
					?>
				</tbody>
			</table>
		</div>
		<?php
	}
}