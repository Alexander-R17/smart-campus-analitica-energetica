# Arquitectura final Smart Campus con Streamlit

```text
Usuario web
  ↓
Firebase Hosting / Cloud Run: página principal Smart Campus
  ↓
Google Tag Manager + evento interno web_eventos
  ↓
Supabase PostgreSQL: modelo copo de nieve + eventos web
  ↓
Google Colab: predicciones IA
  ↓
Supabase: fact_consumo_energetico_pred
  ↓
Streamlit Community Cloud: dashboard público tipo Power BI
```

## Páginas del dashboard

### Página 1
KPIs principales: descriptiva, diagnóstica, predictiva y prescriptiva. Incluye predicciones de consumo, demanda, riesgo y periodos de alto consumo.

### Página 2
Análisis OLAP y control operativo: demanda pico, factor de potencia, ranking de ambientes, ranking de riesgo, relación temperatura-consumo, matriz hora-mes e histogramas.

### Página 3
Analítica web arriba y tabla de hechos abajo. Responde:

1. ¿Qué etapas generan más interacción?
2. ¿Dónde abandonan más los usuarios?
3. ¿Cuántos completan el proceso hasta el dashboard?
4. ¿Qué dispositivos utilizan los usuarios?

## Archivos importantes

- `streamlit_dashboard/app.py`
- `streamlit_dashboard/requirements.txt`
- `backend/app/controllers/StreamlitController.php`
- `backend/app/controllers/EventController.php`
- `database/02_supabase_web_analytics_streamlit.sql`
- `frontend/assets/js/app.js`
