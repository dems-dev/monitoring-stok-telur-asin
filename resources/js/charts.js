/**
 * Chart.js dibundel lewat Vite (bukan CDN) agar grafik dashboard tetap tampil
 * tanpa koneksi internet. Entry ini hanya dimuat di halaman dashboard.
 */
import Chart from 'chart.js/auto';

window.Chart = Chart;
