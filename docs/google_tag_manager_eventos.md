# Google Tag Manager / GA4 - eventos Smart Campus

La web ya ejecuta `window.dataLayer.push()` desde `frontend/assets/js/app.js`. Para que Google Tag Manager capture los eventos:

1. Crear contenedor web en Google Tag Manager.
2. Pegar el snippet GTM en `frontend/public/index.php` si se usará GA4 real.
3. Crear etiqueta GA4 Event.
4. Crear activadores de evento personalizado con estos nombres:

```text
inicio_flujo
login_usuario
seleccion_archivo
click_continuar
carga_archivo
validacion_staging
etl_completado
modelo_copo_nieve_completado
colab_ia_iniciado
colab_ia_completado
visualizacion_dashboard
ver_datos_actuales
abandono_flujo
error_proceso
salida_usuario
```

## Parámetros enviados

```text
usuario_id
sesion_id
etapa_numero
etapa_nombre
resultado
dispositivo
navegador
tiempo_seg
url_pagina
```

## Uso en el dashboard Streamlit

Independientemente de GA4, los eventos también se registran en Supabase en la tabla `web_eventos`, para que la página 3 del dashboard responda las 4 preguntas del laboratorio:

1. ¿Qué etapas generan más interacción?
2. ¿Dónde abandonan más los usuarios?
3. ¿Cuántos completan el proceso hasta el dashboard?
4. ¿Qué dispositivos utilizan los usuarios?
