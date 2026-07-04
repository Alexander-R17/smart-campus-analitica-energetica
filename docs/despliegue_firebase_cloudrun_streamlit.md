# Despliegue cloud: Web + Supabase + Colab + Streamlit

## Punto importante
Firebase Hosting no ejecuta PHP directamente. Por eso, para mantener tu web actual sin rediseñarla, se usa:

- **Cloud Run** para ejecutar el backend PHP.
- **Firebase Hosting** como dominio público y entrada principal.
- **Supabase** para la base de datos cloud.
- **Google Colab/ngrok** para IA durante la demo académica.
- **Streamlit Community Cloud** para el dashboard final.

## Orden de conexión

1. Supabase: ejecutar `database/01_supabase_sql_editor_pegar_aqui.sql`.
2. Supabase: ejecutar `database/02_supabase_web_analytics_streamlit.sql`.
3. Colab: ejecutar `scripts_colab/PROCESO_5_Y_6_SUPABASE.ipynb` y copiar la URL ngrok.
4. Streamlit: subir carpeta `streamlit_dashboard/` a GitHub y publicar.
5. Backend PHP: pegar credenciales en variables de entorno o en `ExternalServices.php` para prueba local.
6. Firebase/Cloud Run: publicar la web para tener un solo link.

## Variables necesarias

```text
SUPABASE_URL
SUPABASE_API_KEY
COLAB_API_URL
STREAMLIT_DASHBOARD_URL
GTM_CONTAINER_ID opcional
```

## Sustentación

La interfaz principal conserva login, carga de archivos, flujo 1 al 7, animaciones y ventanas emergentes. El cambio real está en la arquitectura: los datos van a Supabase, Google Colab realiza la predicción, la actividad del usuario se registra en `web_eventos` y el dashboard de Streamlit lee todo desde Supabase.
