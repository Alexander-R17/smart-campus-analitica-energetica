<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proceso ETL | Smart Campus</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="dashboard-body">
    <header class="topbar compact-topbar">
        <div class="brand-left">
            <img src="assets/img/logo_UNuevaEsperanza.png" alt="Logo Universidad Nueva Esperanza">
            <div>
                <h1>PLATAFORMA INTEGRAL DE ANALÍTICA ENERGÉTICA - SMART CAMPUS UNIVERSIDAD NUEVA ESPERANZA</h1>
                <p>Actividad a presentar: prototipo web con BI, Big Data e IA para optimización energética</p>
            </div>
        </div>
        <div class="top-actions">
            <a href="index.php?route=graphs" class="btn-ghost btn-current-data"><span class="btn-icon">📊</span> Ver datos actuales</a>
            <a href="index.php?route=logout" class="btn-ghost"><span class="btn-icon">⏻</span> Salir</a>
        </div>
    </header>

    <main class="process-main">
        <section class="process-title">
            <h2>FLUJO LINEAL DEL SISTEMA — DEL 1 AL 7</h2>
            <p>Sube las fuentes de datos, confirma cada etapa con <b>Continuar</b> y al llegar a Visualización BI usa <b>Graficar</b> para ver KPIs, predicciones y reportes. Si la base de datos ya tiene información cargada, usa <b>Ver datos actuales</b> para abrir las gráficas sin volver a subir archivos ni duplicar registros.</p>
        </section>

        <section class="pipeline-grid pipeline-grid-top">
            <article class="pipeline-card process-card active" data-step="1" id="step1">
                <div class="step-title blue"><span>1</span> FUENTES DE DATOS</div>
                <ul class="icon-list compact-list">
                    <li><span class="mini-icon">▣</span>Medidores inteligentes IoT</li>
                    <li><span class="mini-icon">⌁</span>Sensores de ocupación</li>
                    <li><span class="mini-icon">◒</span>Sistema académico</li>
                    <li><span class="mini-icon">♨</span>Sensores ambientales</li>
                    <li><span class="mini-icon">▤</span>Historial energético Excel / CSV</li>
                    <li><span class="mini-icon">☁</span>API clima y temperatura</li>
                    <li><span class="mini-icon">⚒</span>Registros de mantenimiento</li>
                </ul>
                <div class="upload-zone" id="dropZone">
                    <img src="assets/img/icon_iot.png" alt="IoT">
                    <strong>Agregar archivos CSV</strong>
                    <small>Puede subir 1 o más fuentes normalizadas</small>
                    <input type="file" id="csvFiles" accept=".csv" multiple>
                </div>
                <ul id="fileList" class="file-list"></ul>
                <p class="process-text" id="uploadText">Esperando selección de archivos.</p>
            </article>

            <article class="pipeline-card process-card" data-step="2" id="step2">
                <div class="step-title green"><span>2</span> STAGING AREA<br><small>(LANDING ZONE)</small></div>
                <img src="assets/img/icon_staging.png" class="step-icon" alt="Staging Area">
                <div class="process-box">Datos crudos energéticos</div>
                <div class="process-box">Validación inicial</div>
                <div class="process-box">Integración de sensores</div>
                <div class="process-box">Control de calidad</div>
                <div class="process-box">Logs de carga</div>
                <div class="progress"><div id="stagingProgress"></div></div>
                <p id="stagingText" class="process-text">Esperando carga desde fuentes.</p>
            </article>

            <article class="pipeline-card process-card" data-step="3" id="step3">
                <div class="step-title darkgreen"><span>3</span> PROCESO ETL</div>
                <img src="assets/img/icon_etl.png" class="step-icon" alt="Proceso ETL">
                <div class="etl-group"><h4>EXTRACT</h4><p>Extracción de sensores · Captura de consumo · Lectura de ocupación</p></div>
                <div class="etl-group"><h4>TRANSFORM</h4><p>Limpieza de datos · Normalización · Deducción · Conversión de variables · Cálculo de KPIs · Integración energética</p></div>
                <div class="etl-group"><h4>LOAD</h4><p>Carga al Data Warehouse · Consolidación histórica</p></div>
                <div class="progress"><div id="etlProgress"></div></div>
                <p id="etlText" class="process-text">Esperando confirmación de staging.</p>
            </article>

            <article class="pipeline-card process-card warehouse-card" data-step="4" id="step4">
                <div class="step-title purple"><span>4</span> DATA WAREHOUSE<br><small>MODELO COPO DE NIEVE</small></div>
                <img src="assets/img/icon_dw.png" class="step-icon" alt="Data Warehouse">
                <div class="snowflake-model mini-snowflake">
                    <div class="dim dim-tiempo"><b>DimTiempo</b><span>día · mes · año · hora</span></div>
                    <div class="dim dim-edificio"><b>DimEdificio</b><span>nombre · ubicación · tipo</span></div>
                    <div class="fact"><b>FactConsumoEnergético</b><span>consumo · demanda pico · eficiencia · riesgo</span></div>
                    <div class="dim dim-ambiente"><b>DimAmbiente</b><span>aula · laboratorio · oficina</span></div>
                    <div class="dim dim-ocupacion"><b>DimOcupación</b><span>personas · porcentaje</span></div>
                    <div class="dim dim-medidor"><b>DimMedidor</b><span>tipo · estado · código</span></div>
                </div>
                <p id="dwText" class="process-text">Sin datos consolidados todavía.</p>
            </article>
        </section>

        <section class="pipeline-grid pipeline-grid-bottom">
            <article class="pipeline-card process-card" data-step="5" id="step5">
                <div class="step-title orange"><span>5</span> CAPA IA<br><small>(PREDICCIONES)</small></div>
                <div class="python-badge">PYTHON ML ENGINE</div>
                <div class="ai-icon">🐍</div>
                <ul class="feature-list">
                    <li>Random Forest</li>
                    <li>Árbol de Decisión</li>
                    <li>Clasificación energética</li>
                    <li>Regresión predictiva</li>
                    <li>Predicción de sobreconsumo</li>
                    <li>Detección de anomalías</li>
                </ul>
                <div class="prediction-box">
                    <b>PREDICCIONES</b>
                    <span>Consumo energético futuro</span>
                    <span>Riesgo de sobreconsumo</span>
                    <span>Demanda de ambientes</span>
                    <span>Periodos de alto consumo</span>
                </div>
                <div class="progress"><div id="iaProgress"></div></div>
                <p id="iaText" class="process-text">Esperando Data Warehouse.</p>
            </article>

            <article class="pipeline-card process-card" data-step="6" id="step6">
                <div class="step-title gold"><span>6</span> CAPA SEMÁNTICA<br><small>(KPIs)</small></div>
                <div class="cube-icon">◇</div>
                <h4>MODELO SEMÁNTICO</h4>
                <div class="semantic-box"><b>MEDIDAS KPI</b><span>Consumo Total (kWh)</span><span>Pico Demanda (kW)</span><span>Eficiencia Energética (%)</span><span>Riesgo Energético (Índice)</span></div>
                <div class="semantic-box"><b>REGLAS DE NEGOCIO</b><span>Alertas automáticas</span><span>Semáforo energético</span><span>Clasificación de riesgo</span></div>
                <div class="semantic-box"><b>CATÁLOGO SEMÁNTICO</b><span>Métricas · Dimensiones · Indicadores</span></div>
                <div class="progress"><div id="semanticProgress"></div></div>
                <p id="semanticText" class="process-text">Esperando capa IA.</p>
            </article>

            <article class="pipeline-card process-card" data-step="7" id="step7">
                <div class="step-title blue"><span>7</span> VISUALIZACIÓN BI<br><small>(POWER BI)</small></div>
                <div class="powerbi-logo">▰ Power BI</div>
                <div class="screen-mock">
                    <div class="screen-bars"><span></span><span></span><span></span><span></span></div>
                    <div class="screen-lines"><i></i><i></i><i></i></div>
                </div>
                <ul class="check-list">
                    <li>Dashboards ejecutivos</li>
                    <li>KPIs en tiempo real</li>
                    <li>Reportes energéticos</li>
                    <li>Filtros interactivos</li>
                    <li>Análisis predictivo</li>
                    <li>Alertas automáticas</li>
                    <li>Exportación de reportes</li>
                </ul>
                <div class="progress"><div id="biProgress"></div></div>
                <p id="biText" class="process-text">Esperando capa semántica.</p>
            </article>
        </section>

        <div class="process-actions">
            <button id="btnContinuar" class="btn-primary">Continuar</button>
            <button id="btnRetroceder" class="btn-secondary">Retroceder</button>
        </div>
    </main>

    <script src="assets/js/dashboard.js"></script>
</body>
</html>
