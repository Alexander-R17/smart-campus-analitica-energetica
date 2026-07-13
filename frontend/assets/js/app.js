/* =========================================================
   CONEXIÓN DEL FRONTEND CON EL BACKEND PHP DE RENDER
========================================================= */

const SMART_CAMPUS_API =
    "https://smart-campus-web-api.onrender.com";

const SMART_CAMPUS_BACKEND_ROUTES = new Set([
    "auth_login",
    "auth_logout",
    "upload_csv",
    "cloud_validate_staging",
    "cloud_run_etl",
    "cloud_status",
    "ml_colab",
    "ml_ia",
    "ia_process",
    "ml_status",
    "open_powerbi",
    "download_processed_csv",
    "looker_config",
    "streamlit_config",
    "web_event"
]);

const SMART_CAMPUS_ES_FIREBASE =
    window.location.hostname.endsWith(".web.app") ||
    window.location.hostname.endsWith(".firebaseapp.com") ||
    window.location.port === "5000";

if (
    SMART_CAMPUS_ES_FIREBASE &&
    !window.__smartCampusRenderConfigurado
) {
    window.__smartCampusRenderConfigurado = true;

    const smartCampusFetchOriginal =
        window.fetch.bind(window);

    window.fetch = function (recurso, opciones = {}) {
        let direccion = "";

        if (typeof recurso === "string") {
            direccion = recurso;
        } else if (recurso instanceof URL) {
            direccion = recurso.toString();
        } else if (recurso instanceof Request) {
            direccion = recurso.url;
        }

        let urlOriginal;

        try {
            urlOriginal = new URL(
                direccion || window.location.href,
                window.location.href
            );
        } catch (error) {
            return smartCampusFetchOriginal(
                recurso,
                opciones
            );
        }

        let ruta =
            urlOriginal.searchParams.get("route") ||
            urlOriginal.searchParams.get("action");

        if (
            !ruta &&
            opciones.body instanceof FormData
        ) {
            ruta =
                opciones.body.get("route") ||
                opciones.body.get("action");
        }

        if (
            !ruta &&
            opciones.body instanceof URLSearchParams
        ) {
            ruta =
                opciones.body.get("route") ||
                opciones.body.get("action");
        }

        if (
            !ruta ||
            !SMART_CAMPUS_RUTAS_BACKEND.has(
                String(ruta)
            )
        ) {
            return smartCampusFetchOriginal(
                recurso,
                opciones
            );
        }

        const urlRender = new URL(
            SMART_CAMPUS_API
        );

        urlOriginal.searchParams.forEach(
            (valor, clave) => {
                urlRender.searchParams.set(
                    clave,
                    valor
                );
            }
        );

        if (
            !urlRender.searchParams.has("route")
        ) {
            urlRender.searchParams.set(
                "route",
                String(ruta)
            );
        }

        console.log(
            "[Smart Campus] Backend Render:",
            urlRender.toString()
        );

        return smartCampusFetchOriginal(
            urlRender.toString(),
            opciones
        );
    };
}

/* =========================================================
   FIN DE CONEXIÓN CON RENDER
========================================================= */
const $ = (id) => document.getElementById(id);

let currentStep = 1;
let selectedFiles = [];
let uploadCompleted = false;
let iaProcesada = false;

const smartSessionId = sessionStorage.getItem('smart_session') || (crypto.randomUUID ? crypto.randomUUID() : String(Date.now()));
sessionStorage.setItem('smart_session', smartSessionId);

const cards = () => [...document.querySelectorAll('.process-card')];

window.addEventListener('DOMContentLoaded', () => {
    $('btnComenzar')?.addEventListener('click', () => {
        $('landingView').classList.add('hidden');
        $('loginView').classList.remove('hidden');
        registrarEventoSmartCampus('inicio_flujo', { etapa_numero: 0, etapa_nombre: 'Landing', resultado: 'inicio' });
    });

    $('btnLogin')?.addEventListener('click', login);
    $('loginPass')?.addEventListener('keydown', (e) => { if (e.key === 'Enter') login(); });
    $('btnSalir')?.addEventListener('click', salir);
    $('btnDatosActuales')?.addEventListener('click', verDatosActuales);
    $('btnContinuar')?.addEventListener('click', continuar);
    $('btnRetroceder')?.addEventListener('click', retroceder);
    $('modalClose')?.addEventListener('click', cerrarModal);
    $('modalOk')?.addEventListener('click', cerrarModal);

    const input = $('csvFiles');
    const drop = $('dropZone');

    input?.addEventListener('change', () => {
        selectedFiles = [...input.files].filter(file => file.name.toLowerCase().endsWith('.csv'));
        uploadCompleted = false;
        iaProcesada = false;
        renderFiles();
        registrarEventoSmartCampus('seleccion_archivo', { etapa_numero: 1, etapa_nombre: 'Fuentes de datos', resultado: `${selectedFiles.length} archivo(s)` });
    });

    drop?.addEventListener('click', (e) => {
        if (e.target.tagName !== 'INPUT') input.click();
    });
    drop?.addEventListener('dragover', (e) => {
        e.preventDefault();
        drop.classList.add('dragover');
    });
    drop?.addEventListener('dragleave', () => drop.classList.remove('dragover'));
    drop?.addEventListener('drop', (e) => {
        e.preventDefault();
        drop.classList.remove('dragover');
        selectedFiles = [...e.dataTransfer.files].filter(file => file.name.toLowerCase().endsWith('.csv'));
        input.value = '';
        uploadCompleted = false;
        iaProcesada = false;
        renderFiles();
    });

    setStep(1);
});

async function login() {
    const username = $('loginUser').value.trim();
    const password = $('loginPass').value;

    const mensaje = $('loginMsg');
    const boton = $('btnLogin');

    mensaje.textContent = '';
    mensaje.classList.add('hidden');

    if (!username || !password) {
        mensaje.textContent =
            'Ingresa tu usuario y contraseña.';

        mensaje.classList.remove('hidden');
        return;
    }

    boton.disabled = true;
    boton.textContent = 'Validando...';

    try {
        const resultado = await fetchJson(
            'index.php?route=auth_login',
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    password: password
                })
            }
        );

        localStorage.setItem(
            'smartCampusUsuario',
            JSON.stringify(resultado.usuario)
        );

        localStorage.setItem(
            'smartCampusSesionId',
            String(resultado.sesion_id)
        );

        window.smartCampusUsuarioActual =
            resultado.usuario;

        window.smartCampusSesionId =
            resultado.sesion_id;

        $('loginView').classList.add('hidden');
        $('appView').classList.remove('hidden');

        registrarEventoSmartCampus(
            'login_usuario',
            {
                etapa_numero: 0,
                etapa_nombre: 'Login',
                resultado: 'exitoso',
                usuario_id: resultado.usuario.id,
                username: resultado.usuario.username,
                nombre_usuario:
                    resultado.usuario.nombre_completo,
                rol: resultado.usuario.rol,
                sesion_id: resultado.sesion_id
            }
        );

        showToast(
            'Acceso correcto',
            `Bienvenido, ${resultado.usuario.nombre_completo}.`,
            'success'
        );
    } catch (error) {
        mensaje.textContent =
            error.message ||
            'No se pudo iniciar sesión.';

        mensaje.classList.remove('hidden');

        showToast(
            'Acceso denegado',
            mensaje.textContent,
            'error'
        );
    } finally {
        boton.disabled = false;

        boton.innerHTML =
            '<span class="btn-icon">🔐</span> Ingresar';
    }
}

function salir() {
    $('appView').classList.add('hidden');
    $('loginView').classList.remove('hidden');
    registrarEventoSmartCampus('salida_usuario', { etapa_numero: currentStep, etapa_nombre: `Paso ${currentStep}`, resultado: currentStep >= 7 ? 'completado' : 'incompleto' });
    showToast('Sesión cerrada', 'Regresaste al inicio de sesión.', 'warning');
}

function renderFiles() {
    const list = $('fileList');
    list.innerHTML = '';

    selectedFiles.forEach((file, index) => {
        const li = document.createElement('li');
        li.innerHTML = `<b>${index + 1}.</b> ${file.name} <span>${(file.size / 1024).toFixed(1)} KB</span>`;
        list.appendChild(li);
    });

    $('uploadText').textContent = selectedFiles.length
        ? `${selectedFiles.length} archivo(s) listo(s). Presiona Continuar para cargar a la nube.`
        : 'Esperando selección de archivos.';
}

function setStep(step) {
    currentStep = step;

    cards().forEach(card => {
        const n = Number(card.dataset.step);
        card.classList.remove('active', 'done', 'processing');
        if (n < step) card.classList.add('done');
        if (n === step) card.classList.add('active');
    });

    $('btnContinuar').textContent = step === 7 ? 'Graficar' : 'Continuar';
}

async function continuar() {
    try {
        disableButtons(true);

        registrarEventoSmartCampus('click_continuar', { etapa_numero: currentStep, etapa_nombre: `Paso ${currentStep}`, resultado: 'click' });

        if (currentStep === 1) {
            if (!selectedFiles.length) {
                showToast('Falta CSV', 'Primero selecciona uno o más archivos CSV.', 'warning');
                return;
            }

            $('uploadText').textContent = 'Subiendo fuentes a Supabase PostgreSQL...';
            const result = await uploadFiles();

            if (!result.ok) {
                showToast('Error al subir CSV', result.error || result.message || 'No se pudo cargar a la nube.', 'error');
                $('uploadText').textContent = 'Error durante la carga Supabase.';
                return;
            }

            uploadCompleted = true;

if (!result.batch_id) {
    throw new Error(
        'El servidor cargó el CSV, pero no devolvió el identificador del lote.'
    );
}

localStorage.setItem(
    'smartCampusBatchId',
    String(result.batch_id)
);

console.log(
    'Lote Smart Campus guardado:',
    result.batch_id
);

$('uploadText').textContent =
    `Lote Supabase #${result.batch_id}. Registros cargados: ${result.registros_estimados}.`;
//
            setStep(2);
            await animateStep('step2', 'stagingProgress', 'stagingText', [
                'Recibiendo datos crudos en Supabase Landing Zone...',
                'Identificando origen de datos y estructura CSV...',
                'Validando columnas obligatorias y tipos de dato...',
                'Registrando lote en staging Supabase...',
                'Staging Supabase listo para validación.'
            ], 1400);
            return;
        }

        if (currentStep === 2) {
            $('stagingText').textContent = 'Validando origen de datos en staging Supabase...';
            const batchIdActual =
    localStorage.getItem('smartCampusBatchId');

if (!batchIdActual) {
    throw new Error(
        'No se encontró el lote activo. Vuelve al paso 1 y carga el CSV.'
    );
}

const validation = await fetchJson(
    `index.php?route=cloud_validate_staging&batch_id=${encodeURIComponent(batchIdActual)}`,
    {
        method: 'POST'
    }
);
//
            const registros = validation.detalle?.registros ?? 'verificados';
            registrarEventoSmartCampus('validacion_staging', { etapa_numero: 2, etapa_nombre: 'Staging Area', resultado: 'exitoso' });
            showToast('Origen validado', `Staging Supabase validó ${registros} registros.`, 'success');

            setStep(3);
            await animateStep('step3', 'etlProgress', 'etlText', [
                'EXTRACT: leyendo datos desde staging Supabase...',
                'TRANSFORM: normalizando tiempo, edificio, ambiente y ocupación...',
                'TRANSFORM: calculando campos para KPIs energéticos...',
                'LOAD: preparando carga al modelo copo de nieve Supabase...',
                'Proceso ETL Supabase listo para ejecutar.'
            ], 1600);
            return;
        }

        if (currentStep === 3) {
            $('etlText').textContent = 'Ejecutando ETL y carga al Data Warehouse Supabase...';
            const batchIdActual =
    localStorage.getItem('smartCampusBatchId');

if (!batchIdActual) {
    throw new Error(
        'No se encontró el lote necesario para ejecutar el ETL.'
    );
}

const etl = await fetchJson(
    `index.php?route=cloud_run_etl&batch_id=${encodeURIComponent(batchIdActual)}`,
    {
        method: 'POST'
    }
); //
            const factRows = etl.warehouse?.counts?.fact ?? 'cargados';
            registrarEventoSmartCampus('etl_completado', { etapa_numero: 3, etapa_nombre: 'Proceso ETL', resultado: 'exitoso' });
            showToast('ETL Supabase completado', `Tabla de hechos actualizada con ${factRows} registros.`, 'success');

            setStep(4);
            await animateWarehouse();
            return;
        }

        if (currentStep === 4) {
           const batchIdActual =
    localStorage.getItem('smartCampusBatchId');

if (!batchIdActual) {
    throw new Error(
        'No se encontró el lote para consultar el Data Warehouse.'
    );
}

const status = await fetchJson(
    `index.php?route=cloud_status&batch_id=${encodeURIComponent(batchIdActual)}`
);//
            const counts = status.warehouse?.counts || {};
            $('dwText').textContent = `Data Warehouse Supabase listo: hechos ${counts.fact ?? 0}, ambientes ${counts.dim_ambiente ?? 0}, edificios ${counts.dim_edificio ?? 0}.`;
            registrarEventoSmartCampus('modelo_copo_nieve_completado', { etapa_numero: 4, etapa_nombre: 'Data Warehouse', resultado: 'exitoso' });
            showToast('Copo de nieve Supabase listo', 'Colab puede consumir los datos desde la nube.', 'success');

            setStep(5);
            await ejecutarCapaIAColab();
            return;
        }

        if (currentStep === 5) {
            setStep(6);
            await animateStep('step6', 'semanticProgress', 'semanticText', [
                'Recibiendo resultados IA desde Colab...',
                'Guardando predicciones en tabla Supabase fact_consumo_energetico_pred...',
                'Guardando dataset semántico en Supabase...',
                'Actualizando métricas KPI para Streamlit Community Cloud...',
                'Capa semántica Supabase completada.'
            ], 1400);
            return;
        }

        if (currentStep === 6) {
            setStep(7);
            await animateStep('step7', 'biProgress', 'biText', [
                'Preparando visualizaciones BI en nube...',
                'Verificando URL pública de Streamlit Community Cloud...',
                'Habilitando KPIs, filtros OLAP y analítica web...',
                'Streamlit listo para graficar desde la nube.'
            ], 1200);
            return;
        }

        if (currentStep === 7) {
            await abrirDashboardStreamlit();
        }
    } catch (error) {
        console.error(error);
        showToast('Error del proceso', error.message || 'Revisa configuración Supabase, Colab y Supabase.', 'error');
    } finally {
        disableButtons(false);
    }
}

function retroceder() {
    if (currentStep > 1) {
        setStep(currentStep - 1);
        showToast('Proceso retrocedido', `Ahora estás en el paso ${currentStep}.`, 'warning');
    }
}

function disableButtons(disabled) {
    $('btnContinuar').disabled = disabled;
    $('btnRetroceder').disabled = disabled;
}

async function uploadFiles() {
    const formData = new FormData();
    if (selectedFiles.length === 1) {
        formData.append('csv', selectedFiles[0]);
    } else {
        selectedFiles.forEach(file => formData.append('csv_files[]', file));
    }

    const response = await fetch('index.php?route=upload_csv', {
        method: 'POST',
        body: formData
    });

    return await response.json();
}

async function fetchJson(url, options = {}) {
    const response = await fetch(url, options);
    const text = await response.text();

    let data;

    try {
        data = JSON.parse(text);
    } catch (error) {
        console.error('Respuesta no JSON recibida desde:', url);
        console.error(text);

        throw new Error(
            'El servidor devolvió HTML en vez de JSON. Revisa la ruta: ' + url
        );
    }

    if (!response.ok || data.ok === false) {
        throw new Error(data.error || data.message || 'Error desconocido del servidor.');
    }

    return data;
}

async function ejecutarCapaIAColab() {
    registrarEventoSmartCampus('render_ia_iniciado', { etapa_numero: 5, etapa_nombre: 'Capa IA', resultado: 'iniciado' });

    $('iaText').textContent = 'Conectando con Render IA mediante API Supabase...';
    $('semanticText').textContent = 'Esperando resultados de IA desde Data Warehouse Supabase...';

    await animateStep('step5', 'iaProgress', 'iaText', [
        'Conectando con Render IA...',
        'Leyendo dataset desde Data Warehouse Supabase...',
        'Ejecutando modelo Random Forest y clasificación de riesgo...',
        'Generando predicciones energéticas...',
        'Guardando predicciones en Supabase para Streamlit...'
    ], 1800, false);

    const response = await fetch('index.php?route=ml_colab', { method: 'POST' });
    const text = await response.text();

    let data;
    try {
        data = JSON.parse(text);
    } catch (error) {
        console.error('Respuesta no JSON desde IA:', text);
        $('iaText').textContent = 'Error: el servidor devolvió una respuesta inválida.';
        throw new Error('Respuesta inválida del servidor IA.');
    }

    if (!data.ok) {
        $('iaText').textContent = 'Error al ejecutar Render IA.';
        throw new Error(data.error || 'No se pudo ejecutar Render IA.');
    }

    iaProcesada = true;
    $('iaProgress').style.width = '100%';
    $('iaText').textContent = 'Capa IA completada desde Render.';
    $('semanticText').textContent = 'Resultados disponibles para capa semántica.';

    const registrosIA = data.registros_insertados ?? data.registros_predichos ?? data.registros_predicciones ?? data.rows_cloud_pred ?? 0;
    const registrosLeidos = data.registros_leidos ?? registrosIA;
    const proveedorIA = data.ai_provider ?? 'Render Flask API';

    showProcessModal(
        '✅',
        'Render IA procesó la Capa IA Supabase',
        `La IA (${proveedorIA}) leyó ${registrosLeidos} registros desde el Data Warehouse Supabase y guardó ${registrosIA} predicciones en la tabla predictiva. Ahora puedes continuar con la Capa Semántica.`
    );

    registrarEventoSmartCampus('render_ia_completado', { etapa_numero: 5, etapa_nombre: 'Capa IA', resultado: 'exitoso' });
    showToast('IA Render completada', `Se guardaron ${registrosIA} predicciones en Supabase.`, 'success');
}

async function animateWarehouse() {
    $('dwText').textContent = 'Visualizando modelo copo de nieve Supabase con dimensiones y hechos...';
    const card = $('step4');
    card.classList.add('processing');
    await wait(350);

    const dims = card.querySelectorAll('.dim, .fact');
    dims.forEach((d, i) => setTimeout(() => d.classList.add('filled'), i * 160));

    await wait(1250);
    $('dwText').textContent = 'Data Warehouse Supabase consolidado. Dimensiones y tabla de hechos preparadas en la nube.';
    card.classList.remove('processing');
    showToast('Data Warehouse Supabase listo', 'Modelo copo de nieve preparado para IA.', 'success');
}

function animateStep(cardId, barId, textId, messages, duration, finish = true) {
    return new Promise(resolve => {
        const card = $(cardId);
        const bar = $(barId);
        const text = $(textId);
        let progress = 0;
        let msgIndex = 0;

        card.classList.add('processing');
        if (bar) bar.style.width = '0%';
        if (text) text.textContent = messages[0];

        const interval = setInterval(() => {
            progress += 5;
            if (bar) bar.style.width = Math.min(progress, 96) + '%';
            const next = Math.min(messages.length - 1, Math.floor((progress / 100) * messages.length));
            if (next !== msgIndex) {
                msgIndex = next;
                if (text) text.textContent = messages[msgIndex];
            }
            if (progress >= 100) {
                clearInterval(interval);
                if (finish && bar) bar.style.width = '100%';
                setTimeout(() => {
                    card.classList.remove('processing');
                    resolve();
                }, 180);
            }
        }, duration / 20);
    });
}

async function abrirDashboardStreamlit() {
    registrarEventoSmartCampus('visualizacion_dashboard', {
        etapa_numero: 7,
        etapa_nombre: 'Dashboard Streamlit Community Cloud',
        resultado: 'completado'
    });

    const response = await fetch('index.php?route=streamlit_config');
    const data = await response.json();

    if (!data.ok || !data.url) {
        showToast('Falta configurar Streamlit', data.message || 'Pega la URL de Streamlit en ExternalServices.php.', 'error');
        return;
    }

    window.open(data.url, '_blank', 'noopener,noreferrer');
    showToast('Dashboard Streamlit abierto', 'El dashboard cloud se abre en una nueva pestaña.', 'success');
}

async function verDatosActuales() {
    registrarEventoSmartCampus('ver_datos_actuales', {
        etapa_numero: currentStep,
        etapa_nombre: 'Consulta datos actuales',
        resultado: 'consulta_directa'
    });
    showToast('Abriendo datos actuales', 'Se abrirá Streamlit conectado a Supabase en nube.', 'warning');
    await abrirDashboardStreamlit();
}

function detectarDispositivo() {
    if (/Tablet|iPad/i.test(navigator.userAgent)) return 'Tablet';
    if (/Mobi|Android|iPhone/i.test(navigator.userAgent)) return 'Mobile';
    return 'Desktop';
}

function registrarEventoSmartCampus(nombreEvento, datos = {}) {
    const payload = {
        evento: nombreEvento,
        usuario_id: localStorage.getItem('smart_user') || 'usuario_demo',
        sesion_id: smartSessionId,
        etapa_numero: datos.etapa_numero ?? currentStep ?? 0,
        etapa_nombre: datos.etapa_nombre || `Paso ${currentStep}`,
        resultado: datos.resultado || 'exitoso',
        dispositivo: detectarDispositivo(),
        navegador: navigator.userAgent,
        tiempo_seg: Math.round(performance.now() / 1000),
        url_pagina: window.location.href
    };

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ event: nombreEvento, ...payload });

    fetch('index.php?route=web_event', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    }).catch(() => null);
}

window.addEventListener('beforeunload', () => {
    if (currentStep < 7) {
        const payload = {
            evento: 'abandono_flujo',
            usuario_id: localStorage.getItem('smart_user') || 'usuario_demo',
            sesion_id: smartSessionId,
            etapa_numero: currentStep,
            etapa_nombre: `Paso ${currentStep}`,
            resultado: 'incompleto',
            dispositivo: detectarDispositivo(),
            navegador: navigator.userAgent,
            tiempo_seg: Math.round(performance.now() / 1000),
            url_pagina: window.location.href
        };
        navigator.sendBeacon?.('index.php?route=web_event', new Blob([JSON.stringify(payload)], { type: 'application/json' }));
    }
});

function showToast(title, message, type = 'success') {
    const area = $('toastArea');
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<b>${title}</b><span>${message}</span>`;
    area.appendChild(el);
    setTimeout(() => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(10px)';
        setTimeout(() => el.remove(), 250);
    }, 4200);
}

function showProcessModal(icon, title, text) {
    $('modalIcon').textContent = icon;
    $('modalTitle').textContent = title;
    $('modalText').textContent = text;
    $('modalProceso').classList.remove('hidden');
}

function cerrarModal() {
    $('modalProceso').classList.add('hidden');
}

function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

