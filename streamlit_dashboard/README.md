# Smart Campus Streamlit Dashboard

Dashboard BI en Streamlit Community Cloud para reemplazar Looker Studio y acercarse al diseño original de Power BI.

## Contenido

- `app.py`: aplicación principal.
- `modules/conexion_supabase.py`: lectura dinámica desde Supabase.
- `modules/componentes.py`: estilos visuales tipo Power BI.
- `assets/logo.png`: logo institucional.
- `.streamlit/config.toml`: tema visual.
- `requirements.txt`: dependencias para Streamlit Community Cloud.

## Secretos necesarios en Streamlit Community Cloud

En la app publicada, entrar a **Settings → Secrets** y pegar:

```toml
SUPABASE_URL = "https://TU-PROYECTO.supabase.co"
SUPABASE_API_KEY = "TU_SUPABASE_ANON_O_SERVICE_ROLE_KEY"
```

## Tablas que lee

- `dimambiente`
- `dimedificio`
- `dimocupacion`
- `dimtiempo`
- `factconsumoenergetico`
- `fact_consumo_energetico_pred`
- `web_eventos`

## Páginas del dashboard

1. KPIs principales + análisis predictivo.
2. Análisis OLAP + visualizaciones estadísticas.
3. Analítica web de comportamiento + tabla de hechos y dimensiones.

## Publicación

1. Subir esta carpeta a GitHub.
2. Crear app en Streamlit Community Cloud.
3. Repositorio: tu repositorio.
4. Branch: `main`.
5. Main file path: `streamlit_dashboard/app.py` si subes todo el proyecto completo, o `app.py` si subes solo esta carpeta.
6. Pegar la URL pública en `backend/app/config/ExternalServices.php` como `STREAMLIT_DASHBOARD_URL`.
