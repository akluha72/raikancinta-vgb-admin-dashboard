import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

// Chart.js for the sales dashboard. Exposed globally so per-page inline
// scripts (see sales/dashboard.blade.php) can build charts from a @json blob.
import Chart from 'chart.js/auto';

window.Chart = Chart;
