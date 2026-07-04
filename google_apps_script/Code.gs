/**
 * Smart Campus Cloud → Google Sheets → Looker Studio
 * Web App para recibir el CSV predictivo generado por Google Colab.
 *
 * Configuración previa en Apps Script:
 * 1) Project Settings → Script properties:
 *    SPREADSHEET_ID = ID de tu Google Sheet
 *    SMARTCAMPUS_TOKEN = el mismo valor de GOOGLE_SHEETS_TOKEN en ExternalServices.php
 *    DATA_SHEET_NAME = smartcampus_dataset
 *    KPI_SHEET_NAME = smartcampus_kpis
 * 2) Deploy → New deployment → Web app
 *    Execute as: Me
 *    Who has access: Anyone with the link
 */

function doPost(e) {
  try {
    const body = JSON.parse(e.postData.contents || '{}');
    const props = PropertiesService.getScriptProperties();
    const expectedToken = props.getProperty('SMARTCAMPUS_TOKEN') || '';

    if (expectedToken && body.token !== expectedToken) {
      return jsonOutput({ ok: false, message: 'Token inválido.' });
    }

    const spreadsheetId = props.getProperty('SPREADSHEET_ID');
    if (!spreadsheetId) {
      return jsonOutput({ ok: false, message: 'Falta SPREADSHEET_ID en Script properties.' });
    }

    const csv = body.csv || '';
    if (!csv.trim()) {
      return jsonOutput({ ok: false, message: 'CSV vacío.' });
    }

    const dataSheetName = props.getProperty('DATA_SHEET_NAME') || 'smartcampus_dataset';
    const kpiSheetName = props.getProperty('KPI_SHEET_NAME') || 'smartcampus_kpis';
    const ss = SpreadsheetApp.openById(spreadsheetId);

    const values = Utilities.parseCsv(csv);
    if (!values.length || !values[0].length) {
      return jsonOutput({ ok: false, message: 'CSV sin columnas.' });
    }

    const dataSheet = getOrCreateSheet(ss, dataSheetName);
    dataSheet.clearContents();
    dataSheet.getRange(1, 1, values.length, values[0].length).setValues(values);
    dataSheet.setFrozenRows(1);

    const resumen = body.resumen || {};
    const kpiRows = [
      ['metric', 'value'],
      ['batch_id', body.batch_id || ''],
      ['updated_at', body.updated_at || new Date().toISOString()],
      ['registros', resumen.registros || Math.max(values.length - 1, 0)],
      ['consumo_total_kwh', resumen.consumo_total_kwh || ''],
      ['prediccion_total_kwh', resumen.prediccion_total_kwh || ''],
      ['riesgo_promedio', resumen.riesgo_promedio || '']
    ];

    const kpiSheet = getOrCreateSheet(ss, kpiSheetName);
    kpiSheet.clearContents();
    kpiSheet.getRange(1, 1, kpiRows.length, 2).setValues(kpiRows);
    kpiSheet.setFrozenRows(1);

    SpreadsheetApp.flush();

    return jsonOutput({
      ok: true,
      message: 'Google Sheets actualizado correctamente.',
      rows: Math.max(values.length - 1, 0),
      columns: values[0].length,
      spreadsheet_url: ss.getUrl()
    });
  } catch (err) {
    return jsonOutput({ ok: false, message: String(err) });
  }
}

function doGet() {
  return jsonOutput({ ok: true, message: 'Smart Campus Cloud Sheets Web App activo.' });
}

function getOrCreateSheet(ss, name) {
  return ss.getSheetByName(name) || ss.insertSheet(name);
}

function jsonOutput(obj) {
  return ContentService
    .createTextOutput(JSON.stringify(obj))
    .setMimeType(ContentService.MimeType.JSON);
}
