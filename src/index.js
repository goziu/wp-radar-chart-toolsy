import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, TextControl, ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import './editor.css';
import metadata from './block.json';

const fallbackLabels = ['項目1', '項目2', '項目3', '項目4', '項目5', '項目6', '項目7'];
const fallbackChartColor = '#3b82f6';
const fallbackChartWidth = 500;
const defaultLabels =
    typeof window !== 'undefined' &&
    Array.isArray(window.wpRadarChartToolsyDefaults?.itemLabels)
        ? window.wpRadarChartToolsyDefaults.itemLabels
        : fallbackLabels;
const defaultChartColor =
    typeof window !== 'undefined' && window.wpRadarChartToolsyDefaults?.chartColor
        ? window.wpRadarChartToolsyDefaults.chartColor
        : fallbackChartColor;
const defaultChartWidth =
    typeof window !== 'undefined' && Number.isFinite(window.wpRadarChartToolsyDefaults?.chartWidth)
        ? window.wpRadarChartToolsyDefaults.chartWidth
        : fallbackChartWidth;
const getLabelForIndex = (index) => defaultLabels[index] || `項目${index + 1}`;
const isDefaultItems = (items) =>
    items.length === 5 &&
    items.every((item, index) => item.label === fallbackLabels[index] && item.value === 5);

/**
 * レーダーチャートを描画する関数
 */
function drawRadarChart(canvas, labels, values, color, showTotal) {
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;
    const centerX = canvas.width / 2;
    const centerY = canvas.height / 2;
    const radius = Math.min(centerX, centerY) - 40;
    const numPoints = labels.length;
    const angleStep = (2 * Math.PI) / numPoints;

    // キャンバスをクリア
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // グリッド線を描画
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    const maxValue = 10;
    const gridLevels = 5;

    for (let level = 1; level <= gridLevels; level++) {
        ctx.beginPath();
        const levelRadius = (radius * level) / gridLevels;
        for (let i = 0; i < numPoints; i++) {
            const angle = i * angleStep - Math.PI / 2;
            const x = centerX + levelRadius * Math.cos(angle);
            const y = centerY + levelRadius * Math.sin(angle);
            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }
        ctx.closePath();
        ctx.stroke();
    }

    // 軸線を描画
    ctx.strokeStyle = '#d1d5db';
    ctx.lineWidth = 1;
    for (let i = 0; i < numPoints; i++) {
        const angle = i * angleStep - Math.PI / 2;
        ctx.beginPath();
        ctx.moveTo(centerX, centerY);
        ctx.lineTo(
            centerX + radius * Math.cos(angle),
            centerY + radius * Math.sin(angle)
        );
        ctx.stroke();
    }

    // データポリゴンを描画
    if (values.length > 0) {
        ctx.fillStyle = color + '80'; // 50%透過
        ctx.strokeStyle = color;
        ctx.lineWidth = 2;
        ctx.beginPath();

        for (let i = 0; i < numPoints; i++) {
            const angle = i * angleStep - Math.PI / 2;
            const value = values[i] || 0;
            const pointRadius = (radius * value) / maxValue;
            const x = centerX + pointRadius * Math.cos(angle);
            const y = centerY + pointRadius * Math.sin(angle);

            if (i === 0) {
                ctx.moveTo(x, y);
            } else {
                ctx.lineTo(x, y);
            }
        }

        ctx.closePath();
        ctx.fill();
        ctx.stroke();

        // データポイントを描画
        ctx.fillStyle = color;
        for (let i = 0; i < numPoints; i++) {
            const angle = i * angleStep - Math.PI / 2;
            const value = values[i] || 0;
            const pointRadius = (radius * value) / maxValue;
            const x = centerX + pointRadius * Math.cos(angle);
            const y = centerY + pointRadius * Math.sin(angle);

            ctx.beginPath();
            ctx.arc(x, y, 4, 0, 2 * Math.PI);
            ctx.fill();
        }
    }

    // ラベルを描画
    ctx.fillStyle = '#374151';
    ctx.font = '12px sans-serif';
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';

    for (let i = 0; i < numPoints; i++) {
        const angle = i * angleStep - Math.PI / 2;
        const labelRadius = radius + 25;
        const x = centerX + labelRadius * Math.cos(angle);
        const y = centerY + labelRadius * Math.sin(angle);

        ctx.fillText(labels[i] || `項目${i + 1}`, x, y);
    }

    // 最大値を表示
    ctx.fillStyle = '#6b7280';
    ctx.font = '10px sans-serif';
    for (let i = 0; i < numPoints; i++) {
        const angle = i * angleStep - Math.PI / 2;
        const labelRadius = radius + 10;
        const x = centerX + labelRadius * Math.cos(angle);
        const y = centerY + labelRadius * Math.sin(angle);

        ctx.fillText('10', x, y);
    }

    // 合計値を表示（中央のみ）
    if (showTotal) {
        const totalValue = values.reduce((sum, value) => sum + (Number(value) || 0), 0);
        const displayTotal = `合計: ${totalValue}`;

        ctx.fillStyle = '#374151';
        ctx.font = '24px sans-serif';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        ctx.fillText(displayTotal, centerX, centerY);
    }
}

/**
 * エディタ用のブロックコンポーネント
 */
function Edit({ attributes, setAttributes }) {
    const { items, chartColor, blockId, chartWidth, showTotal, defaultsApplied } = attributes;
    const resolvedChartWidth = Number.isFinite(chartWidth) ? chartWidth : 500;
    const blockProps = useBlockProps({
        className: 'wp-radar-chart-toolsy-editor',
    });

    // 管理画面の初期値を新規ブロックに反映
    useEffect(() => {
        if (
            !defaultsApplied &&
            defaultLabels.length > 0 &&
            isDefaultItems(items) &&
            chartColor === fallbackChartColor &&
            resolvedChartWidth === fallbackChartWidth
        ) {
            const newItems = items.map((item, index) => ({
                ...item,
                label: getLabelForIndex(index),
            }));
            setAttributes({
                items: newItems,
                chartColor: defaultChartColor,
                chartWidth: defaultChartWidth,
                defaultsApplied: true,
            });
        }
    }, [defaultsApplied, items, chartColor, resolvedChartWidth]);

    // ブロックIDが未設定の場合は生成
    useEffect(() => {
        if (!blockId) {
            setAttributes({ blockId: 'radar-chart-' + Date.now() });
        }
    }, []);

    // 項目数の調整（最小5項目）
    useEffect(() => {
        if (items.length < 5) {
            const newItems = [...items];
            while (newItems.length < 5) {
                newItems.push({ label: getLabelForIndex(newItems.length), value: 5 });
            }
            setAttributes({ items: newItems });
        }
    }, [items.length]);

    // キャンバスの更新
    useEffect(() => {
        const canvas = document.getElementById('radar-chart-preview-' + blockId);
        if (canvas) {
            const labels = items.map(item => item.label || '');
            const values = items.map(item => item.value || 0);
            drawRadarChart(canvas, labels, values, chartColor, showTotal);
        }
    }, [items, chartColor, blockId, chartWidth, showTotal]);

    // 項目の更新
    const updateItem = (index, field, value) => {
        const newItems = [...items];
        newItems[index] = { ...newItems[index], [field]: value };
        setAttributes({ items: newItems });
    };

    // 項目の追加（最大7項目）
    const addItem = () => {
        if (items.length < 7) {
            setAttributes({
                items: [...items, { label: getLabelForIndex(items.length), value: 5 }],
            });
        }
    };

    // 項目の削除（最小5項目）
    const removeItem = (index) => {
        if (items.length > 5) {
            const newItems = items.filter((_, i) => i !== index);
            setAttributes({ items: newItems });
        }
    };

    const validItems = items.filter(item => item.label && item.value !== undefined);

    return (
        <div {...blockProps}>
            <InspectorControls>
                <PanelBody title="チャート設定" initialOpen={true}>
                    <div style={{ marginBottom: '16px' }}>
                        <label style={{ display: 'block', marginBottom: '8px', fontWeight: 'bold' }}>
                            チャートカラー
                        </label>
                        <input
                            type="color"
                            value={chartColor}
                            onChange={(e) => setAttributes({ chartColor: e.target.value })}
                            style={{ width: '100%', height: '40px', cursor: 'pointer' }}
                        />
                        <p style={{ marginTop: '8px', fontSize: '12px', color: '#6b7280' }}>
                            囲まれた中身は50%透過で表示されます
                        </p>
                    </div>
                    <RangeControl
                        label="チャート幅（px）"
                        value={resolvedChartWidth}
                        onChange={(value) => setAttributes({ chartWidth: value })}
                        min={200}
                        max={1200}
                        step={10}
                    />
                    <ToggleControl
                        label="合計値を出力する"
                        checked={!!showTotal}
                        onChange={(value) => setAttributes({ showTotal: value })}
                    />
                </PanelBody>

                <PanelBody title="項目設定" initialOpen={true}>
                    {items.map((item, index) => (
                        <div key={index} style={{ marginBottom: '20px', padding: '12px', border: '1px solid #e5e7eb', borderRadius: '4px' }}>
                            <TextControl
                                label={`項目${index + 1}の名前`}
                                value={item.label || ''}
                                onChange={(value) => updateItem(index, 'label', value)}
                            />
                            <RangeControl
                                label={`項目${index + 1}の値`}
                                value={item.value || 0}
                                onChange={(value) => updateItem(index, 'value', value)}
                                min={0}
                                max={10}
                                step={1}
                            />
                            {items.length > 5 && (
                                <button
                                    onClick={() => removeItem(index)}
                                    style={{
                                        marginTop: '8px',
                                        padding: '4px 8px',
                                        background: '#ef4444',
                                        color: 'white',
                                        border: 'none',
                                        borderRadius: '4px',
                                        cursor: 'pointer',
                                    }}
                                >
                                    削除
                                </button>
                            )}
                        </div>
                    ))}
                    {items.length < 7 && (
                        <button
                            onClick={addItem}
                            style={{
                                width: '100%',
                                padding: '8px',
                                background: '#3b82f6',
                                color: 'white',
                                border: 'none',
                                borderRadius: '4px',
                                cursor: 'pointer',
                            }}
                        >
                            項目を追加（最大7項目）
                        </button>
                    )}
                </PanelBody>
            </InspectorControls>

            <div style={{ padding: '20px', textAlign: 'center' }}>
                <canvas
                    id={'radar-chart-preview-' + blockId}
                    width={resolvedChartWidth}
                    height={resolvedChartWidth}
                    style={{ maxWidth: '100%', height: 'auto' }}
                />
            </div>
        </div>
    );
}

/**
 * ブロックの保存関数（サーバーサイドレンダリングを使用）
 */
function save() {
    return null; // サーバーサイドレンダリングを使用
}

// ブロックの登録
registerBlockType(metadata.name, {
    edit: Edit,
    save: save,
});
