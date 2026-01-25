<?php
/**
 * Plugin Name: Radar Chart Toolsy
 * Plugin URI: https://plus-webskill.com/plugins
 * Description: 入力された内容でレーダーチャートを生成するGutenbergブロックプラグイン
 * Version: 1.0.2
 * Author: Toolsy
 * Author URI: https://plus-webskill.com/
 * Requires at least: 6.8
 * Requires PHP:      7.4
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグインのバージョン
define('WP_RADAR_CHART_TOOLSY_VERSION', '1.0.0');
define('WP_RADAR_CHART_TOOLSY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_RADAR_CHART_TOOLSY_PLUGIN_URL', plugin_dir_url(__FILE__));

if ( ! defined( 'MY_TOOLSY_PLUGIN_FILE' ) ) {
	define( 'MY_TOOLSY_PLUGIN_FILE', __FILE__ );
}

require_once __DIR__ . '/inc/toolsy-core.php';
require_once __DIR__ . '/inc/toolsy-theme-check.php';

add_action( 'plugins_loaded', 'my_toolsy_integrity_guard_strong', 0 );

/**
 * 管理画面の初期ラベル
 */
function wp_radar_chart_toolsy_get_default_labels() {
    return array('項目1', '項目2', '項目3', '項目4', '項目5', '項目6', '項目7');
}

/**
 * 管理画面設定の取得
 */
function wp_radar_chart_toolsy_get_settings() {
    $defaults = array(
        'item_labels' => wp_radar_chart_toolsy_get_default_labels(),
        'chart_color' => '#3b82f6',
        'chart_width' => 500,
    );
    $options = get_option('wp_radar_chart_toolsy_settings', array());
    $options = wp_parse_args($options, $defaults);

    if (!is_array($options['item_labels'])) {
        $options['item_labels'] = $defaults['item_labels'];
    }

    for ($i = 0; $i < 7; $i++) {
        if (!isset($options['item_labels'][$i]) || $options['item_labels'][$i] === '') {
            $options['item_labels'][$i] = $defaults['item_labels'][$i];
        } else {
            $options['item_labels'][$i] = sanitize_text_field($options['item_labels'][$i]);
        }
    }

    $chart_color = sanitize_hex_color($options['chart_color']);
    if (!$chart_color) {
        $chart_color = $defaults['chart_color'];
    }

    $chart_width = absint($options['chart_width']);
    if ($chart_width < 200) {
        $chart_width = 200;
    }
    if ($chart_width > 1200) {
        $chart_width = 1200;
    }

    $options['chart_color'] = $chart_color;
    $options['chart_width'] = $chart_width;

    return $options;
}

/**
 * 管理画面設定のサニタイズ
 */
function wp_radar_chart_toolsy_sanitize_settings($options) {
    $defaults = wp_radar_chart_toolsy_get_default_labels();
    $labels = array();

    if (isset($options['item_labels']) && is_array($options['item_labels'])) {
        $labels = array_slice($options['item_labels'], 0, 7);
    }

    for ($i = 0; $i < 7; $i++) {
        $value = isset($labels[$i]) ? $labels[$i] : $defaults[$i];
        $labels[$i] = sanitize_text_field($value);
        if ($labels[$i] === '') {
            $labels[$i] = $defaults[$i];
        }
    }

    $chart_color = isset($options['chart_color']) ? sanitize_hex_color($options['chart_color']) : '#3b82f6';
    if (!$chart_color) {
        $chart_color = '#3b82f6';
    }

    $chart_width = isset($options['chart_width']) ? absint($options['chart_width']) : 500;
    if ($chart_width < 200) {
        $chart_width = 200;
    }
    if ($chart_width > 1200) {
        $chart_width = 1200;
    }

    return array(
        'item_labels' => $labels,
        'chart_color' => $chart_color,
        'chart_width' => $chart_width,
    );
}

/**
 * 管理画面メニューの追加
 */
function wp_radar_chart_toolsy_add_admin_menu() {
    add_menu_page(
        'レーダーチャート設定',
        'レーダーチャート',
        'manage_options',
        'wp-radar-chart-toolsy-settings',
        'wp_radar_chart_toolsy_render_settings_page',
        'dashicons-chart-area',
        81
    );
}
add_action('admin_menu', 'wp_radar_chart_toolsy_add_admin_menu');

/**
 * 管理画面設定の登録
 */
function wp_radar_chart_toolsy_register_settings() {
    register_setting(
        'wp_radar_chart_toolsy_settings',
        'wp_radar_chart_toolsy_settings',
        array(
            'sanitize_callback' => 'wp_radar_chart_toolsy_sanitize_settings',
        )
    );
}
add_action('admin_init', 'wp_radar_chart_toolsy_register_settings');

/**
 * 管理画面の設定ページ
 */
function wp_radar_chart_toolsy_render_settings_page() {
    if (!current_user_can('manage_options')) {
        return;
    }

    $settings = wp_radar_chart_toolsy_get_settings();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('レーダーチャート設定', 'wp-radar-chart-toolsy'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('wp_radar_chart_toolsy_settings'); ?>
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row"><?php echo esc_html__('チャートカラー', 'wp-radar-chart-toolsy'); ?></th>
                        <td>
                            <input type="color"
                                   name="wp_radar_chart_toolsy_settings[chart_color]"
                                   value="<?php echo esc_attr($settings['chart_color']); ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php echo esc_html__('チャート幅（px）', 'wp-radar-chart-toolsy'); ?></th>
                        <td>
                            <input type="number"
                                   name="wp_radar_chart_toolsy_settings[chart_width]"
                                   value="<?php echo esc_attr($settings['chart_width']); ?>"
                                   min="200"
                                   max="1200"
                                   step="10"
                                   class="small-text" />
                        </td>
                    </tr>
                    <?php for ($i = 0; $i < 7; $i++) : ?>
                        <tr>
                            <th scope="row">
                                <?php echo esc_html(sprintf('項目%dの初期名', $i + 1)); ?>
                            </th>
                            <td>
                                <input type="text"
                                       name="wp_radar_chart_toolsy_settings[item_labels][]"
                                       value="<?php echo esc_attr($settings['item_labels'][$i]); ?>"
                                       class="regular-text" />
                            </td>
                        </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * ブロックを登録
 */
function wp_radar_chart_toolsy_register_block() {
    // ブロックが登録されているか確認
    if (!function_exists('register_block_type')) {
        return;
    }

    // block.jsonのパス（@wordpress/scriptsはbuild/block.jsonに生成する）
    $block_json = WP_RADAR_CHART_TOOLSY_PLUGIN_DIR . 'build/block.json';
    
    // block.jsonが存在するか確認
    if (!file_exists($block_json)) {
        return;
    }

    // ブロックの登録（block.jsonのパスを指定）
    $block_type = register_block_type(
        $block_json,
        array(
            'render_callback' => 'wp_radar_chart_toolsy_render_block',
        )
    );

    if ($block_type && !empty($block_type->editor_script_handles)) {
        $editor_handle = $block_type->editor_script_handles[0];
        add_action('enqueue_block_editor_assets', function() use ($editor_handle) {
            $settings = wp_radar_chart_toolsy_get_settings();
            $inline_data = array(
                'itemLabels' => array_values($settings['item_labels']),
                'chartColor' => $settings['chart_color'],
                'chartWidth' => $settings['chart_width'],
            );
            $script = 'window.wpRadarChartToolsyDefaults = ' . wp_json_encode($inline_data) . ';';
            wp_add_inline_script($editor_handle, $script, 'before');
        });
    }

    // エラーチェック（デバッグ用）
    if (!$block_type) {
        return;
    }
}
add_action('init', 'wp_radar_chart_toolsy_register_block');

/**
 * ブロックのフロントエンドレンダリング
 */
function wp_radar_chart_toolsy_render_block($attributes) {
    // 属性のデフォルト値
    $items = isset($attributes['items']) ? $attributes['items'] : array();
    $chartColor = isset($attributes['chartColor']) ? $attributes['chartColor'] : '#3b82f6';
    $blockId = isset($attributes['blockId']) ? $attributes['blockId'] : 'radar-chart-' . uniqid();
    $chartWidth = isset($attributes['chartWidth']) ? intval($attributes['chartWidth']) : 500;
    $showTotal = !empty($attributes['showTotal']);
    if ($chartWidth < 200) {
        $chartWidth = 200;
    }

    // 有効な項目のみをフィルタリング（最小5項目）
    $validItems = array_filter($items, function($item) {
        return !empty($item['label']) && isset($item['value']);
    });

    if (count($validItems) < 5) {
        return '<p>' . esc_html__('レーダーチャートを表示するには、少なくとも5つの項目が必要です。', 'wp-radar-chart-toolsy') . '</p>';
    }

    // データの準備
    $labels = array();
    $values = array();
    foreach ($validItems as $item) {
        $labels[] = esc_html($item['label']);
        $values[] = floatval($item['value']);
    }

    // HTMLの生成
    ob_start();
    ?>
    <div class="wp-radar-chart-toolsy-container" data-block-id="<?php echo esc_attr($blockId); ?>">
        <canvas id="<?php echo esc_attr($blockId); ?>"
                width="<?php echo esc_attr($chartWidth); ?>"
                height="<?php echo esc_attr($chartWidth); ?>"
                data-labels='<?php echo esc_attr(json_encode($labels)); ?>'
                data-values='<?php echo esc_attr(json_encode($values)); ?>'
                data-color='<?php echo esc_attr($chartColor); ?>'
                data-show-total="<?php echo esc_attr($showTotal ? '1' : '0'); ?>">
        </canvas>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * フロントエンド用のスクリプトとスタイルをエンキュー
 */
function wp_radar_chart_toolsy_enqueue_assets() {
    // フロントエンドでのみ読み込む
    if (is_admin()) {
        return;
    }

    // Chart.jsの読み込み
    wp_enqueue_script(
        'chart-js',
        'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
        array(),
        '4.4.0',
        true
    );

    // プラグインのフロントエンドスクリプト
    wp_enqueue_script(
        'wp-radar-chart-toolsy-frontend',
        WP_RADAR_CHART_TOOLSY_PLUGIN_URL . 'build/frontend.js',
        array('chart-js'),
        WP_RADAR_CHART_TOOLSY_VERSION,
        true
    );

    // プラグインのフロントエンドスタイル
    wp_enqueue_style(
        'wp-radar-chart-toolsy-frontend',
        WP_RADAR_CHART_TOOLSY_PLUGIN_URL . 'build/frontend.css',
        array(),
        WP_RADAR_CHART_TOOLSY_VERSION
    );
}
add_action('wp_enqueue_scripts', 'wp_radar_chart_toolsy_enqueue_assets');
