<?php
/**
 * Zero-DB JSON file storage manager for contact form messages.
 *
 * Stores visitor messages into a protected JSON file inside the WordPress uploads
 * directory (wp-content/uploads/mm-suc-p-messages/messages.json) with zero database usage.
 *
 * @package wp-maintenance-mode-site-under-construction
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MM_SUC_P_Messages {

	/**
	 * Get the directory path for storing messages in uploads.
	 * Ensures the directory exists and installs security protection files (.htaccess and index.php).
	 *
	 * @return string Absolute directory path with trailing slash, or empty string on failure.
	 */
	public static function get_dir() {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return '';
		}

		$dir = trailingslashit( $uploads['basedir'] ) . MM_SUC_P_MESSAGES_FOLDER . '/';

		if ( ! file_exists( $dir ) ) {
			if ( ! wp_mkdir_p( $dir ) ) {
				return '';
			}
		}

		self::ensure_security_files( $dir );

		return $dir;
	}

	/**
	 * Ensure the messages storage directory is protected from direct HTTP web access.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	private static function ensure_security_files( $dir ) {
		$htaccess = $dir . '.htaccess';
		if ( ! file_exists( $htaccess ) ) {
			$rules  = "# Apache 2.2\n<IfModule !mod_authz_core.c>\n\tOrder deny,allow\n\tDeny from all\n</IfModule>\n";
			$rules .= "# Apache 2.4+\n<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n";
			@file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents
		}

		$index = $dir . 'index.php';
		if ( ! file_exists( $index ) ) {
			@file_put_contents( $index, "<?php\n// Silence is golden.\n" ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents
		}
	}

	/**
	 * Get the absolute path to the messages.json file.
	 *
	 * @return string File path, or empty string if uploads is unavailable.
	 */
	public static function get_file_path() {
		$dir = self::get_dir();
		if ( '' === $dir ) {
			return '';
		}
		return $dir . 'messages.json';
	}

	/**
	 * Retrieve all stored messages.
	 *
	 * @return array List of message associative arrays, ordered newest first.
	 */
	public static function get_all() {
		$file = self::get_file_path();
		if ( '' === $file || ! file_exists( $file ) || ! is_readable( $file ) ) {
			return array();
		}

		$content = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $content || '' === trim( $content ) ) {
			return array();
		}

		$data = json_decode( $content, true );
		if ( ! is_array( $data ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Retrieve a single message by ID.
	 *
	 * @param string $id Message ID.
	 * @return array|null Message array if found, null otherwise.
	 */
	public static function get( $id ) {
		$messages = self::get_all();
		foreach ( $messages as $msg ) {
			if ( isset( $msg['id'] ) && $msg['id'] === $id ) {
				return $msg;
			}
		}
		return null;
	}

	/**
	 * Save a new message record to the JSON file.
	 *
	 * @param array $record Message record containing name, email, message, etc.
	 * @return bool True on success, false on failure.
	 */
	public static function save( $record ) {
		$file = self::get_file_path();
		if ( '' === $file ) {
			return false;
		}

		if ( empty( $record['id'] ) ) {
			$record['id'] = 'msg_' . wp_generate_uuid4();
		}

		if ( empty( $record['timestamp'] ) ) {
			$record['timestamp'] = time();
		}

		if ( empty( $record['date'] ) ) {
			$record['date'] = current_time( 'mysql' );
		}

		if ( ! isset( $record['read'] ) ) {
			$record['read'] = false;
		}

		$fp = @fopen( $file, 'c+' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fp ) {
			return false;
		}

		$saved = false;

		if ( flock( $fp, LOCK_EX ) ) {
			$size    = filesize( $file );
			$content = $size > 0 ? fread( $fp, $size ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$list    = array();

			if ( ! empty( $content ) ) {
				$decoded = json_decode( $content, true );
				if ( is_array( $decoded ) ) {
					$list = $decoded;
				}
			}

			// Prepend newest message to top of list.
			array_unshift( $list, $record );

			$json = wp_json_encode( $list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

			ftruncate( $fp, 0 );
			rewind( $fp );
			fwrite( $fp, $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			fflush( $fp );
			flock( $fp, LOCK_UN );
			$saved = true;
		}

		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $saved;
	}

	/**
	 * Delete a single message by ID.
	 *
	 * @param string $id Message ID.
	 * @return bool True on success, false on failure.
	 */
	public static function delete( $id ) {
		$file = self::get_file_path();
		if ( '' === $file || ! file_exists( $file ) ) {
			return false;
		}

		$fp = @fopen( $file, 'c+' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fp ) {
			return false;
		}

		$deleted = false;

		if ( flock( $fp, LOCK_EX ) ) {
			$size    = filesize( $file );
			$content = $size > 0 ? fread( $fp, $size ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$list    = array();

			if ( ! empty( $content ) ) {
				$decoded = json_decode( $content, true );
				if ( is_array( $decoded ) ) {
					$list = $decoded;
				}
			}

			$filtered = array();
			foreach ( $list as $msg ) {
				if ( isset( $msg['id'] ) && $msg['id'] === $id ) {
					$deleted = true;
					continue;
				}
				$filtered[] = $msg;
			}

			$json = wp_json_encode( $filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

			ftruncate( $fp, 0 );
			rewind( $fp );
			fwrite( $fp, $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			fflush( $fp );
			flock( $fp, LOCK_UN );
		}

		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $deleted;
	}

	/**
	 * Mark a message as read.
	 *
	 * @param string $id Message ID.
	 * @return bool True on success, false on failure.
	 */
	public static function mark_read( $id ) {
		$file = self::get_file_path();
		if ( '' === $file || ! file_exists( $file ) ) {
			return false;
		}

		$fp = @fopen( $file, 'c+' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fp ) {
			return false;
		}

		$updated = false;

		if ( flock( $fp, LOCK_EX ) ) {
			$size    = filesize( $file );
			$content = $size > 0 ? fread( $fp, $size ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread
			$list    = array();

			if ( ! empty( $content ) ) {
				$decoded = json_decode( $content, true );
				if ( is_array( $decoded ) ) {
					$list = $decoded;
				}
			}

			foreach ( $list as &$msg ) {
				if ( isset( $msg['id'] ) && $msg['id'] === $id ) {
					$msg['read'] = true;
					$updated     = true;
					break;
				}
			}
			unset( $msg );

			if ( $updated ) {
				$json = wp_json_encode( $list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );
				ftruncate( $fp, 0 );
				rewind( $fp );
				fwrite( $fp, $json ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
				fflush( $fp );
			}

			flock( $fp, LOCK_UN );
		}

		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $updated;
	}

	/**
	 * Delete all stored messages.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function clear_all() {
		$file = self::get_file_path();
		if ( '' === $file ) {
			return false;
		}

		$fp = @fopen( $file, 'w' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_fopen
		if ( ! $fp ) {
			return false;
		}

		if ( flock( $fp, LOCK_EX ) ) {
			fwrite( $fp, "[]\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
			fflush( $fp );
			flock( $fp, LOCK_UN );
		}

		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return true;
	}

	/**
	 * Count total messages and unread messages.
	 *
	 * @return array Array with 'total' and 'unread' counts.
	 */
	public static function count() {
		$messages = self::get_all();
		$total    = count( $messages );
		$unread   = 0;

		foreach ( $messages as $msg ) {
			if ( empty( $msg['read'] ) ) {
				$unread++;
			}
		}

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
