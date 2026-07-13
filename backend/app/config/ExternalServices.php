<?php

/**
 * Smart Campus Cloud Config
 *
 * Arquitectura actual:
 * Web PHP -> Supabase PostgreSQL Cloud -> Render IA Flask API -> Streamlit Community Cloud.
 *
 * IMPORTANTE:
 * No publiques credenciales reales en GitHub.
 * Para producción usa variables de entorno.
 */

if (!function_exists('sc_env')) {
    function sc_env(string $key, $default = null) {
        $value = getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

return [
    /*
    |--------------------------------------------------------------------------
    | SUPABASE CLOUD DATABASE
    |--------------------------------------------------------------------------
    | Supabase reemplaza completamente a MySQL/XAMPP como base de datos.
    */

    'SUPABASE_URL' => sc_env(
        'SUPABASE_URL',
        'https://ttjqobmwmjggvarsvvnn.supabase.co'
    ),

    /*
     * Pega aquí tu Supabase publishable key SOLO en tu proyecto local.
     * No pegues service_role aquí si este archivo va a GitHub.
     */
    'SUPABASE_API_KEY' => sc_env(
        'SUPABASE_API_KEY',
        'sb_publishable_9tdeX7nM-cMV0itosrA_MA_k4TrYjaP'
    ),

    'SUPABASE_SCHEMA' => sc_env(
        'SUPABASE_SCHEMA',
        'public'
    ),


    /*
    |--------------------------------------------------------------------------
    | SERVICIO IA EN RENDER
    |--------------------------------------------------------------------------
    | Este servicio reemplaza a Google Colab + ngrok.
    | URL pública actual:
    | https://smart-campus-ia.onrender.com
    */

    'AI_SERVICE_URL' => sc_env(
        'AI_SERVICE_URL',
        'https://smart-campus-ia.onrender.com'
    ),

    /*
     * Compatibilidad histórica:
     * Si algún archivo antiguo todavía busca COLAB_API_URL,
     * lo hacemos apuntar también a Render para no romper el flujo.
     */
    'COLAB_API_URL' => sc_env(
        'COLAB_API_URL',
        'https://smart-campus-ia.onrender.com'
    ),

    'COLAB_INPUT_MODE' => sc_env(
        'COLAB_INPUT_MODE',
        'backend_export'
    ),


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD STREAMLIT
    |--------------------------------------------------------------------------
    | Dashboard final publicado en Streamlit Community Cloud.
    */

    'STREAMLIT_DASHBOARD_URL' => sc_env(
        'STREAMLIT_DASHBOARD_URL',
        'https://smart-campus-nueva-esperanza-bi.streamlit.app'
    ),


    /*
    |--------------------------------------------------------------------------
    | GOOGLE / ANALÍTICA WEB OPCIONAL
    |--------------------------------------------------------------------------
    | GTM / GA4 queda como complemento formal.
    */

    'GTM_CONTAINER_ID' => sc_env(
        'GTM_CONTAINER_ID',
        'GTM-XXXXXXX'
    ),

    'GOOGLE_SHEETS_WEBAPP_URL' => sc_env(
        'GOOGLE_SHEETS_WEBAPP_URL',
        ''
    ),

    'GOOGLE_SHEETS_TOKEN' => sc_env(
        'GOOGLE_SHEETS_TOKEN',
        ''
    ),

    'LOOKER_STUDIO_URL' => sc_env(
        'LOOKER_STUDIO_URL',
        ''
    ),


    /*
    |--------------------------------------------------------------------------
    | ARCHIVOS TEMPORALES
    |--------------------------------------------------------------------------
    | En nube final esto debe ser mínimo.
    */

    'KEEP_LOCAL_UPLOAD_BACKUP' => sc_env(
        'KEEP_LOCAL_UPLOAD_BACKUP',
        'false'
    ),

    'UPLOAD_CSV_PATH' => __DIR__ . '/../../../storage/uploads/ultimo_dataset.csv',

    'PROCESSED_CSV_PATH' => __DIR__ . '/../../../cloud_data/smartcampus_looker_dataset_predicciones.csv',


    /*
    |--------------------------------------------------------------------------
    | COMPATIBILIDAD ANTIGUA
    |--------------------------------------------------------------------------
    | Ya no se usa MySQL ni Power BI local.
    */

    'ENABLE_MYSQL_HISTORY' => false,
    'LOCAL_FALLBACK' => false
];