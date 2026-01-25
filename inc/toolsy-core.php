<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'MY_TOOLSY_SIGN' ) ) {
	define( 'MY_TOOLSY_SIGN', 'v1_9f3a2c' );
}

if ( ! function_exists( 'my_toolsy_get_plugin_name' ) ) {
	function my_toolsy_get_plugin_name() {
		if ( is_admin() ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$data = get_plugin_data( MY_TOOLSY_PLUGIN_FILE, false, false );
			if ( ! empty( $data['Name'] ) ) {
				return $data['Name'];
			}
		}
		return 'この';
	}
}

if ( ! function_exists( 'my_toolsy_integrity_guard_strong' ) ) {
	function my_toolsy_integrity_guard_strong() {

	if ( ! function_exists( 'my_toolsy_check_theme' ) ) {
		if ( is_admin() ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( plugin_basename( MY_TOOLSY_PLUGIN_FILE ) );
			unset( $_GET['activate'] );
		}
		if ( ! defined( 'MY_TOOLSY_BLOCKED' ) ) define( 'MY_TOOLSY_BLOCKED', true );
		return;
	}


	my_toolsy_check_theme();


	if ( ! defined( 'MY_TOOLSY_THEME_CHECKED' ) || MY_TOOLSY_THEME_CHECKED !== MY_TOOLSY_SIGN ) {
		if ( is_admin() ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			deactivate_plugins( plugin_basename( MY_TOOLSY_PLUGIN_FILE ) );
			unset( $_GET['activate'] );
		}
		if ( ! defined( 'MY_TOOLSY_BLOCKED' ) ) define( 'MY_TOOLSY_BLOCKED', true );
		return;
	}
	}
}

