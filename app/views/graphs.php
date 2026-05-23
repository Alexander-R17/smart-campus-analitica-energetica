<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gráficas KPI | Smart Campus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body graphs-body">
    <main class="graphs-main graphs-main-olap">
        <section class="export-panel report-actions-panel print-hide">
            <div class="export-group left-actions">
                <a href="index.php?route=dashboard" class="btn-secondary action-btn"><span class="btn-icon">↩</span> Volver al proceso</a>
                <a href="index.php?route=logout" class="btn-logout action-btn"><span class="btn-icon">⏻</span> Salir</a>
            </div>
            <div class="export-group export-actions">
                <button id="btnExportImage" class="btn-primary action-btn"><span class="btn-icon">🖼️</span> Exportar formato imagen</button>
                <button id="btnExportPowerBI" class="btn-primary powerbi-btn action-btn"><span class="btn-icon">📊</span> Exportar formato Power BI</button>
                <button id="btnExportPdf" class="btn-primary pdf-btn action-btn"><span class="btn-icon">📄</span> Exportar formato PDF</button>
            </div>
        </section>

        <section class="analytics-layout">
            <aside class="olap-sidebar print-hide">
                <div class="filter-title">🔎 FILTROS DIMENSIONALES</div>
                <label>Año</label>
                <select id="filterAnio"><option value="">Todos</option></select>
                <label>Mes</label>
                <select id="filterMes"><option value="">Todos</option></select>
                <label>Edificio</label>
                <select id="filterEdificio"><option value="">Todos</option></select>
                <label>Tipo de ambiente</label>
                <select id="filterTipo"><option value="">Todos</option></select>
                <label>Riesgo energético</label>
                <select id="filterRiesgo">
                    <option value="">Todos</option>
                    <option value="0">Normal</option>
                    <option value="1">Alto riesgo</option>
                </select>
                <div class="two-inputs">
                    <div><label>Hora desde</label><input id="filterHoraDesde" type="number" min="0" max="23" placeholder="0"></div>
                    <div><label>Hora hasta</label><input id="filterHoraHasta" type="number" min="0" max="23" placeholder="23"></div>
                </div>
                <label>Consumo mínimo (kWh)</label>
                <input id="filterMinConsumo" type="number" min="0" step="1" placeholder="Ej. 200">
                <button id="btnAplicarFiltros" class="btn-primary full"><span class="btn-icon">✅</span> Aplicar filtros</button>
                <button id="btnLimpiarFiltros" class="btn-secondary full"><span class="btn-icon">🧹</span> Limpiar filtros</button>

                <div class="filter-title secondary">⚙️ OPERACIONES OLAP</div>
                <button class="olap-op" data-op="slice"><b>SLICE</b><span>Corte por una dimensión. Ejemplo: Mes = Mayo</span></button>
                <button class="olap-op" data-op="dice"><b>DICE</b><span>Filtrar varias dimensiones. Ejemplo: Laboratorio + Riesgo</span></button>
                <button class="olap-op" data-op="drill"><b>DRILL DOWN</b><span>Profundizar: Mes → Hora</span></button>
                <button class="olap-op" data-op="rollup"><b>ROLL UP</b><span>Resumir: Hora → Mes</span></button>
                <button class="olap-op" data-op="pivot"><b>PIVOT</b><span>Cambiar filas/columnas: Edificio ↔ Ambiente</span></button>
                <small class="sidebar-note">Los filtros modifican KPIs, gráficas, ranking y exportación Power BI.</small>
            </aside>

            <section id="exportArea" class="report-canvas report-canvas-olap">
                <div class="report-titlebar">
                    <img src="assets/img/logo_UNuevaEsperanza.png" alt="Logo">
                    <div>
                        <h1>PLATAFORMA INTEGRAL DE ANALÍTICA ENERGÉTICA - SMART CAMPUS UNIVERSIDAD NUEVA ESPERANZA</h1>
                        <p>Dashboard ejecutivo generado desde Data Warehouse, Capa IA, Capa Semántica y Filtros OLAP</p>
                    </div>
                    <div class="report-meta">
                        <span>Registros BD: <b id="totalDbRows">0</b></span>
                        <span>Filtrados: <b id="filteredRows">0</b></span>
                    </div>
                </div>

                <section class="kpi-section report-section">
                    <h2>KPIs PRINCIPALES — UNA PREGUNTA POR TIPO DE ANÁLISIS</h2>
                    <div class="kpi-grid four-cols">
                        <div class="kpi-card desc">
                            <h3>DESCRIPTIVA</h3>
                            <p>¿Cuál es el consumo energético total mensual (kWh) por edificio?</p>
                            <strong id="kpiTotal">0 kWh</strong>
                            <small id="kpiVariacion">vs mes anterior: 0%</small>
                            <canvas id="chartLine" height="92"></canvas>
                        </div>
                        <div class="kpi-card diag">
                            <h3>DIAGNÓSTICA</h3>
                            <p>¿Por qué se registran mayores consumos energéticos?</p>
                            <b>Causa principal:</b>
                            <small id="kpiCausa">Cargue datos para analizar.</small>
                        </div>
                        <div class="kpi-card pred">
                            <h3>PREDICTIVA</h3>
                            <p>¿Cuál será el consumo energético proyectado en los próximos meses?</p>
                            <strong id="kpiPred">0 kWh</strong>
                            <small>Próximos 6 meses</small>
                            <canvas id="chartPred" height="92"></canvas>
                        </div>
                        <div class="kpi-card pres">
                            <h3>PRESCRIPTIVA</h3>
                            <p>¿Qué acciones deben implementarse para reducir el consumo energético y los costos?</p>
                            <b>Acción recomendada:</b>
                            <small id="kpiAccion">Cargue datos para recomendar acciones.</small>
                        </div>
                    </div>
                </section>

                <section class="prediction-section report-section">
                    <h2>ZONA PREDICCIÓN (IA) — 4 PREDICCIONES CLAVE</h2>
                    <div class="prediction-grid four-cols">
                        <div class="chart-box prediction-card"><h3>PREDICCIÓN 1</h3><p>Consumo energético futuro (kWh)</p><canvas id="chartPred1"></canvas><small>Pregunta predictiva: ¿Cuál será el consumo energético proyectado?</small></div>
                        <div class="chart-box prediction-card"><h3>PREDICCIÓN 2</h3><p>Demanda de ambientes (%)</p><canvas id="chartPred2"></canvas><small>Pregunta predictiva: ¿Qué ambientes presentarán mayor demanda?</small></div>
                        <div class="chart-box prediction-card"><h3>PREDICCIÓN 3</h3><p>Riesgo de sobreconsumo</p><canvas id="chartPred3"></canvas><small>Pregunta predictiva: ¿Qué zonas presentan mayor riesgo?</small></div>
                        <div class="chart-box prediction-card"><h3>PREDICCIÓN 4</h3><p>Periodos de alto consumo (kWh)</p><canvas id="chartPred4"></canvas><small>Pregunta predictiva: ¿Qué periodos tendrán mayor consumo?</small></div>
                    </div>
                </section>

                <section class="analysis-section report-section">
                    <h2>ANÁLISIS ESTADÍSTICO — VISUALIZACIONES CLAVE</h2>
                    <div class="chart-grid four-cols">
                        <div class="chart-box"><h3>1. Distribución de consumo por tipo de ambiente</h3><canvas id="chartPie"></canvas></div>
                        <div class="chart-box"><h3>2. Tendencia mensual de consumo energético</h3><canvas id="chartTrend"></canvas></div>
                        <div class="chart-box"><h3>3. Ranking de edificios por consumo</h3><canvas id="chartBars"></canvas></div>
                        <div class="chart-box"><h3>4. Histograma / frecuencia por hora</h3><canvas id="chartHist"></canvas></div>
                    </div>
                </section>

                <section class="analysis-section report-section">
                    <h2>ANÁLISIS OLAP Y CONTROL OPERATIVO</h2>
                    <div class="chart-grid four-cols">
                        <div class="chart-box"><h3>5. Demanda pico promedio por mes</h3><canvas id="chartDemand"></canvas></div>
                        <div class="chart-box"><h3>6. Factor de potencia promedio</h3><canvas id="chartFactor"></canvas></div>
                        <div class="chart-box"><h3>7. Ranking de ambientes por consumo</h3><canvas id="chartAmbientes"></canvas></div>
                        <div class="chart-box"><h3>8. Ranking de riesgo por edificio (%)</h3><canvas id="chartRiskRank"></canvas></div>
                    </div>
                    <div class="chart-grid two-cols extended-charts">
                        <div class="chart-box"><h3>9. Relación temperatura vs consumo</h3><canvas id="chartScatter"></canvas></div>
                        <div class="chart-box"><h3>10. Matriz hora-mes / consumo energético</h3><canvas id="chartHeatmap"></canvas></div>
                    </div>
                </section>

                <section class="infra-section report-section">
                    <h2>INFRAESTRUCTURA TECNOLÓGICA</h2>
                    <div class="infra-grid">
                        <div>▣<span>Medidores IoT y Sensores</span></div>
                        <div>☷<span>Integración ETL (Python)</span></div>
                        <div>▤<span>MySQL / Data Warehouse</span></div>
                        <div>▰<span>Big Data Storage</span></div>
                        <div>🐍<span>Machine Learning</span></div>
                        <div>▰<span>Power BI Dashboards</span></div>
                        <div>🔒<span>Seguridad / Roles</span></div>
                        <div>⏱<span>Monitoreo y Alertas</span></div>
                        <div>☁<span>Cloud / On-Premise</span></div>
                    </div>
                </section>

                <section class="table-section report-section">
                    <h2>DATA WAREHOUSE — TABLA DE HECHOS Y DIMENSIONES</h2>
                    <div class="table-responsive">
                        <table id="warehouseTable">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Año</th><th>Mes</th><th>Hora</th><th>Edificio</th><th>Ambiente</th><th>Tipo</th><th>Ocupación</th><th>Temp.</th><th>Demanda Pico</th><th>Factor Pot.</th><th>Consumo kWh</th><th>Eficiencia</th><th>Riesgo</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </section>
            </section>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="assets/js/graphs.js"></script>
</body>
</html>
