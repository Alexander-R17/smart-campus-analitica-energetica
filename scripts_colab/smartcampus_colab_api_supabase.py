# SmartCampus Supabase + Colab API
# Ejecutar en Google Colab por celdas.
# CELDA 1:
# !pip -q install flask flask-cors pyngrok pandas scikit-learn numpy

import io
import pandas as pd
import numpy as np
from flask import Flask, request, jsonify
from flask_cors import CORS
from sklearn.ensemble import RandomForestRegressor, RandomForestClassifier

app = Flask(__name__)
CORS(app)

MESES = {
    1: "Ene", 2: "Feb", 3: "Mar", 4: "Abr", 5: "May", 6: "Jun",
    7: "Jul", 8: "Ago", 9: "Sep", 10: "Oct", 11: "Nov", 12: "Dic"
}

def franja_horaria(hora):
    try:
        hora = int(hora)
        if 6 <= hora <= 11:
            return "Mañana"
        if 12 <= hora <= 17:
            return "Tarde"
        return "Noche"
    except Exception:
        return "Sin dato"

def nivel_riesgo(prob):
    if prob >= 0.70:
        return "Alto"
    if prob >= 0.40:
        return "Medio"
    return "Bajo"

def preparar_dataset(df):
    df = df.copy()
    necesarias = [
        "id_tiempo", "hora", "mes", "anio", "id_edificio", "id_ambiente",
        "ocupacion", "temperatura", "demanda_pico_kw", "factor_potencia", "consumo_kwh"
    ]
    for col in necesarias:
        if col not in df.columns:
            df[col] = 0

    for col in ["id_tiempo", "hora", "mes", "anio", "id_edificio", "id_ambiente"]:
        df[col] = pd.to_numeric(df[col], errors="coerce").fillna(0).astype(int)

    for col in ["ocupacion", "temperatura", "demanda_pico_kw", "factor_potencia", "consumo_kwh"]:
        df[col] = pd.to_numeric(df[col], errors="coerce").fillna(0)

    if "nombre_edificio" not in df.columns:
        df["nombre_edificio"] = "Edificio " + df["id_edificio"].astype(str)
    else:
        df["nombre_edificio"] = df["nombre_edificio"].fillna("Edificio " + df["id_edificio"].astype(str))

    if "tipo_ambiente" not in df.columns:
        tipos = ["Aula", "Laboratorio", "Auditorio", "Biblioteca", "Oficina", "Administrativo"]
        df["tipo_ambiente"] = [tipos[i % len(tipos)] for i in range(len(df))]
    else:
        df["tipo_ambiente"] = df["tipo_ambiente"].fillna("Aula")

    df["nombre_mes"] = df.get("nombre_mes", df["mes"].map(MESES)).fillna(df["mes"].map(MESES)).fillna("Sin mes")
    df["periodo"] = df.get("periodo", df["anio"].astype(str) + "-" + df["mes"].astype(str).str.zfill(2))
    df["periodo"] = df["periodo"].fillna(df["anio"].astype(str) + "-" + df["mes"].astype(str).str.zfill(2))
    df["franja_horaria"] = df.get("franja_horaria", df["hora"].apply(franja_horaria))
    df["franja_horaria"] = df["franja_horaria"].fillna(df["hora"].apply(franja_horaria))
    return df

def ejecutar_modelo_ml(df):
    df = preparar_dataset(df)
    columnas_modelo = [
        "hora", "mes", "anio", "id_edificio", "id_ambiente",
        "ocupacion", "temperatura", "demanda_pico_kw", "factor_potencia"
    ]
    X = df[columnas_modelo]
    y_reg = df["consumo_kwh"]

    if len(df) < 5 or y_reg.nunique() <= 1:
        df["pred_consumo_kwh"] = np.round(y_reg, 2)
        df["sobreconsumo_real"] = 0
        df["riesgo_sobreconsumo_prob"] = 0.0
    else:
        limite_alto = df["consumo_kwh"].quantile(0.75)
        df["sobreconsumo_real"] = (df["consumo_kwh"] >= limite_alto).astype(int)
        y_clf = df["sobreconsumo_real"]

        reg = RandomForestRegressor(n_estimators=120, random_state=42, max_depth=8)
        reg.fit(X, y_reg)
        df["pred_consumo_kwh"] = np.round(reg.predict(X), 2)

        if y_clf.nunique() <= 1:
            df["riesgo_sobreconsumo_prob"] = float(y_clf.iloc[0])
        else:
            clf = RandomForestClassifier(n_estimators=120, random_state=42, max_depth=8)
            clf.fit(X, y_clf)
            df["riesgo_sobreconsumo_prob"] = np.round(clf.predict_proba(X)[:, 1], 3)

    df["riesgo_sobreconsumo_pred"] = df["riesgo_sobreconsumo_prob"].apply(nivel_riesgo)
    df["eficiencia_energetica_pct"] = np.round(
        np.where(df["demanda_pico_kw"] > 0, (df["ocupacion"] / df["demanda_pico_kw"]) * 10, 0),
        2
    )
    df["riesgo_energetico_indice"] = df["riesgo_sobreconsumo_prob"].apply(nivel_riesgo)

    columnas_salida = [
        "id_tiempo", "hora", "mes", "anio", "id_edificio", "id_ambiente",
        "ocupacion", "temperatura", "demanda_pico_kw", "factor_potencia",
        "consumo_kwh", "nombre_edificio", "tipo_ambiente", "nombre_mes",
        "periodo", "franja_horaria", "eficiencia_energetica_pct",
        "riesgo_energetico_indice", "pred_consumo_kwh", "sobreconsumo_real",
        "riesgo_sobreconsumo_prob", "riesgo_sobreconsumo_pred"
    ]
    return df[columnas_salida]

def resumen_dataset(df_procesado):
    return {
        "registros": int(len(df_procesado)),
        "consumo_total_kwh": float(df_procesado["consumo_kwh"].sum()),
        "prediccion_total_kwh": float(df_procesado["pred_consumo_kwh"].sum()),
        "riesgo_promedio": float(df_procesado["riesgo_sobreconsumo_prob"].mean()) if len(df_procesado) else 0.0
    }

@app.route("/health", methods=["GET"])
def health():
    return jsonify({"ok": True, "message": "API Colab SmartCampus Supabase activa"})

@app.route("/predict", methods=["POST"])
def predict_from_csv():
    if "csv" not in request.files:
        return jsonify({"ok": False, "error": "No se recibió archivo CSV"}), 400
    try:
        df = pd.read_csv(request.files["csv"])
        df_procesado = ejecutar_modelo_ml(df)
        buffer = io.StringIO()
        df_procesado.to_csv(buffer, index=False)
        return jsonify({
            "ok": True,
            "message": "Predicción generada correctamente desde Supabase backend export",
            "source": "supabase_backend_export",
            "resumen": resumen_dataset(df_procesado),
            "csv": buffer.getvalue()
        })
    except Exception as e:
        return jsonify({"ok": False, "error": str(e)}), 500

# CELDA FINAL:
# from pyngrok import ngrok
# ngrok.kill()
# ngrok.set_auth_token("PEGA_AQUI_TU_TOKEN_NGROK")
# public_url = ngrok.connect(5000).public_url
# print("URL pública de Colab:", public_url)
# print("Endpoint de predicción:", public_url + "/predict")
# app.run(host="0.0.0.0", port=5000)
