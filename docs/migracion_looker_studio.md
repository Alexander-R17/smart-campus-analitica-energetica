# Looker Studio como visualización final

En esta versión, Looker Studio reemplaza a Power BI Desktop.

## Flujo de visualización

```text
Supabase PostgreSQL → Google Colab IA → Google Sheets → Looker Studio
```

## Fuente de datos recomendada

Looker Studio debe conectarse a la hoja:

```text
smartcampus_dataset
```

Esa hoja es actualizada por:

```text
google_apps_script/Code.gs
```

## Hojas creadas

- `smartcampus_dataset`: datos predictivos finales.
- `smartcampus_kpis`: resumen de KPIs para tarjetas.

## Recomendación de dashboard

Usa gráficos equivalentes a tu Power BI original:

- Tarjeta de consumo total.
- Tarjeta de predicción total.
- Línea de consumo mensual.
- Barras por edificio.
- Barras por ambiente.
- Riesgo energético por periodo.
- Tabla de detalle predictivo.
