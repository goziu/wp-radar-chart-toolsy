/**
 * フロントエンド用のレーダーチャート描画
 */

(function() {
    'use strict';

    // Chart.jsが読み込まれているか確認
    if (typeof Chart === 'undefined') {
        console.error('Chart.js is not loaded');
        return;
    }

    /**
     * レーダーチャートを描画
     */
    function initRadarChart(canvas) {
        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const values = JSON.parse(canvas.dataset.values || '[]');
        const color = canvas.dataset.color || '#3b82f6';
        const showTotal = canvas.dataset.showTotal === '1';

        if (labels.length === 0 || values.length === 0) {
            return;
        }

        // アニメーション用のデータを初期化（0から開始）
        const animatedValues = new Array(values.length).fill(0);
        let animationProgress = 0;
        const animationDuration = 1000; // 1秒
        const startTime = Date.now();

        const totalValue = values.reduce((sum, value) => sum + (Number(value) || 0), 0);
        const totalText = `合計: ${totalValue}`;

        const totalLabelPlugin = {
            id: 'totalLabelPlugin',
            afterDraw(chart) {
                if (!showTotal) {
                    return;
                }

                const ctx = chart.ctx;
                if (!ctx) {
                    return;
                }

                const chartArea = chart.chartArea;
                const centerX = chartArea ? (chartArea.left + chartArea.right) / 2 : chart.width / 2;
                const centerY = chartArea ? (chartArea.top + chartArea.bottom) / 2 : chart.height / 2;

                ctx.save();
                ctx.fillStyle = '#374151';
                ctx.font = '24px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(totalText, centerX, centerY);
                ctx.restore();
            }
        };

        // Chart.jsの設定
        const chartConfig = {
            type: 'radar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'データ',
                    data: animatedValues,
                    backgroundColor: color + '80', // 50%透過
                    borderColor: color,
                    borderWidth: 2,
                    pointBackgroundColor: color,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: color,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        enabled: true,
                    }
                },
                scales: {
                    r: {
                        beginAtZero: true,
                        max: 10,
                        min: 0,
                        ticks: {
                            stepSize: 2,
                            display: false,
                        },
                        grid: {
                            color: '#e5e7eb',
                        },
                        pointLabels: {
                            font: {
                                size: 12,
                            },
                            color: '#374151',
                        },
                    }
                },
                animation: {
                    duration: 0, // Chart.jsのアニメーションを無効化（手動で制御）
                }
            },
            plugins: [totalLabelPlugin]
        };

        const chart = new Chart(canvas, chartConfig);

        /**
         * アニメーション関数
         */
        function animate() {
            const currentTime = Date.now();
            const elapsed = currentTime - startTime;
            animationProgress = Math.min(elapsed / animationDuration, 1);

            // イージング関数（ease-out）
            const easeOut = 1 - Math.pow(1 - animationProgress, 3);

            // 値を更新
            for (let i = 0; i < values.length; i++) {
                animatedValues[i] = values[i] * easeOut;
            }

            chart.update('none'); // アニメーションなしで更新

            if (animationProgress < 1) {
                requestAnimationFrame(animate);
            }
        }

        // アニメーション開始
        animate();

        return chart;
    }

    /**
     * Intersection Observerで要素が表示されたら初期化
     */
    function initCharts() {
        const containers = document.querySelectorAll('.wp-radar-chart-toolsy-container');

        if (containers.length === 0) {
            return;
        }

        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const container = entry.target;
                    const canvas = container.querySelector('canvas');

                    if (canvas && !canvas.dataset.initialized) {
                        canvas.dataset.initialized = 'true';
                        initRadarChart(canvas);
                        observer.unobserve(container);
                    }
                }
            });
        }, observerOptions);

        containers.forEach(container => {
            observer.observe(container);
        });
    }

    // DOMContentLoadedまたはChart.js読み込み後に初期化
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCharts);
    } else {
        // Chart.jsが既に読み込まれている場合
        if (typeof Chart !== 'undefined') {
            initCharts();
        } else {
            // Chart.jsの読み込みを待つ
            window.addEventListener('load', initCharts);
        }
    }
})();
