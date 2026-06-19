<?php
/**
 * Database schema definitions.
 *
 * @package LightweightPlugins\Elallas
 */

declare(strict_types=1);

namespace LightweightPlugins\Elallas\Database;

/**
 * Database table schema.
 */
final class Schema {

	/**
	 * Cases table name.
	 *
	 * @return string
	 */
	public static function cases_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'lw_elallas_cases';
	}

	/**
	 * Case items table name.
	 *
	 * @return string
	 */
	public static function case_items_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'lw_elallas_case_items';
	}

	/**
	 * Events (audit log) table name.
	 *
	 * @return string
	 */
	public static function events_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'lw_elallas_events';
	}

	/**
	 * Documents table name.
	 *
	 * @return string
	 */
	public static function documents_table(): string {
		global $wpdb;
		return $wpdb->prefix . 'lw_elallas_documents';
	}

	/**
	 * SQL for the cases table.
	 *
	 * @return string
	 */
	public static function get_cases_sql(): string {
		$table   = self::cases_table();
		$charset = self::get_charset_collate();

		return "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			case_number VARCHAR(32) NOT NULL,
			order_id BIGINT(20) UNSIGNED NOT NULL,
			order_number VARCHAR(64) NOT NULL DEFAULT '',
			customer_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			customer_email_hash CHAR(64) NOT NULL DEFAULT '',
			customer_email_encrypted TEXT NULL,
			status VARCHAR(32) NOT NULL DEFAULT 'received',
			withdrawal_type VARCHAR(10) NOT NULL DEFAULT 'full',
			submitted_at DATETIME NULL,
			confirmed_at DATETIME NULL,
			deadline_status VARCHAR(16) NOT NULL DEFAULT 'unknown',
			order_created_at DATETIME NULL,
			order_completed_at DATETIME NULL,
			delivery_date DATETIME NULL,
			ip_hash VARCHAR(64) NOT NULL DEFAULT '',
			user_agent_hash VARCHAR(64) NOT NULL DEFAULT '',
			source_url VARCHAR(255) NOT NULL DEFAULT '',
			language VARCHAR(12) NOT NULL DEFAULT '',
			assigned_admin_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			customer_note TEXT NULL,
			bank_account_encrypted TEXT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY case_number (case_number),
			KEY order_id (order_id),
			KEY customer_id (customer_id),
			KEY status (status),
			KEY deadline_status (deadline_status)
		) {$charset};";
	}

	/**
	 * SQL for the case items table.
	 *
	 * @return string
	 */
	public static function get_case_items_sql(): string {
		$table   = self::case_items_table();
		$charset = self::get_charset_collate();

		return "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			case_id BIGINT(20) UNSIGNED NOT NULL,
			order_item_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			variation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			product_name_snapshot VARCHAR(255) NOT NULL DEFAULT '',
			sku_snapshot VARCHAR(100) NOT NULL DEFAULT '',
			qty_ordered INT(11) NOT NULL DEFAULT 0,
			qty_withdrawn INT(11) NOT NULL DEFAULT 0,
			line_total_snapshot DECIMAL(19,4) NOT NULL DEFAULT 0,
			tax_total_snapshot DECIMAL(19,4) NOT NULL DEFAULT 0,
			eligibility_flag VARCHAR(20) NOT NULL DEFAULT 'eligible',
			eligibility_note VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id),
			KEY case_id (case_id)
		) {$charset};";
	}

	/**
	 * SQL for the events (audit log) table.
	 *
	 * @return string
	 */
	public static function get_events_sql(): string {
		$table   = self::events_table();
		$charset = self::get_charset_collate();

		return "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			case_id BIGINT(20) UNSIGNED NOT NULL,
			event_type VARCHAR(50) NOT NULL,
			actor_type VARCHAR(20) NOT NULL DEFAULT 'system',
			actor_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			message TEXT NULL,
			metadata_json LONGTEXT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY case_id (case_id)
		) {$charset};";
	}

	/**
	 * SQL for the documents table.
	 *
	 * @return string
	 */
	public static function get_documents_sql(): string {
		$table   = self::documents_table();
		$charset = self::get_charset_collate();

		return "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			case_id BIGINT(20) UNSIGNED NOT NULL,
			document_type VARCHAR(50) NOT NULL DEFAULT 'withdrawal_statement',
			file_path VARCHAR(255) NOT NULL DEFAULT '',
			file_hash CHAR(64) NOT NULL DEFAULT '',
			token VARCHAR(64) NOT NULL DEFAULT '',
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY case_id (case_id)
		) {$charset};";
	}

	/**
	 * Charset collate string.
	 *
	 * @return string
	 */
	private static function get_charset_collate(): string {
		global $wpdb;
		return $wpdb->get_charset_collate();
	}
}
