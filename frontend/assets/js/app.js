const $ = (id) => document.getElementById(id);

let currentStep = 1;
let selectedFiles = [];
let uploadCompleted = false;
let iaProcesada = false;

const cards = () => [...document.querySelectorAll('.process-card')];

window.addEventListener('DOMContentLoaded', () => {
    $('btnComenzar')?.addEventListener('click', () => {
        $('landingView').classList.add('hidden');
        $('loginView').classList.remove('hidden');
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

function login() {
    const user = $('loginUser').value.trim();
    const pass = $('loginPass').value.trim();
    const msg = $('loginMsg');

    if (user === 'Ingeniero1' && pass === '12345678') {
        msg.classList.add('hidden');
        $('loginView').classList.add('hidden');
        $('appView').classList.remove('hidden');
        showToast('Acceso correcto', 'Bienvenido al flujo Smart Campus.', 'success');
    } else {
        msg.textContent = 'Usuario o contraseña incorrectos.';
        msg.classList.remove('hidden');
        showToast('Credenciales inválidas', 'Revisa usuario y contraseña.', 'error');
    }
}

function salir() {
    $('appView').classList.add('hidden');
    $('loginView').classList.remove('hidden');
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
        ? `${selectedFiles.length} archivo(s) listo(s). Presiona Continuar para cargar al sistema.`
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

        if (currentStep === 1) {
            if (!selectedFiles.length) {
                showToast('Falta CSV', 'Primero selecciona uno o más archivos CSV.', 'warning');
                return;
            }

            $('uploadText').textContent = 'Subiendo fuentes al servidor...';
            const result = await uploadFiles();

            if (!result.ok) {
                showToast('Error al subir CSV', result.error || result.message || 'No se pudo importar el CSV.', 'error');
                $('uploadText').textContent = 'Error durante la carga del CSV.';
                return;
            }

            uploadCompleted = true;
            $('uploadText').textContent = `Carga recibida. Registros estimados: ${result.registros_estimados}.`;
            showToast('CSV cargado', `Archivo preparado con ${result.registros_estimados} registros.`, 'success');

            setStep(2);
            await animateStep('step2', 'stagingProgress', 'stagingText', [
                'Recibiendo datos crudos energéticos...',
                'Validando estructura del CSV...',
                'Integrando sensores, ocupación y datos ambientales...',
                'Generando control de calidad y logs de carga...',
                'Staging Area completado.'
            ], 1400);
            return;
        }

        if (currentStep === 2) {
            setStep(3);
            await animateStep('step3', 'etlProgress', 'etlText', [
                'EXTRACT: lectura de consumo, ocupación y demanda...',
                'TRANSFORM: normalización y limpieza...',
                'TRANSFORM: cálculo preliminar de KPIs...',
                'LOAD: preparando carga al Data Warehouse...',
                'Proceso ETL completado.'
            ], 1600);
            return;
        }

        if (currentStep === 3) {
            setStep(4);
            await animateWarehouse();
            return;
        }

        if (currentStep === 4) {
            setStep(5);
            await ejecutarCapaIAColab();
            return;
        }

        if (currentStep === 5) {
            setStep(6);
            await animateStep('step6', 'semanticProgress', 'semanticText', [
                'Recibiendo resultados del motor Python ML...',
                'Construyendo modelo semántico...',
                'Generando medidas KPI y reglas de negocio...',
                'Clasificando riesgo energético...',
                'Capa semántica completada.'
            ], 1400);
            return;
        }

        if (currentStep === 6) {
            setStep(7);
            await animateStep('step7', 'biProgress', 'biText', [
                'Preparando visualizaciones BI...',
                'Conectando CSV procesado con Power BI...',
                'Habilitando KPIs, filtros OLAP y reportes...',
                'Power BI listo para graficar.'
            ], 1200);
            return;
        }

        if (currentStep === 7) {
            await abrirPowerBI();
        }
    } catch (error) {
        console.error(error);
        showToast('Error del proceso', error.message || 'Revisa Apache, MySQL y Colab.', 'error');
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

async function ejecutarCapaIAColab() {
    $('iaText').textContent = 'Conectando con Google Colab mediante API ngrok...';
    $('semanticText').textContent = 'Esperando resultados de IA para KPIs semánticos...';

    await animateStep('step5', 'iaProgress', 'iaText', [
        'Conectando con Google Colab...',
        'Enviando CSV consolidado al motor Python...',
        'Ejecutando Random Forest y clasificación de riesgo...',
        'Generando predicciones energéticas...',
        'Recibiendo CSV procesado para Power BI y MySQL...'
    ], 1800, false);

    const response = await fetch('index.php?route=ml_colab', { method: 'POST' });
    const data = await response.json();

    if (!data.ok) {
        $('iaText').textContent = 'Error al ejecutar Google Colab.';
        throw new Error(data.error || 'No se pudo ejecutar Google Colab.');
    }

    iaProcesada = true;
    $('iaProgress').style.width = '100%';
    $('iaText').textContent = 'Capa IA completada desde Google Colab.';
    $('semanticText').textContent = 'Resultados disponibles para capa semántica.';

    const rows = data.rows_mysql || 0;
    const resumen = data.resumen || {};
    const pred = resumen.prediccion_total_kwh ? Number(resumen.prediccion_total_kwh).toLocaleString('es-PE') : 'calculada';

    showProcessModal(
        '✅',
        'Google Colab procesó la Capa IA',
        `Se generó el CSV predictivo, se guardaron ${rows} registros en MySQL y la predicción total quedó ${pred} kWh. Ahora puedes continuar con la Capa Semántica.`
    );
    showToast('IA completada', 'Colab devolvió el CSV procesado para Power BI.', 'success');
}

async function animateWarehouse() {
    $('dwText').textContent = 'Construyendo modelo copo de nieve con dimensiones y hechos...';
    const card = $('step4');
    card.classList.add('processing');
    await wait(350);

    const dims = card.querySelectorAll('.dim, .fact');
    dims.forEach((d, i) => setTimeout(() => d.classList.add('filled'), i * 160));

    await wait(1250);
    $('dwText').textContent = 'Data Warehouse consolidado. Dimensiones y tabla de hechos preparadas.';
    card.classList.remove('processing');
    showToast('Data Warehouse listo', 'Modelo copo de nieve preparado para IA.', 'success');
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

async function abrirPowerBI() {
    const response = await fetch('index.php?route=open_powerbi');
    const data = await response.json();

    if (!data.ok) {
        showToast('Power BI no abrió', data.error || 'Coloca el .pbix en powerbi/report/.', 'error');
        return;
    }

    showToast('Power BI solicitado', 'Si no abre automáticamente, abre manualmente el archivo .pbix.', 'success');
}

async function verDatosActuales() {
    showToast('Abriendo datos actuales', 'Se solicitará Power BI sin volver a subir CSV.', 'warning');
    await abrirPowerBI();
}

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
