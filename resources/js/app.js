import { Chart } from 'chart.js/auto';

const chartRegistry = new WeakMap();

function initializeCharts() {
	document.querySelectorAll('canvas[data-chart-config]').forEach((canvas) => {
		const rawConfig = canvas.getAttribute('data-chart-config');

		if (! rawConfig) {
			return;
		}

		const existingChart = chartRegistry.get(canvas);

		if (existingChart) {
			existingChart.destroy();
		}

		const chart = new Chart(canvas, JSON.parse(rawConfig));

		chartRegistry.set(canvas, chart);
	});
}

document.addEventListener('DOMContentLoaded', initializeCharts);
document.addEventListener('livewire:navigated', initializeCharts);
