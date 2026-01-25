<?php

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! function_exists( 'my_toolsy_check_theme' ) ) {
	function my_toolsy_check_theme() {

	if ( ! defined( 'MY_TOOLSY_THEME_CHECKED' ) ) {
		define( 'MY_TOOLSY_THEME_CHECKED', MY_TOOLSY_SIGN );
	}


	$theme  = wp_get_theme();
	$parent = $theme->parent();
	$theme_name = $parent ? $parent->get( 'Name' ) : $theme->get( 'Name' );


	$allowed = array( 'Cocoon', 'AFFINGER' );


	foreach ( $allowed as $name ) {
		if ( stripos( $theme_name, $name ) !== false ) {
			return;
		}
	}


	if ( is_admin() ) {
		$GLOBALS['my_toolsy_plugin_name'] = my_toolsy_get_plugin_name();


		add_action( 'admin_notices', function () {
			$plugin_name = isset( $GLOBALS['my_toolsy_plugin_name'] ) ? $GLOBALS['my_toolsy_plugin_name'] : 'この';
			echo '<div class="notice notice-error is-dismissible"><p>';
			echo esc_html( $plugin_name ) . 'プラグインはWordPressテーマ「<a href="https://wp-cocoon.com/" target="_blank" rel="noopener nofollow">cocoon</a>」';
			echo 'または「<a href="https://on-store.net/" target="_blank" rel="noopener nofollow">AFFINGER</a>」で利用できます';
			echo '</p></div>';
		} );


		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( MY_TOOLSY_PLUGIN_FILE ) );
		unset( $_GET['activate'] );
	}


	if ( ! defined( 'MY_TOOLSY_BLOCKED' ) ) define( 'MY_TOOLSY_BLOCKED', true );
	}
}

