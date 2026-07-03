<?php
/**
 * Transaction reporting abilities.
 *
 * Registers:
 *   mo-otp/enable-transaction-logging
 *   mo-otp/get-transaction-logs
 *   mo-otp/export-transaction-logs
 *   mo-otp/clear-transaction-logs
 *
 * @package miniorange-otp-verification
 */

namespace OTP\API;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OTP\Helper\MoReporting;

/**
 * Registers transaction reporting abilities.
 */
class MoReportingAbilities {

	/**
	 * Registers all transaction reporting abilities with WordPress.
	 *
	 * Called once on plugin init to make all abilities in this class
	 * available to the REST API and AI assistants.
	 *
	 * @return void
	 */
	public static function register_all() {
		static::register_enable_transaction_logging();
		static::register_get_transaction_logs();
		static::register_export_transaction_logs();
		static::register_clear_transaction_logs();
	}

	/**
	 * Registers the 'mo-otp/enable-transaction-logging' ability.
	 *
	 * This ability turns transaction logging on or off. When enabled, the
	 * plugin records every OTP send and verify attempt in a log table so
	 * you can audit activity or debug delivery problems.
	 *
	 * @return void
	 */
	public static function register_enable_transaction_logging() {
		wp_register_ability(
			'mo-otp/enable-transaction-logging',
			array(
				'label'               => 'Enable Transaction Logging',
				'description'         => 'Toggle OTP transaction logging on or off. When enabled, every OTP send and verify event is recorded in the database for auditing and reporting.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'enabled' ),
					'additionalProperties' => false,
					'properties'           => array(
						'enabled' => array(
							'type'        => 'boolean',
							'description' => 'true to enable transaction logging, false to disable.',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success' => array( 'type' => 'boolean' ),
						'message' => array( 'type' => 'string' ),
						'enabled' => array( 'type' => 'boolean' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$enabled = (bool) ( $input['enabled'] ?? false );
					update_mo_option( 'is_mo_report_enabled', $enabled ? '1' : '' );
					return array(
						'success' => true,
						'message' => 'Transaction logging ' . ( $enabled ? 'enabled' : 'disabled' ) . ' successfully.',
						'enabled' => $enabled,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/get-transaction-logs' ability.
	 *
	 * This ability reads OTP transaction log entries within a date range.
	 * Each entry shows the phone or email used, the OTP channel, the
	 * result (success or failure), and the timestamp. Logging must be
	 * enabled first via enable-transaction-logging.
	 *
	 * @return void
	 */
	public static function register_get_transaction_logs() {
		wp_register_ability(
			'mo-otp/get-transaction-logs',
			array(
				'label'               => 'Get Transaction Logs',
				'description'         => 'Query OTP transaction logs by date range, user identifier, and request type. Requires transaction logging to be enabled.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'from_date', 'to_date' ),
					'additionalProperties' => false,
					'properties'           => array(
						'from_date'    => array(
							'type'        => 'string',
							'description' => 'Start date in Y-m-d or Y-m-d H:i format (e.g. 2024-01-01).',
						),
						'to_date'      => array(
							'type'        => 'string',
							'description' => 'End date in Y-m-d or Y-m-d H:i format (e.g. 2024-12-31).',
						),
						'search_user'  => array(
							'type'        => 'string',
							'description' => 'Optional email or phone to filter logs by.',
						),
						'request_type' => array(
							'type'        => 'string',
							'enum'        => array( 'req_all', 'PHONE', 'EMAIL', 'WHATSAPP', 'NOTIFICATION' ),
							'description' => 'Filter by OTP type. Default is req_all (no filter).',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'logging_enabled' => array( 'type' => 'boolean' ),
						'total'           => array( 'type' => 'integer' ),
						'entries'         => array( 'type' => 'array' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					$from_date    = sanitize_text_field( $input['from_date'] ?? '' );
					$to_date      = sanitize_text_field( $input['to_date'] ?? '' );
					$search_user  = sanitize_text_field( $input['search_user'] ?? '' );
					$request_type = sanitize_text_field( $input['request_type'] ?? 'req_all' );

					if ( strlen( $from_date ) === 10 ) {
						$from_date .= ' 00:00:00';
					}
					if ( strlen( $to_date ) === 10 ) {
						$to_date .= ' 23:59:59';
					}

					$reporting = MoReporting::instance();
					$entries   = $reporting->get_entries( $from_date, $to_date, $search_user, $request_type );

					return array(
						'logging_enabled' => (bool) get_mo_option( 'is_mo_report_enabled' ),
						'total'           => count( $entries ),
						'entries'         => array_values( $entries ),
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/export-transaction-logs' ability.
	 *
	 * This ability returns transaction log entries within a date range in
	 * a format ready for export (CSV-style rows). Use it to download a
	 * record of all OTP activity for auditing or reporting purposes.
	 *
	 * @return void
	 */
	public static function register_export_transaction_logs() {
		wp_register_ability(
			'mo-otp/export-transaction-logs',
			array(
				'label'               => 'Export Transaction Logs',
				'description'         => 'Export transaction logs for a date range as structured data (CSV rows). Use get-transaction-logs to preview; this ability returns the same data formatted for export.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'from_date', 'to_date' ),
					'additionalProperties' => false,
					'properties'           => array(
						'from_date' => array(
							'type'        => 'string',
							'description' => 'Start date in Y-m-d format (e.g. 2024-01-01).',
						),
						'to_date'   => array(
							'type'        => 'string',
							'description' => 'End date in Y-m-d format (e.g. 2024-12-31).',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'   => array( 'type' => 'boolean' ),
						'total'     => array( 'type' => 'integer' ),
						'headers'   => array( 'type' => 'array', 'items' => array( 'type' => 'string' ) ),
						'rows'      => array( 'type' => 'array' ),
						'from_date' => array( 'type' => 'string' ),
						'to_date'   => array( 'type' => 'string' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					global $wpdb;

					$from_date = sanitize_text_field( $input['from_date'] ?? '' );
					$to_date   = sanitize_text_field( $input['to_date'] ?? '' );

					if ( empty( $from_date ) || empty( $to_date ) ) {
						return array( 'success' => false, 'message' => 'from_date and to_date are required.' );
					}

					$from_full = strlen( $from_date ) === 10 ? $from_date . ' 00:00:00' : $from_date;
					$to_full   = strlen( $to_date ) === 10 ? $to_date . ' 23:59:59' : $to_date;

					$cache_key = 'mo_reporting_export_' . md5( $from_full . '_' . $to_full );
					$rows_raw  = wp_cache_get( $cache_key, 'mo_reporting' );

					if ( false === $rows_raw ) {
						$rows_raw = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
							$wpdb->prepare(
								"SELECT id, txID, phone, email, form_name, otp_type, status, ip_address, time FROM `{$wpdb->prefix}mo_reporting` WHERE time BETWEEN %s AND %s ORDER BY time DESC",
								$from_full,
								$to_full
							),
							ARRAY_A
						);
						wp_cache_set( $cache_key, $rows_raw, 'mo_reporting', 120 );
					}

					$headers = array( 'ID', 'Transaction ID', 'Phone', 'Email', 'Form Name', 'OTP Type', 'Status', 'IP Address', 'Time' );
					$rows    = array();
					foreach ( $rows_raw as $r ) {
						$rows[] = array(
							(int) $r['id'],
							(string) $r['txID'],
							(string) $r['phone'],
							(string) $r['email'],
							(string) ( $r['form_name'] ?? '' ),
							(string) $r['otp_type'],
							(string) $r['status'],
							(string) ( $r['ip_address'] ?? '' ),
							(string) $r['time'],
						);
					}

					return array(
						'success'   => true,
						'total'     => count( $rows ),
						'headers'   => $headers,
						'rows'      => $rows,
						'from_date' => $from_full,
						'to_date'   => $to_full,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => true,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}

	/**
	 * Registers the 'mo-otp/clear-transaction-logs' ability.
	 *
	 * This ability permanently deletes transaction log entries within a
	 * given date range. Use it to clean up old log data and free up
	 * database space. This action cannot be undone.
	 *
	 * @return void
	 */
	public static function register_clear_transaction_logs() {
		wp_register_ability(
			'mo-otp/clear-transaction-logs',
			array(
				'label'               => 'Clear Transaction Logs',
				'description'         => 'Delete transaction log entries for a given date range. This is irreversible — use export-transaction-logs first to save a copy.',
				'category'            => 'mo-otp',
				'input_schema'        => array(
					'type'                 => 'object',
					'required'             => array( 'from_date', 'to_date' ),
					'additionalProperties' => false,
					'properties'           => array(
						'from_date' => array(
							'type'        => 'string',
							'description' => 'Start date in Y-m-d format.',
						),
						'to_date'   => array(
							'type'        => 'string',
							'description' => 'End date in Y-m-d format.',
						),
					),
				),
				'output_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'success'       => array( 'type' => 'boolean' ),
						'message'       => array( 'type' => 'string' ),
						'deleted_count' => array( 'type' => 'integer' ),
					),
				),
				'permission_callback' => function () {
					return current_user_can( 'manage_options' );
				},
				'execute_callback'    => function ( $input ) {
					global $wpdb;

					$from_date = sanitize_text_field( $input['from_date'] ?? '' );
					$to_date   = sanitize_text_field( $input['to_date'] ?? '' );

					if ( empty( $from_date ) || empty( $to_date ) ) {
						return array( 'success' => false, 'message' => 'from_date and to_date are required.' );
					}

					$from_full = strlen( $from_date ) === 10 ? $from_date . ' 00:00:00' : $from_date;
					$to_full   = strlen( $to_date ) === 10 ? $to_date . ' 23:59:59' : $to_date;

					$cache_key = 'mo_reporting_clear_' . md5( $from_full . '_' . $to_full );
					$rows      = wp_cache_get( $cache_key, 'mo_reporting' );

					if ( false === $rows ) {
						$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
							$wpdb->prepare(
								"SELECT id FROM `{$wpdb->prefix}mo_reporting` WHERE time BETWEEN %s AND %s",
								$from_full,
								$to_full
							),
							ARRAY_A
						);
						wp_cache_set( $cache_key, $rows, 'mo_reporting', 30 );
					}

					$deleted   = 0;
					$db_table  = $wpdb->prefix . 'mo_reporting';
					foreach ( $rows as $row ) {
						$result = $wpdb->delete( $db_table, array( 'id' => (int) $row['id'] ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						if ( false !== $result ) {
							++$deleted;
						}
					}

					wp_cache_delete( $cache_key, 'mo_reporting' );

					return array(
						'success'       => true,
						'message'       => $deleted . ' log entr' . ( 1 === $deleted ? 'y' : 'ies' ) . ' deleted.',
						'deleted_count' => $deleted,
					);
				},
				'meta'                => array(
					'show_in_rest' => true,
					'annotations'  => array(
						'readonly'      => false,
						'idempotent'    => false,
						'openWorldHint' => false,
					),
					'mcp'          => array( 'public' => true ),
				),
			)
		);
	}
}
