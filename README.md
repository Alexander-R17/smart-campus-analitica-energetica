# Smart Campus Cloud con Supabase + Colab + Streamlit

Versión actualizada del proyecto para trabajar con arquitectura cloud y dashboard tipo Power BI desarrollado en Streamlit Community Cloud.

## Qué se mantiene

- Landing principal.
- Login.
- Flujo visual 1 al 7.
- Animaciones y barras de avance.
- Ventanas emergentes.
- Carga de archivos CSV.

## Qué cambia por detrás

- MySQL local queda reemplazado por Supabase.
- Power BI / Looker Studio queda reemplazado por Streamlit Community Cloud.
- Google Colab sigue como motor de IA.
- Se agrega registro de comportamiento del usuario en Supabase (`web_eventos`).

## Arquitectura

```text
Web principal → Supabase → Google Colab → Supabase predicciones → Streamlit Dashboard
        ↓
  Eventos de usuario → Supabase web_eventos → Streamlit Página 3
```

## Pasos rápidos

1. Crear proyecto en Supabase.
2. Ejecutar `database/01_supabase_sql_editor_pegar_aqui.sql`.
3. Ejecutar `database/02_supabase_web_analytics_streamlit.sql`.
4. Configurar `backend/app/config/ExternalServices.php`.
5. Ejecutar Colab y pegar `COLAB_API_URL`.
6. Subir `streamlit_dashboard/` a GitHub.
7. Publicar app en Streamlit Community Cloud.
8. Pegar la URL pública en `STREAMLIT_DASHBOARD_URL`.
9. Abrir la web y presionar `Graficar`.

## Dashboard Streamlit

El dashboard está en:

```text
streamlit_dashboard/app.py
```

Tiene 3 páginas:

1. KPIs principales y predicción.
2. Análisis OLAP y visualizaciones estadísticas.
3. Analítica web + tabla de hechos y dimensiones.

## Despliegue

Lee:

```text
docs/despliegue_firebase_cloudrun_streamlit.md
```
