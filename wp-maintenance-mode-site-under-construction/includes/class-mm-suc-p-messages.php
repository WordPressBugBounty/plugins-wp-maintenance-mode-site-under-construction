<?php
/**
 * Database storage manager for contact form messages.
 *
 * Stores visitor contact messages in a dedicated custom table ({$wpdb->prefix}mm_suc_messages)
 * with indexes, prepared statements, and support for automated data cleanup upon uninstallation.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SUC_P_Messages {

	/**
	 * Schema version for database upgrades.
	 */
	const DB_VERSION = '1.0.0';

	/**
	 * Option key tracking installed database version.
	 */
	const DB_VERSION_KEY = 'mm_suc_p_messages_db_version';

	/**
	 * Get the custom table name with WordPress prefix.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'mm_suc_messages';
	}

	/**
	 * Create or update the custom database table using dbDelta.
	 *
	 * @return void
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table_name} (
			id varchar(64) NOT NULL,
			name varchar(100) NOT NULL DEFAULT '',
			email varchar(190) NOT NULL DEFAULT '',
			message text NOT NULL,
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY is_read (is_read),
			KEY created_at (created_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_KEY, self::DB_VERSION );
	}

	/**
	 * Ensure the table is created if missing or outdated.
	 *
	 * @return void
	 */
	public static function maybe_create_table() {
		$installed = get_option( self::DB_VERSION_KEY, '' );

		if ( self::DB_VERSION !== $installed ) {
			self::create_table();
			self::migrate_from_json();
		}
	}

	/**
	 * Migrate legacy file-based messages from JSON storage into the database table.
	 *
	 * @return void
	 */
	public static function migrate_from_json() {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return;
		}

		$dir  = trailingslashit( $uploads['basedir'] ) . MM_SUC_P_MESSAGES_FOLDER . '/';
		$file = $dir . 'messages.json';

		if ( ! file_exists( $file ) || ! is_readable( $file ) ) {
			return;
		}

		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( ! empty( $content ) ) {
			$data = json_decode( $content, true );

			if ( is_array( $data ) && ! empty( $data ) ) {
				global $wpdb;
				$table_name = self::get_table_name();

				foreach ( $data as $record ) {
					if ( empty( $record['id'] ) ) {
						continue;
					}

					$id         = sanitize_text_field( $record['id'] );
					$name       = isset( $record['name'] ) ? sanitize_text_field( $record['name'] ) : '';
					$email      = isset( $record['email'] ) ? sanitize_email( $record['email'] ) : '';
					$message    = isset( $record['message'] ) ? sanitize_textarea_field( $record['message'] ) : '';
					$is_read    = ! empty( $record['read'] ) ? 1 : 0;
					$created_at = ! empty( $record['date'] ) ? sanitize_text_field( $record['date'] ) : current_time( 'mysql' );

					$wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$table_name,
						array(
							'id'         => $id,
							'name'       => $name,
							'email'      => $email,
							'message'    => $message,
							'is_read'    => $is_read,
							'created_at' => $created_at,
						),
						array( '%s', '%s', '%s', '%s', '%d', '%s' )
					);
				}
			}
		}

		// Clean up the legacy JSON file and directory once migrated.
		@unlink( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( $dir . '.htaccess' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@unlink( $dir . 'index.php' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@rmdir( $dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	/**
	 * Retrieve all stored messages ordered newest first.
	 *
	 * @return array List of message associative arrays.
	 */
	public static function get_all() {
		global $wpdb;

		self::maybe_create_table();
		$table_name = self::get_table_name();

		$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT id, name, email, message, is_read, created_at FROM {$table_name} ORDER BY created_at DESC",
			ARRAY_A
		);

		if ( ! is_array( $results ) ) {
			return array();
		}

		$messages = array();

		foreach ( $results as $row ) {
			$messages[] = array(
				'id'        => (string) $row['id'],
				'name'      => (string) $row['name'],
				'email'     => (string) $row['email'],
				'message'   => (string) $row['message'],
				'read'      => ! empty( $row['is_read'] ),
				'date'      => (string) $row['created_at'],
				'timestamp' => strtotime( (string) $row['created_at'] ),
			);
		}

		return $messages;
	}

	/**
	 * Retrieve a single message by ID.
	 *
	 * @param string $id Message ID.
	 * @return array|null Message array if found, null otherwise.
	 */
	public static function get( $id ) {
		global $wpdb;

		self::maybe_create_table();
		$table_name = self::get_table_name();

		$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id, name, email, message, is_read, created_at FROM {$table_name} WHERE id = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return array(
			'id'        => (string) $row['id'],
			'name'      => (string) $row['name'],
			'email'     => (string) $row['email'],
			'message'   => (string) $row['message'],
			'read'      => ! empty( $row['is_read'] ),
			'date'      => (string) $row['created_at'],
			'timestamp' => strtotime( (string) $row['created_at'] ),
		);
	}

	/**
	 * Save a new message record.
	 *
	 * @param array $record Message record containing name, email, message, etc.
	 * @return bool True on success, false on failure.
	 */
	public static function save( $record ) {
		global $wpdb;

		self::maybe_create_table();
		$table_name = self::get_table_name();

		$id         = ! empty( $record['id'] ) ? sanitize_text_field( $record['id'] ) : 'msg_' . wp_generate_uuid4();
		$name       = isset( $record['name'] ) ? sanitize_text_field( $record['name'] ) : '';
		$email      = isset( $record['email'] ) ? sanitize_email( $record['email'] ) : '';
		$message    = isset( $record['message'] ) ? sanitize_textarea_field( $record['message'] ) : '';
		$is_read    = ! empty( $record['read'] ) ? 1 : 0;
		$created_at = ! empty( $record['date'] ) ? sanitize_text_field( $record['date'] ) : current_time( 'mysql' );

		$saved = $wpdb->replace( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_name,
			array(
				'id'         => $id,
				'name'       => $name,
				'email'      => $email,
				'message'    => $message,
				'is_read'    => $is_read,
				'created_at' => $created_at,
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s' )
		);

		return false !== $saved;
	}

	/**
	 * Delete a single message by ID.
	 *
	 * @param string $id Message ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $id ) {
		global $wpdb;

		self::maybe_create_table();
		$table_name = self::get_table_name();

		$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_name,
			array( 'id' => $id ),
			array( '%s' )
		);

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * Mark a message as read.
	 *
	 * @param string $id Message ID.
	 * @return bool True on success, false on failure.
	 */
	public static function mark_read( $id ) {
		global $wpdb;

		self::maybe_create_table();
		$table_name = self::get_table_name();

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_name,
			array( 'is_read' => 1 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%s' )
		);

		return false !== $updated;
	}

	/**
	 * Delete all stored messages (truncate).
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function clear_all() {
		global $wpdb;

		self::maybe_create_table();
		$table_name = self::get_table_name();

		$cleared = $wpdb->query( "TRUNCATE TABLE {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return false !== $cleared;
	}

	/**
	 * Count total messages and unread messages.
	 *
	 * @return array Array with 'total' and 'unread' counts.
	 */
	public static function count() {
		global $wpdb;

		self::maybe_create_table();
		$table_name = self::get_table_name();

		$total  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$unread = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name} WHERE is_read = 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array(
			'total'  => $total,
			'unread' => $unread,
		);
	}
}

/**
 * Functional wrappers for convenience and consistency with plugin conventions.
 */

function mm_suc_p_get_messages() {
	return MM_SUC_P_Messages::get_all();
}

function mm_suc_p_save_message( $record ) {
	return MM_SUC_P_Messages::save( $record );
}

function mm_suc_p_delete_message( $id ) {
	return MM_SUC_P_Messages::delete( $id );
}

function mm_suc_p_clear_all_messages() {
	return MM_SUC_P_Messages::clear_all();
}

function mm_suc_p_mark_message_read( $id ) {
	return MM_SUC_P_Messages::mark_read( $id );
}

function mm_suc_p_get_messages_count() {
	return MM_SUC_P_Messages::count();
}
