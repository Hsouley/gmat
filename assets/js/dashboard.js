/* Dashboard Charts - GMAT */
(function() {
    'use strict';
    function initDashboard() {
        const chartsData = window.gmatCharts || {};
        const catCanvas = document.getElementById('chartCategorie');
        if (catCanvas && chartsData.categories) {
            new Chart(catCanvas, {
                type: 'doughnut',
                data: {
                    labels: chartsData.categories.map(c => c.nom),
                    datasets: [{
                        data: chartsData.categories.map(c => c.quantite),
                        backgroundColor: ['#0d6efd', '#20c997', '#6610f2', '#dc3545', '#fd7e14', '#198754'],
                        borderWidth: 2, borderColor: '#fff'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }
        const etatCanvas = document.getElementById('chartEtat');
        if (etatCanvas && chartsData.etats) {
            const colors = { 'Neuf': '#198754', 'Très bon état': '#20c997', 'Bon état': '#0dcaf0', 'État moyen': '#fd7e14', 'Mauvais état': '#dc3545', 'En panne': '#6c757d' };
            new Chart(etatCanvas, {
                type: 'pie',
                data: {
                    labels: chartsData.etats.map(e => e.etat),
                    datasets: [{
                        data: chartsData.etats.map(e => e.quantite),
                        backgroundColor: chartsData.etats.map(e => colors[e.etat] || '#6c757d'),
                        borderWidth: 2, borderColor: '#fff'
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
            });
        }
        const serviceCanvas = document.getElementById('chartService');
        if (serviceCanvas && chartsData.services) {
            new Chart(serviceCanvas, {
                type: 'bar',
                data: {
                    labels: chartsData.services.map(s => s.nom),
                    datasets: [{
                        label: 'Quantité affectée',
                        data: chartsData.services.map(s => s.total),
                        backgroundColor: '#0d6efd', borderRadius: 6
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }
    }
    if (typeof Chart !== 'undefined') {
        document.addEventListener('DOMContentLoaded', initDashboard);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
            script.onload = initDashboard;
            document.head.appendChild(script);
        });
    }
})();
