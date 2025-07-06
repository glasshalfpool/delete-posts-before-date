<?php

/**
 * Plugin Name: Delete Posts before date 
 * Description: Glasshalfpool Custom plugin to delete attachments before set date
 * Version: 1.6
 * Author: Jamie Glasspool
 */

declare(strict_types=1);

/**
 * Delete all media attachments uploaded before a specific date.
 *
 * @param string $date Date in 'Y-m-d' format (e.g., '2024-05-01').
 * @return int Number of deleted attachments.
 */
function delete_attachments_before_date( string $date ): int {
	if ( ! current_user_can( 'delete_posts' ) ) {
		return 0;
	}

	global $wpdb;

	$attachments = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_date < %s",
			$date
		)
	);

	$deleted = 0;
	foreach ( $attachments as $attachment_id ) {
		if ( wp_delete_attachment( (int) $attachment_id, true ) ) {
			$deleted++;
		}
	}

	return $deleted;
}

/**
 * Add admin menu page for deleting attachments before a date.
 */
function dpad_add_admin_menu() {
    add_menu_page(
        __( 'Delete Attachments by Date', 'delete-posts-before-date' ),
        __( 'Delete Attachments', 'delete-posts-before-date' ),
        'delete_posts',
        'delete-attachments-before-date',
        'dpad_admin_page',
        'dashicons-trash',
        80
    );
}
add_action( 'admin_menu', 'dpad_add_admin_menu' );

/**
 * Render the admin page and handle form submission.
 */
function dpad_admin_page() {
    $message = '';
    if ( isset( $_POST['dpad_submit'] ) && check_admin_referer( 'dpad_delete_attachments', 'dpad_nonce' ) ) {
        if ( current_user_can( 'delete_posts' ) ) {
            $date = isset( $_POST['dpad_date'] ) ? sanitize_text_field( $_POST['dpad_date'] ) : '';
            if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
                $deleted_count = delete_attachments_before_date( $date );
                $message = sprintf( __( 'Deleted %d attachments uploaded before %s.', 'delete-posts-before-date' ), $deleted_count, esc_html( $date ) );
            } else {
                $message = __( 'Please enter a valid date in YYYY-MM-DD format.', 'delete-posts-before-date' );
            }
        } else {
            $message = __( 'You do not have permission to delete attachments.', 'delete-posts-before-date' );
        }
    }
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Delete Attachments by Date', 'delete-posts-before-date' ); ?></h1>
        <?php if ( $message ) : ?>
            <div class="notice notice-info is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
        <?php endif; ?>
        <form method="post">
            <?php wp_nonce_field( 'dpad_delete_attachments', 'dpad_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="dpad_date"><?php esc_html_e( 'Delete attachments uploaded before:', 'delete-posts-before-date' ); ?></label></th>
                    <td><input type="date" id="dpad_date" name="dpad_date" required pattern="\d{4}-\d{2}-\d{2}"></td>
                </tr>
            </table>
            <?php submit_button( __( 'Delete Attachments', 'delete-posts-before-date' ), 'primary', 'dpad_submit' ); ?>
        </form>
    </div>
    <?php
}

?>