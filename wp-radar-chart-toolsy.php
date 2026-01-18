<?php
/**
 * Plugin Name: Radar Chart Toolsy
 * Plugin URI: https://github.com/your-username/wp-radar-chart-toolsy
 * Description: 入力された内容でレーダーチャートを生成するGutenbergブロックプラグイン
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-radar-chart-toolsy
 */

// 直接アクセスを防ぐ
if (!defined('ABSPATH')) {
    exit;
}

// プラグインのバージョン
define('WP_RADAR_CHART_TOOLSY_VERSION', '1.0.0');
define('WP_RADAR_CHART_TOOLSY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_RADAR_CHART_TOOLSY_PLUGIN_URL', plugin_dir_url(__FILE__));

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
        error_log('wp-radar-chart-toolsy: block.jsonが見つかりません。パス: ' . $block_json);
        return;
    }

    // ブロックの登録（block.jsonのパスを指定）
    $block_type = register_block_type(
        $block_json,
        array(
            'render_callback' => 'wp_radar_chart_toolsy_render_block',
        )
    );

    // エラーチェック（デバッグ用）
    if (!$block_type) {
        error_log('wp-radar-chart-toolsy: ブロックの登録に失敗しました。block.jsonのパス: ' . $block_dir);
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
                data-color='<?php echo esc_attr($chartColor); ?>'>
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
