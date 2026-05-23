const $ = (id) => document.getElementById(id);
let lastKpis = null;
let filtersReady = false;

window.addEventListener('DOMContentLoaded', async () => {
    await loadKpis();
    $('btnAplicarFiltros')?.addEventListener('click', async () => await loadKpis(getFilters()));
    $('btnLimpiarFiltros')?.addEventListener('click', async () => { clearFilters(); await loadKpis(); });
    document.querySelectorAll('.olap-op').forEach(btn => btn.addEventListener('click', () => applyOlap(btn.dataset.op)));
    $('btnExportImage').addEventListener('click', exportImage);
    $('btnExportPowerBI').addEventListener('click', exportPowerBI);
    $('btnExportPdf').addEventListener('click', exportPdf);
});

function getFilters() {
    return {
        anio: $('filterAnio')?.value || '',
        mes: $('filterMes')?.value || '',
        edificio: $('filterEdificio')?.value || '',
        tipo_ambiente: $('filterTipo')?.value || '',
        riesgo: $('filterRiesgo')?.value || '',
        hora_desde: $('filterHoraDesde')?.value || '',
        hora_hasta: $('filterHoraHasta')?.value || '',
        min_consumo: $('filterMinConsumo')?.value || '',
    };
}

function clearFilters() {
    ['filterAnio','filterMes','filterEdificio','filterTipo','filterRiesgo','filterHoraDesde','filterHoraHasta','filterMinConsumo'].forEach(id => {
        const el = $(id);
        if (el) el.value = '';
    });
}

function queryFromFilters(filters = getFilters()) {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([k, v]) => { if (v !== '' && v !== null && v !== undefined) params.set(k, v); });
    return params.toString();
}

async function loadKpis(filters = {}) {
    try {
        const qs = queryFromFilters(filters);
        const response = await fetch('api/kpis.php' + (qs ? '?' + qs : ''));
        const json = await response.json();
        if (!json.ok) {
            alert(json.message || 'No se pudieron cargar los KPIs.');
            return;
        }
        lastKpis = json.data;
        if (!filtersReady) {
            renderFilterOptions(lastKpis.filterOptions || {});
            filtersReady = true;
        }
        renderDashboard(lastKpis);
    } catch (error) {
        console.error(error);
        alert('No se pudo conectar con los KPIs. Revisa Apache, MySQL y la sesión activa.');
    }
}

function renderFilterOptions(options) {
    fillSelect('filterAnio', options.anios || [], 'Todos');
    fillSelect('filterMes', options.meses || [], 'Todos', monthLabel);
    fillSelect('filterEdificio', options.edificios || [], 'Todos');
    fillSelect('filterTipo', options.tiposAmbiente || [], 'Todos');
}

function fillSelect(id, values, allText = 'Todos', labelFn = (v) => v) {
    const select = $(id);
    if (!select) return;
    const current = select.value;
    select.innerHTML = `<option value="">${allText}</option>`;
    [...new Set(values.filter(v => v !== null && v !== undefined && String(v) !== ''))].forEach(value => {
        const option = document.createElement('option');
        option.value = value;
        option.textContent = labelFn(value);
        select.appendChild(option);
    });
    select.value = current;
}

function monthLabel(value) {
    const names = {1:'Enero',2:'Febrero',3:'Marzo',4:'Abril',5:'Mayo',6:'Junio',7:'Julio',8:'Agosto',9:'Setiembre',10:'Octubre',11:'Noviembre',12:'Diciembre'};
    return names[Number(value)] || value;
}

async function applyOlap(op) {
    if (!lastKpis) return;
    const opts = lastKpis.filterOptions || {};
    clearFilters();
    if (op === 'slice') {
        const meses = opts.meses || [];
        if (meses.length) $('filterMes').value = meses.includes(5) || meses.includes('5') ? '5' : meses[0];
    }
    if (op === 'dice') {
        const tipos = opts.tiposAmbiente || [];
        const edificios = opts.edificios || [];
        if (tipos.includes('Laboratorio')) $('filterTipo').value = 'Laboratorio';
        else if (tipos.length) $('filterTipo').value = tipos[0];
        if (edificios.length) $('filterEdificio').value = edificios[0];
        $('filterRiesgo').value = '1';
    }
    if (op === 'drill') {
        $('filterHoraDesde').value = 8;
        $('filterHoraHasta').value = 20;
    }
    if (op === 'rollup') {
        const anios = opts.anios || [];
        if (anios.length) $('filterAnio').value = anios[0];
    }
    if (op === 'pivot') {
        const tipos = opts.tiposAmbiente || [];
        if (tipos.length) $('filterTipo').value = tipos[0];
    }
    await loadKpis(getFilters());
}

function renderDashboard(data) {
    $('totalDbRows').textContent = Number(data.totalDbRows || 0).toLocaleString('es-PE');
    $('filteredRows').textContent = Number(data.filteredRows || 0).toLocaleString('es-PE');
    $('kpiTotal').textContent = formatKwh(data.consumoActual || data.totalConsumo || 0);
    const v = Number(data.variacion || 0);
    $('kpiVariacion').textContent = `vs mes anterior: ${v >= 0 ? '+' : ''}${v.toFixed(2)}%`;
    $('kpiCausa').textContent = data.causaPrincipal || 'Sin datos suficientes.';
    $('kpiPred').textContent = formatKwh(data.prediccion6Meses || 0);
    $('kpiAccion').textContent = data.accionRecomendada || 'Sin recomendación.';

    renderTable(data.warehouseRows || []);

    drawLineChart('chartLine', data.seriesMes || [], '#123f92');
    drawLineChart('chartPred', data.seriesPrediccion || createProjectionSeries(data.seriesMes || []), '#f97316');

    drawLineChart('chartPred1', data.seriesPrediccion || [], '#123f92');
    drawBarChart('chartPred2', data.demandaAmbientes || [], ['#123f92', '#d9a000']);
    drawLineChart('chartPred3', data.riesgoPorMes || [], '#ef4444');
    drawBarChart('chartPred4', data.periodosAltoConsumo || [], ['#6f42c1', '#123f92']);

    drawPieChart('chartPie', data.distribucionAmbiente || []);
    drawLineChart('chartTrend', data.seriesMes || [], '#123f92');
    drawBarChart('chartBars', data.topEdificios || [], ['#123f92', '#d9a000']);
    drawBarChart('chartHist', data.histogramaHoras || [], ['#6f42c1', '#d9a000']);

    drawLineChart('chartDemand', data.demandaPorMes || [], '#e75b22');
    drawLineChart('chartFactor', data.factorPorMes || [], '#198754');
    drawBarChart('chartAmbientes', data.topAmbientes || [], ['#198754', '#123f92']);
    drawBarChart('chartRiskRank', data.rankingRiesgo || [], ['#ef4444', '#8b1e3f']);
    drawScatterChart('chartScatter', data.scatterTempConsumo || []);
    drawHeatmap('chartHeatmap', data.heatmapHoraMes || []);
}

function renderTable(rows) {
    const tbody = document.querySelector('#warehouseTable tbody');
    tbody.innerHTML = '';
    rows.slice(0, 150).forEach(row => {
        const tr = document.createElement('tr');
        const values = [
            row.id_fact,
            row.anio,
            row.mes,
            row.hora,
            row.edificio,
            row.ambiente,
            row.tipo_ambiente,
            `${Number(row.ocupacion || 0).toFixed(0)}%`,
            `${Number(row.temperatura || 0).toFixed(2)} °C`,
            `${Number(row.demanda_pico_kw || 0).toFixed(2)} kW`,
            Number(row.factor_potencia || 0).toFixed(3),
            Number(row.consumo_kwh || 0).toFixed(2),
            Number(row.eficiencia || 0).toFixed(3),
            row.riesgo_sobreconsumo == 1 ? 'ALTO' : 'OK'
        ];
        values.forEach((value, idx) => {
            const td = document.createElement('td');
            td.textContent = value ?? '';
            if (idx === values.length - 1) td.className = row.riesgo_sobreconsumo == 1 ? 'risk' : 'safe';
            tr.appendChild(td);
        });
        tbody.appendChild(tr);
    });
}

function formatKwh(value) {
    return `${Number(value || 0).toLocaleString('es-PE', { maximumFractionDigits: 2 })} kWh`;
}

function createProjectionSeries(series) {
    const values = series.map(x => Number(x.value || 0));
    if (values.length === 0) return [];
    const last = values[values.length - 1];
    const prev = values.length > 1 ? values[values.length - 2] : last;
    const slope = last - prev;
    const projection = [];
    for (let i = 1; i <= 6; i++) projection.push({ label: `Mes +${i}`, value: Math.max(0, last + slope * i) });
    return projection;
}

function clearCanvas(canvas) {
    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    const cssWidth = Math.max(250, rect.width || 320);
    const cssHeight = Math.max(150, rect.height || 180);
    canvas.width = cssWidth * devicePixelRatio;
    canvas.height = cssHeight * devicePixelRatio;
    canvas.style.width = cssWidth + 'px';
    canvas.style.height = cssHeight + 'px';
    ctx.setTransform(devicePixelRatio, 0, 0, devicePixelRatio, 0, 0);
    ctx.clearRect(0, 0, cssWidth, cssHeight);
    return { ctx, w: cssWidth, h: cssHeight };
}

function drawLineChart(id, series, color = '#123f92') {
    const canvas = $(id);
    if (!canvas) return;
    const { ctx, w, h } = clearCanvas(canvas);
    ctx.font = '11px Arial';
    ctx.strokeStyle = '#dbe3ef';
    ctx.lineWidth = 1;
    for (let i = 0; i < 4; i++) {
        const y = 18 + i * (h - 45) / 3;
        ctx.beginPath(); ctx.moveTo(30, y); ctx.lineTo(w - 12, y); ctx.stroke();
    }
    if (!series.length) {
        ctx.fillStyle = '#64748b'; ctx.fillText('Sin datos', w / 2 - 24, h / 2); return;
    }
    const vals = series.map(s => Number(s.value || 0));
    const max = Math.max(...vals, 1);
    const min = Math.min(...vals, 0);
    const range = Math.max(max - min, 1);
    const points = vals.map((v, i) => {
        const x = 34 + i * ((w - 52) / Math.max(vals.length - 1, 1));
        const y = h - 28 - ((v - min) / range) * (h - 55);
        return { x, y, v, label: series[i].label };
    });
    ctx.strokeStyle = color;
    ctx.lineWidth = 2.3;
    ctx.beginPath();
    points.forEach((p, i) => i ? ctx.lineTo(p.x, p.y) : ctx.moveTo(p.x, p.y));
    ctx.stroke();
    ctx.fillStyle = '#d97706';
    points.forEach(p => { ctx.beginPath(); ctx.arc(p.x, p.y, 3, 0, Math.PI * 2); ctx.fill(); });
    ctx.fillStyle = '#334155';
    points.forEach((p, i) => {
        if (i % Math.ceil(points.length / 6 || 1) === 0) ctx.fillText(String(p.label).slice(0, 8), p.x - 10, h - 8);
    });
}

function drawBarChart(id, series, colors = ['#123f92', '#d9a000']) {
    const canvas = $(id);
    if (!canvas) return;
    const { ctx, w, h } = clearCanvas(canvas);
    if (!series.length) { ctx.fillStyle = '#64748b'; ctx.fillText('Sin datos', w / 2 - 24, h / 2); return; }
    const vals = series.map(s => Number(s.value || 0));
    const max = Math.max(...vals, 1);
    const n = Math.min(series.length, 12);
    const barW = (w - 48) / n;
    ctx.font = '10px Arial';
    for (let i = 0; i < n; i++) {
        const s = series[i];
        const bh = (Number(s.value || 0) / max) * (h - 48);
        const x = 30 + i * barW;
        const y = h - 28 - bh;
        ctx.fillStyle = colors[i % colors.length];
        ctx.fillRect(x + 4, y, Math.max(8, barW - 8), bh);
        ctx.fillStyle = '#334155';
        ctx.save();
        ctx.translate(x + barW / 2, h - 10);
        ctx.rotate(-Math.PI / 8);
        ctx.fillText(String(s.label).slice(0, 10), -16, 0);
        ctx.restore();
    }
}

function drawPieChart(id, series) {
    const canvas = $(id);
    if (!canvas) return;
    const { ctx, w, h } = clearCanvas(canvas);
    if (!series.length) { ctx.fillStyle = '#64748b'; ctx.fillText('Sin datos', w / 2 - 24, h / 2); return; }
    const total = series.reduce((a, b) => a + Number(b.value || 0), 0) || 1;
    const cx = w * 0.34, cy = h * 0.48, r = Math.min(w, h) * 0.26;
    let start = -Math.PI / 2;
    const colors = ['#123f92', '#198754', '#d9a000', '#6f42c1', '#ef4444', '#14b8a6', '#64748b'];
    series.forEach((s, i) => {
        const angle = (Number(s.value || 0) / total) * Math.PI * 2;
        ctx.beginPath(); ctx.moveTo(cx, cy); ctx.arc(cx, cy, r, start, start + angle); ctx.closePath();
        ctx.fillStyle = colors[i % colors.length]; ctx.fill();
        start += angle;
    });
    ctx.font = '11px Arial';
    series.slice(0, 6).forEach((s, i) => {
        const x = w * 0.62, y = 28 + i * 19;
        ctx.fillStyle = colors[i % colors.length]; ctx.fillRect(x, y - 9, 10, 10);
        ctx.fillStyle = '#334155'; ctx.fillText(`${String(s.label).slice(0, 13)}: ${Math.round((s.value / total) * 100)}%`, x + 14, y);
    });
}

function drawScatterChart(id, points) {
    const canvas = $(id);
    if (!canvas) return;
    const { ctx, w, h } = clearCanvas(canvas);
    ctx.font = '11px Arial';
    if (!points.length) { ctx.fillStyle = '#64748b'; ctx.fillText('Sin datos', w / 2 - 24, h / 2); return; }
    const xs = points.map(p => Number(p.x || 0));
    const ys = points.map(p => Number(p.y || 0));
    const minX = Math.min(...xs), maxX = Math.max(...xs);
    const minY = Math.min(...ys), maxY = Math.max(...ys);
    const rx = Math.max(maxX - minX, 1), ry = Math.max(maxY - minY, 1);
    ctx.strokeStyle = '#dbe3ef'; ctx.beginPath(); ctx.moveTo(35, 15); ctx.lineTo(35, h-30); ctx.lineTo(w-15, h-30); ctx.stroke();
    points.forEach(p => {
        const x = 35 + ((Number(p.x) - minX) / rx) * (w - 55);
        const y = h - 30 - ((Number(p.y) - minY) / ry) * (h - 50);
        ctx.fillStyle = p.r == 1 ? '#ef4444' : '#123f92';
        ctx.globalAlpha = 0.75;
        ctx.beginPath(); ctx.arc(x, y, 3.5, 0, Math.PI * 2); ctx.fill();
    });
    ctx.globalAlpha = 1;
    ctx.fillStyle = '#334155';
    ctx.fillText('Temp. °C', w - 70, h - 9);
    ctx.save(); ctx.translate(12, 80); ctx.rotate(-Math.PI/2); ctx.fillText('Consumo kWh', 0, 0); ctx.restore();
}

function drawHeatmap(id, series) {
    const canvas = $(id);
    if (!canvas) return;
    const { ctx, w, h } = clearCanvas(canvas);
    if (!series.length) { ctx.fillStyle = '#64748b'; ctx.fillText('Sin datos', w / 2 - 24, h / 2); return; }
    const parsed = series.map(s => {
        const [m, hour] = String(s.label).split('-').map(Number);
        return { m, hour, value: Number(s.value || 0) };
    }).filter(x => !isNaN(x.m) && !isNaN(x.hour));
    if (!parsed.length) { ctx.fillStyle = '#64748b'; ctx.fillText('Sin datos', w / 2 - 24, h / 2); return; }
    const months = [...new Set(parsed.map(x => x.m))].sort((a,b) => a-b).slice(0, 12);
    const hours = [...new Set(parsed.map(x => x.hour))].sort((a,b) => a-b).slice(0, 12);
    const max = Math.max(...parsed.map(x => x.value), 1);
    const left = 34, top = 18, cw = (w - 48) / Math.max(hours.length,1), ch = (h - 46) / Math.max(months.length,1);
    ctx.font = '9px Arial';
    months.forEach((m, mi) => {
        ctx.fillStyle = '#334155'; ctx.fillText(monthLabel(m).slice(0,3), 5, top + mi*ch + ch*0.65);
        hours.forEach((hour, hi) => {
            const item = parsed.find(x => x.m === m && x.hour === hour);
            const val = item ? item.value : 0;
            const alpha = Math.min(0.95, 0.12 + (val / max) * 0.83);
            ctx.fillStyle = `rgba(18,63,146,${alpha})`;
            ctx.fillRect(left + hi*cw, top + mi*ch, Math.max(2,cw-2), Math.max(2,ch-2));
        });
    });
    ctx.fillStyle = '#334155';
    hours.forEach((hour, hi) => ctx.fillText(String(hour), left + hi*cw + 2, h - 8));
}

async function exportImage() {
    const area = $('exportArea');
    if (!window.html2canvas) {
        alert('No se pudo cargar html2canvas. Verifica conexión a internet o usa captura manual.');
        return;
    }
    const canvas = await html2canvas(area, { backgroundColor: '#ffffff', scale: 2 });
    const link = document.createElement('a');
    link.download = 'dashboard_smart_campus.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

function exportPowerBI() {
    const qs = queryFromFilters();
    window.location.href = 'api/export.php?format=powerbi' + (qs ? '&' + qs : '');
}

async function exportPdf() {
    const area = $('exportArea');
    if (!window.html2canvas || !window.jspdf) {
        window.print();
        return;
    }
    const canvas = await html2canvas(area, { backgroundColor: '#ffffff', scale: 2 });
    const imgData = canvas.toDataURL('image/png');
    const { jsPDF } = window.jspdf;
    const pdf = new jsPDF('l', 'mm', 'a4');
    const pageWidth = pdf.internal.pageSize.getWidth();
    const pageHeight = pdf.internal.pageSize.getHeight();
    const imgWidth = pageWidth - 12;
    const imgHeight = canvas.height * imgWidth / canvas.width;
    let y = 6;
    if (imgHeight <= pageHeight - 12) {
        pdf.addImage(imgData, 'PNG', 6, y, imgWidth, imgHeight);
    } else {
        let remainingHeight = imgHeight;
        while (remainingHeight > 0) {
            pdf.addImage(imgData, 'PNG', 6, y, imgWidth, imgHeight);
            remainingHeight -= pageHeight - 12;
            if (remainingHeight > 0) {
                pdf.addPage();
                y -= pageHeight - 12;
            }
        }
    }
    pdf.save('dashboard_smart_campus.pdf');
}
