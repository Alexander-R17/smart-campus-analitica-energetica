# Estructura solicitada por el docente

La estructura está separada así:

## 1. Front-end
Ubicación: `frontend/`

Contiene la parte visual de la página web:
- Login.
- Flujo modular 1 al 7.
- Botones de continuar/retroceder.
- Botón Ver datos actuales.
- Botón Graficar / Power BI.

## 2. Back-end
Ubicación: `backend/`

Contiene clases/controladores PHP:
- Subida de CSV.
- Conexión a MySQL.
- Llamada al API de Google Colab.
- Guardado del CSV procesado.
- Carga a tabla MySQL.

## 3. Machine Learning
Ubicación: `scripts_colab/`

Contiene el código Python ejecutable en Google Colab:
- Random Forest Regressor.
- Random Forest Classifier.
- Predicción de consumo.
- Riesgo de sobreconsumo.
- Capa semántica.

## 4. Database
Ubicación: `database/`

Contiene scripts SQL:
- Creación de base de datos.
- Tabla de hechos procesada.

## 5. Power BI
Ubicación: `powerbi/`

Contiene:
- Carpeta `data/` para CSV procesado.
- Carpeta `report/` para colocar el archivo `.pbix`.
