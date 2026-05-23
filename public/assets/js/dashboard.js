const $ = (id) => document.getElementById(id);
let currentStep = 1;
let selectedFiles = [];
let uploadCompleted = false;
let warehouseReady = false;

const cards = [...document.querySelectorAll('.process-card')];
const btnContinuar = $('btnContinuar');
const btnRetroceder = $('btnRetroceder');
const csvInput = $('csvFiles');
const fileList = $('fileList');
const dropZone = $('dropZone');

window.addEventListener('DOMContentLoaded', () => {
    setStep(1);
    csvInput.addEventListener('change', handleFiles);
    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragover');
        selectedFiles = [...e.dataTransfer.files].filter(f => f.name.toLowerCase().endsWith('.csv'));
        renderFiles();
    });
    btnContinuar.addEventListener('click', continueProcess);
    btnRetroceder.addEventListener('click', resetAndGoBack);
});

function handleFiles() {
    selectedFiles = [...csvInput.files];
    uploadCompleted = false;
    warehouseReady = false;
    renderFiles();
}

function renderFiles() {
    fileList.innerHTML = '';
    selectedFiles.forEach((file, index) => {
        const li = document.createElement('li');
        li.innerHTML = `<b>${index + 1}.</b> ${file.name} <span>${(file.size / 1024).toFixed(1)} KB</span>`;
        fileList.appendChild(li);
    });
    $('uploadText').textContent = selectedFiles.length
        ? `${selectedFiles.length} archivo(s) listo(s). Presiona Continuar para cargar al sistema.`
        : 'Esperando selección de archivos.';
}

function setStep(step) {
    currentStep = step;
    cards.forEach(card => {
        const cardStep = Number(card.dataset.step);
        card.classList.remove('active', 'done', 'processing');
        if (cardStep < step) card.classList.add('done');
        if (cardStep === step) card.classList.add('active');
    });
    btnContinuar.textContent = step === 7 ? 'Graficar' : 'Continuar';
}

async function continueProcess() {
    try {
        btnContinuar.disabled = true;
        btnRetroceder.disabled = true;

        if (currentStep === 1) {
            if (!selectedFiles.length) {
                alert('Primero debe cargar al menos un archivo CSV en Fuentes de Datos.');
                return;
            }
            $('uploadText').textContent = 'Subiendo fuentes al servidor y preparando carga...';
            const result = await uploadFiles();
            if (!result.ok) {
                alert(result.message || 'No se pudo importar el CSV.');
                return;
            }
            uploadCompleted = true;
            $('uploadText').textContent = `Carga recibida. Registros insertados: ${result.inserted}.`;
            setStep(2);
            await animateStep('step2', 'stagingProgress', 'stagingText', [
                'Recibiendo datos crudos energéticos...',
                'Validando estructura de columnas del CSV...',
                'Integrando sensores, ocupación y datos ambientales...',
                'Generando control de calidad y logs de carga...',
                'Staging Area completado.'
            ], 1600);
            return;
        }

        if (currentStep === 2) {
            setStep(3);
            await animateStep('step3', 'etlProgress', 'etlText', [
                'EXTRACT: leyendo consumo, ocupación, demanda y temperatura...',
                'TRANSFORM: normalizando valores numéricos y tipos de ambiente...',
                'TRANSFORM: calculando eficiencia energética y riesgo...',
                'LOAD: cargando dimensiones y tabla de hechos...',
                'Proceso ETL completado.'
            ], 1800);
            return;
        }

        if (currentStep === 3) {
            setStep(4);
            await animateWarehouse();
            return;
        }

        if (currentStep === 4) {
            setStep(5);
            await animateStep('step5', 'iaProgress', 'iaText', [
                'Inicializando motor Python ML Engine...',
                'Entrenando reglas de clasificación energética...',
                'Calculando regresión predictiva de consumo...',
                'Detectando anomalías y riesgo de sobreconsumo...',
                'Capa IA completada.'
            ], 1800);
            return;
        }

        if (currentStep === 5) {
            setStep(6);
            await animateStep('step6', 'semanticProgress', 'semanticText', [
                'Construyendo modelo semántico...',
                'Generando medidas KPI: consumo, pico, eficiencia y riesgo...',
                'Aplicando reglas de negocio y semáforo energético...',
                'Catalogando métricas, dimensiones e indicadores...',
                'Capa semántica completada.'
            ], 1700);
            return;
        }

        if (currentStep === 6) {
            setStep(7);
            await animateStep('step7', 'biProgress', 'biText', [
                'Preparando visualizaciones BI...',
                'Conectando KPIs con dashboard ejecutivo...',
                'Habilitando filtros, alertas y reportes...',
                'Listo para generar gráficas y exportaciones.',
                'Visualización BI completada.'
            ], 1700);
            return;
        }

        if (currentStep === 7) {
            window.location.href = 'index.php?route=graphs';
        }
    } catch (error) {
        console.error(error);
        alert('Ocurrió un error en el proceso. Verifica Apache, MySQL y la consola del navegador.');
    } finally {
        btnContinuar.disabled = false;
        btnRetroceder.disabled = false;
    }
}

async function animateWarehouse() {
    $('dwText').textContent = 'Construyendo modelo copo de nieve con dimensiones y hechos...';
    const card = $('step4');
    card.classList.add('processing');
    await wait(500);
    const dims = card.querySelectorAll('.dim, .fact');
    dims.forEach((d, i) => {
        setTimeout(() => d.classList.add('filled'), i * 180);
    });
    await wait(1400);
    const response = await fetch('api/kpis.php');
    const json = await response.json();
    if (json.ok && json.data && json.data.hasData) {
        warehouseReady = true;
        $('dwText').textContent = `Data Warehouse consolidado. Registros disponibles: ${json.data.warehouseRows.length}. Presiona Continuar para pasar a Capa IA.`;
    } else {
        $('dwText').textContent = 'No se detectaron datos consolidados. Revisa el CSV cargado.';
    }
    card.classList.remove('processing');
}

function animateStep(cardId, barId, textId, messages, duration) {
    return new Promise(resolve => {
        const card = $(cardId);
        const bar = $(barId);
        const text = $(textId);
        let progress = 0;
        let msgIndex = 0;
        card.classList.add('processing');
        bar.style.width = '0%';
        text.textContent = messages[0];

        const interval = setInterval(() => {
            progress += 4;
            bar.style.width = progress + '%';
            const next = Math.min(messages.length - 1, Math.floor((progress / 100) * messages.length));
            if (next !== msgIndex) {
                msgIndex = next;
                text.textContent = messages[msgIndex];
            }
            if (progress >= 100) {
                clearInterval(interval);
                setTimeout(() => {
                    card.classList.remove('processing');
                    resolve();
                }, 220);
            }
        }, duration / 25);
    });
}

function wait(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function uploadFiles() {
    const formData = new FormData();
    selectedFiles.forEach(file => formData.append('files[]', file));
    const response = await fetch('api/upload.php', { method: 'POST', body: formData });
    return await response.json();
}

async function resetAndGoBack() {
    const confirmReset = confirm('Retroceder limpiará los datos cargados en la tabla de hechos y volverá a Fuentes de Datos. ¿Deseas continuar?');
    if (!confirmReset) return;
    try {
        btnRetroceder.disabled = true;
        const response = await fetch('api/reset.php', { method: 'POST' });
        const json = await response.json();
        if (json.ok) {
            selectedFiles = [];
            uploadCompleted = false;
            warehouseReady = false;
            csvInput.value = '';
            fileList.innerHTML = '';
            $('uploadText').textContent = 'Esperando selección de archivos.';
            resetBars();
            resetSnowflake();
            setStep(1);
        } else {
            alert(json.message || 'No se pudo retroceder.');
        }
    } catch (error) {
        console.error(error);
        alert('No se pudo limpiar el Data Warehouse.');
    } finally {
        btnRetroceder.disabled = false;
    }
}

function resetBars() {
    const bars = ['stagingProgress','etlProgress','iaProgress','semanticProgress','biProgress'];
    bars.forEach(id => { if ($(id)) $(id).style.width = '0%'; });
    $('stagingText').textContent = 'Esperando carga desde fuentes.';
    $('etlText').textContent = 'Esperando confirmación de staging.';
    $('iaText').textContent = 'Esperando Data Warehouse.';
    $('semanticText').textContent = 'Esperando capa IA.';
    $('biText').textContent = 'Esperando capa semántica.';
    $('dwText').textContent = 'Sin datos consolidados todavía.';
}

function resetSnowflake() {
    document.querySelectorAll('.mini-snowflake .filled').forEach(el => el.classList.remove('filled'));
}
