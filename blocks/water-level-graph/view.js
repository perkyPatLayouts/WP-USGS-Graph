/**
 * Frontend script for USGS Water Level Graph block.
 *
 * Renders Chart.js line charts for water level data.
 */

(function() {
	'use strict';

	/**
	 * Initialize all charts on page load.
	 */
	function initCharts() {
		const canvases = document.querySelectorAll('.usgs-water-levels-chart-wrapper canvas');

		canvases.forEach(function(canvas) {
			const chartData = JSON.parse(canvas.dataset.chartData || '{}');
			const chartType = canvas.dataset.chartType || 'line';
			const lineColor = canvas.dataset.lineColor || '#0073aa';

			if (!chartData.labels || !chartData.datasets) {
				return;
			}

			// Get parent wrapper for styling context
			const wrapper = canvas.closest('.usgs-water-levels-chart-wrapper');
			const backgroundColor = wrapper ? getComputedStyle(wrapper).backgroundColor : '#ffffff';

			// Configure Chart.js
			const ctx = canvas.getContext('2d');

			// Destroy existing chart if it exists
			if (canvas.chart) {
				canvas.chart.destroy();
			}

			// Prepare dataset configuration based on chart type
			let datasetConfig = {
				label: chartData.datasets[0].label || 'Water Level (ft)',
				data: chartData.datasets[0].data,
				borderColor: lineColor,
				backgroundColor: hexToRgba(lineColor, chartType === 'bar' ? 0.7 : 0.1),
				borderWidth: 2
			};

			// Add type-specific configurations
			if (chartType === 'line') {
				// Line chart - no fill, show points
				datasetConfig.fill = false;
				datasetConfig.pointRadius = 3;
				datasetConfig.pointBackgroundColor = lineColor;
				datasetConfig.pointBorderColor = '#fff';
				datasetConfig.pointBorderWidth = 1;
				datasetConfig.pointHoverRadius = 5;
				datasetConfig.tension = 0.3;
			} else if (chartType === 'area') {
				// Area chart - filled line with gradient
				datasetConfig.fill = true;
				datasetConfig.backgroundColor = hexToRgba(lineColor, 0.2);
				datasetConfig.pointRadius = 2;
				datasetConfig.pointBackgroundColor = lineColor;
				datasetConfig.pointBorderColor = '#fff';
				datasetConfig.pointBorderWidth = 1;
				datasetConfig.pointHoverRadius = 4;
				datasetConfig.tension = 0.4;
			} else if (chartType === 'bar') {
				// Bar chart - solid bars, no points or tension
				datasetConfig.borderWidth = 1;
				datasetConfig.barThickness = 'flex';
				datasetConfig.maxBarThickness = 40;
			}

			// Determine Chart.js type (area uses 'line' type with fill)
			const chartJsType = chartType === 'area' ? 'line' : chartType;

			// Create new chart
			canvas.chart = new Chart(ctx, {
				type: chartJsType,
				data: {
					labels: chartData.labels,
					datasets: [datasetConfig]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					aspectRatio: 2,
					plugins: {
						legend: {
							display: true,
							position: 'top',
							labels: {
								color: '#333333',
								font: {
									size: 14,
									weight: 'bold'
								}
							}
						},
						tooltip: {
							mode: 'index',
							intersect: false,
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							titleColor: '#ffffff',
							bodyColor: '#ffffff',
							borderColor: lineColor,
							borderWidth: 1,
							padding: 12,
							displayColors: true,
							callbacks: {
								label: function(context) {
									return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' ft';
								}
							}
						}
					},
					scales: {
						x: {
							display: true,
							title: {
								display: true,
								text: 'Measurement Date',
								color: '#666666',
								font: {
									size: 13,
									weight: 'bold'
								}
							},
							ticks: {
								color: '#666666',
								maxRotation: 45,
								minRotation: 45,
								autoSkip: true,
								maxTicksLimit: 12
							},
							grid: {
								display: true,
								color: 'rgba(0, 0, 0, 0.05)'
							}
						},
						y: {
							display: true,
							title: {
								display: true,
								text: 'Water Level (feet)',
								color: '#666666',
								font: {
									size: 13,
									weight: 'bold'
								}
							},
							ticks: {
								color: '#666666',
								callback: function(value) {
									return value.toFixed(2) + ' ft';
								}
							},
							grid: {
								display: true,
								color: 'rgba(0, 0, 0, 0.1)'
							}
						}
					},
					interaction: {
						mode: 'nearest',
						axis: 'x',
						intersect: false
					}
				}
			});
		});
	}

	/**
	 * Convert hex color to rgba.
	 *
	 * @param {string} hex Hex color code.
	 * @param {number} alpha Alpha value (0-1).
	 * @return {string} RGBA color string.
	 */
	function hexToRgba(hex, alpha) {
		// Remove # if present
		hex = hex.replace('#', '');

		// Parse hex values
		const r = parseInt(hex.substring(0, 2), 16);
		const g = parseInt(hex.substring(2, 4), 16);
		const b = parseInt(hex.substring(4, 6), 16);

		return 'rgba(' + r + ', ' + g + ', ' + b + ', ' + alpha + ')';
	}

	/**
	 * Initialize on DOM ready.
	 */
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCharts);
	} else {
		initCharts();
	}

	/**
	 * Re-initialize charts on block editor updates (for live preview).
	 */
	if (window.wp && window.wp.data) {
		let timeout;
		window.wp.data.subscribe(function() {
			clearTimeout(timeout);
			timeout = setTimeout(initCharts, 500);
		});
	}
})();
