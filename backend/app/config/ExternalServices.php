<?php

/**
 * Smart Campus Supabase Config
 *
 * Arquitectura final:
 * CSV/XLSX convertido a CSV -> Web PHP -> Supabase PostgreSQL Cloud -> Google Colab IA -> Streamlit Community Cloud.
 *
 * No coloques credenciales reales en GitHub. Para publicar, usa variables de entorno.
 */

function sc_env(string $key, $default = null) {
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

return [
    // Supabase reemplaza completamente MySQL/XAMPP como base de datos.
    // Ejemplo SUPABASE_URL: https://xxxxxxxxxxxxxxxxxxxx.supabase.co
    'SUPABASE_URL' => sc_env('SUPABASE_URL', 'https://ttjqobmwmjggvarsvvnn.supabase.co'),

    // Para demo académica puedes usar anon key si desactivas RLS o creas políticas.
    // Para backend privado, mejor usar service_role, pero nunca lo publiques en GitHub.
    'SUPABASE_API_KEY' => sc_env('SUPABASE_API_KEY', 'sb_publishable_9tdeX7nM-cMV0itosrA_MA_k4TrYjaP'),
    'SUPABASE_SCHEMA' => sc_env('SUPABASE_SCHEMA', 'public'),

    // URL base pública de Google Colab/ngrok, SIN /predict.
    // Ejemplo: https://xxxxx.ngrok-free.app
    'COLAB_API_URL' => sc_env('COLAB_API_URL', 'https://TU_URL_NGROK.ngrok-free.app'),

    // Recomendado: backend_export. El backend lee Supabase y envía CSV a Colab.
    // Así Colab no necesita credenciales de Supabase.
    'COLAB_INPUT_MODE' => sc_env('COLAB_INPUT_MODE', 'backend_export'),

    // Streamlit Community Cloud como dashboard final en nube.
    'GOOGLE_SHEETS_WEBAPP_URL' => sc_env('GOOGLE_SHEETS_WEBAPP_URL', 'PEGAR_URL_APPS_SCRIPT_WEB_APP_AQUI'),
    'GOOGLE_SHEETS_TOKEN' => sc_env('GOOGLE_SHEETS_TOKEN', 'CAMBIAR_TOKEN_SIMPLE'),
    'LOOKER_STUDIO_URL' => sc_env('LOOKER_STUDIO_URL', 'PEGAR_URL_PUBLICA_LOOKER_STUDIO_AQUI'),

    // Nuevo dashboard publicado en Streamlit Community Cloud.
    // Ejemplo: https://smart-campus-dashboard.streamlit.app
    'STREAMLIT_DASHBOARD_URL' => sc_env('STREAMLIT_DASHBOARD_URL', 'PEGAR_URL_PUBLICA_STREAMLIT_AQUI'),

    // Google Tag Manager / GA4 opcional para analítica formal.
    'GTM_CONTAINER_ID' => sc_env('GTM_CONTAINER_ID', 'GTM-XXXXXXX'),

    // Respaldo local técnico. Para cumplir “todo en nube”, déjalo en false.
    'KEEP_LOCAL_UPLOAD_BACKUP' => sc_env('KEEP_LOCAL_UPLOAD_BACKUP', 'false'),
    'UPLOAD_CSV_PATH' => __DIR__ . '/../../../storage/uploads/ultimo_dataset.csv',
    'PROCESSED_CSV_PATH' => __DIR__ . '/../../../cloud_data/smartcampus_looker_dataset_predicciones.csv',

    // Compatibilidad histórica: ya no se usa MySQL ni Power BI local.
    'ENABLE_MYSQL_HISTORY' => false,
    'LOCAL_FALLBACK' => false
];
