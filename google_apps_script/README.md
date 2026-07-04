# Google Apps Script — Smart Campus

Este script recibe el CSV procesado por Google Colab desde la web PHP y lo publica en Google Sheets para que Looker Studio lo use como fuente de datos.

## Pasos

1. Crea un Google Sheet nuevo llamado `SmartCampus_Looker_Dataset`.
2. Copia el ID del Sheet desde la URL.
3. En Apps Script, pega el contenido de `Code.gs`.
4. En **Project Settings → Script properties**, crea:
   - `SPREADSHEET_ID`: ID del Google Sheet.
   - `SMARTCAMPUS_TOKEN`: el mismo valor de `GOOGLE_SHEETS_TOKEN` en `backend/app/config/ExternalServices.php`.
   - `DATA_SHEET_NAME`: `smartcampus_dataset`.
   - `KPI_SHEET_NAME`: `smartcampus_kpis`.
5. Publica como **Web app**:
   - Execute as: `Me`.
   - Who has access: `Anyone with the link`.
6. Copia la URL `/exec` en `GOOGLE_SHEETS_WEBAPP_URL`.
7. En Looker Studio, crea una fuente de datos usando el conector Google Sheets y selecciona la hoja `smartcampus_dataset`.
