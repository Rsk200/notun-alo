function renderNotunAloStackedImpactChart(canvasId, rows) {
  const canvas = document.getElementById(canvasId);
  if (!canvas || !window.Chart || !Array.isArray(rows)) return;
  const colors = { Paper: '#2e7d32', Plastic: '#0288d1', Metal: '#6d4c41', Glass: '#00acc1', 'E-waste': '#ef6c00', Organic: '#7cb342', Textile: '#8e24aa', Rubber: '#455a64', Wood: '#9e7d22', Others: '#78909c' };
  const months = [...new Set(rows.map((item) => item.month))];
  const categories = [...new Set(rows.map((item) => colors[item.category] ? item.category : 'Others'))];
  const datasets = categories.map((category) => ({
    label: category,
    backgroundColor: colors[category] || colors.Others,
    borderRadius: 4,
    data: months.map((month) => rows.filter((item) => item.month === month && (colors[item.category] ? item.category : 'Others') === category).reduce((sum, item) => sum + Number(item.co2_saved_kg || 0), 0)),
  }));
  new Chart(canvas, {
    type: 'bar',
    data: { labels: months, datasets },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { position: 'bottom' }, title: { display: true, text: 'Monthly CO2 Saved by Category' } },
      scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, title: { display: true, text: 'CO2 saved (kg)' } } },
    },
  });
}
