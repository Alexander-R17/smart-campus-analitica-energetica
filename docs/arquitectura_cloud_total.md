# Arquitectura Cloud Total — Smart Campus con Supabase

## Objetivo

Migrar el proyecto Smart Campus desde una ejecución local basada en XAMPP/MySQL/Power BI hacia una arquitectura cloud, manteniendo la misma interfaz web y el flujo visual lineal de 7 etapas.

La interfaz se mantiene igual para la sustentación: landing, login, tarjetas de proceso, animaciones, botón **Continuar** y botón final **Graficar**. Lo que cambia es la infraestructura interna: ya no se usa MySQL local como Data Warehouse ni Power BI Desktop como visualizador final.

## Flujo final

```text
1. Usuario sube uno o varios CSV desde la página web
2. PHP registra el lote en Supabase PostgreSQL
3. Supabase recibe staging_lectura_cruda
4. El ETL carga el modelo copo de nieve igual al MySQL original
5. Google Colab recibe el dataset consolidado y ejecuta ML
6. Las predicciones regresan a Supabase y se publican en Google Sheets
7. Looker Studio grafica desde Google Sheets
```

## Modelo copo de nieve conservado

Tablas principales:

```text
dimambiente
dimedificio
dimocupacion
dimtiempo
factconsumoenergetico
fact_consumo_energetico_pred
```

Tablas técnicas de proceso:

```text
staging_upload_batch
staging_lectura_cruda
```

## Comparación

| Capa | Antes | Ahora |
|---|---|---|
| Interfaz | PHP local | PHP con mismo diseño |
| Base de datos | MySQL/phpMyAdmin local | Supabase PostgreSQL cloud |
| Modelo | Copo de nieve MySQL | Copo de nieve equivalente en Supabase |
| IA | Google Colab | Google Colab |
| Capa semántica | Power BI local | Google Sheets |
| Dashboard | Power BI Desktop | Looker Studio |

## Configuración clave

Archivo:

```text
backend/app/config/ExternalServices.php
```

Variables principales:

```php
'SUPABASE_URL' => 'https://TU-PROYECTO.supabase.co',
'SUPABASE_API_KEY' => 'TU_SUPABASE_KEY',
'COLAB_API_URL' => 'https://TU_URL_NGROK.ngrok-free.app',
'GOOGLE_SHEETS_WEBAPP_URL' => 'https://script.google.com/macros/s/.../exec',
'LOOKER_STUDIO_URL' => 'https://lookerstudio.google.com/reporting/...',
```

## Sustentación breve

> El sistema mantiene la interfaz institucional del prototipo original, pero internamente toda la analítica opera en nube. El usuario carga archivos CSV, la aplicación registra el lote en Supabase PostgreSQL, valida los datos en una landing zone, ejecuta ETL hacia un modelo dimensional copo de nieve equivalente al de MySQL, envía el dataset consolidado a Google Colab para predicción con Machine Learning, guarda los resultados predictivos en Supabase y publica el dataset final en Google Sheets para que Looker Studio actualice el dashboard sin depender de Power BI local.
