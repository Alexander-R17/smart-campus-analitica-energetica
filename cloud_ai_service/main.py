import os
from datetime import datetime, timezone

import numpy as np
import pandas as pd
import requests
from flask import Flask, jsonify, request
from flask_cors import CORS
from sklearn.ensemble import RandomForestRegressor


app = Flask(__name__)
CORS(app)


SUPABASE_URL = os.getenv("SUPABASE_URL", "").rstrip("/")
SUPABASE_KEY = os.getenv("SUPABASE_KEY", "")


def supabase_headers() -> dict:
    return {
        "apikey": SUPABASE_KEY,
        "Authorization": f"Bearer {SUPABASE_KEY}",
        "Content-Type": "application/json",
        "Accept": "application/json",
        "Prefer": "return=minimal",
    }


def check_config():
    if not SUPABASE_URL or not SUPABASE_KEY:
        return False
    return True


def supabase_get(table: str, params: dict | None = None):
    url = f"{SUPABASE_URL}/rest/v1/{table}"

    response = requests.get(
        url,
        headers=supabase_headers(),
        params=params or {},
        timeout=40,
    )

    if response.status_code >= 400:
        raise RuntimeError(
            f"Error leyendo {table}: {response.status_code} - {response.text}"
        )

    return response.json()


def supabase_insert(table: str, records: list[dict]):
    if not records:
        return {"status_code": 204, "message": "Sin registros para insertar"}

    url = f"{SUPABASE_URL}/rest/v1/{table}"

    response = requests.post(
        url,
        headers=supabase_headers(),
        json=records,
        timeout=60,
    )

    if response.status_code >= 400:
        raise RuntimeError(
            f"Error insertando en {table}: {response.status_code} - {response.text}"
        )

    return {"status_code": response.status_code, "message": "Insertado correctamente"}


def to_number(series, default=0):
    return pd.to_numeric(series, errors="coerce").fillna(default)


def safe_float(value, default=0.0):
    try:
        if pd.isna(value):
            return default
        return float(value)
    except Exception:
        return default


def safe_int(value, default=0):
    try:
        if pd.isna(value):
            return default
        return int(float(value))
    except Exception:
        return default


def calcular_predicciones(df: pd.DataFrame) -> pd.DataFrame:
    df = df.copy()

    columnas_numericas = [
        "id_tiempo",
        "id_edificio",
        "id_ambiente",
        "id_ocupacion",
        "consumo_kwh",
        "demanda_pico_kw",
        "factor_potencia",
        "temperatura",
        "eficiencia",
        "riesgo_sobreconsumo",
    ]

    for col in columnas_numericas:
        if col in df.columns:
            df[col] = to_number(df[col])

    if "consumo_kwh" not in df.columns:
        raise ValueError("La tabla fact_consumo_energetico no tiene la columna consumo_kwh.")

    features = []

    for col in [
        "id_tiempo",
        "id_edificio",
        "id_ambiente",
        "id_ocupacion",
        "demanda_pico_kw",
        "factor_potencia",
        "temperatura",
        "eficiencia",
    ]:
        if col in df.columns:
            features.append(col)

    if len(df) >= 10 and len(features) >= 2:
        X = df[features].fillna(0)
        y = df["consumo_kwh"].fillna(0)

        model = RandomForestRegressor(
            n_estimators=80,
            random_state=42,
            max_depth=8,
        )

        model.fit(X, y)
        df["pred_consumo_kwh"] = model.predict(X) * 1.05
    else:
        df["pred_consumo_kwh"] = df["consumo_kwh"] * 1.08

    promedio_pred = df["pred_consumo_kwh"].mean()
    max_pred = df["pred_consumo_kwh"].max()
    min_pred = df["pred_consumo_kwh"].min()

    rango = max(max_pred - min_pred, 1)

    df["riesgo_sobreconsumo_prob"] = (
        (df["pred_consumo_kwh"] - min_pred) / rango
    ).clip(0, 1)

    def clasificar_riesgo(row):
        prob = row["riesgo_sobreconsumo_prob"]
        pred = row["pred_consumo_kwh"]

        if prob >= 0.70 or pred >= promedio_pred * 1.15:
            return "Alto"
        if prob >= 0.40 or pred >= promedio_pred:
            return "Medio"
        return "Bajo"

    df["riesgo_sobreconsumo_pred"] = df.apply(clasificar_riesgo, axis=1)

    return df


def preparar_registros_prediccion(df: pd.DataFrame) -> list[dict]:
    fecha_proceso = datetime.now(timezone.utc).isoformat()

    registros = []

    for _, row in df.head(1000).iterrows():
        registro = {
            "id_tiempo": safe_int(row.get("id_tiempo")),
            "id_edificio": safe_int(row.get("id_edificio")),
            "id_ambiente": safe_int(row.get("id_ambiente")),

            # 🔥 FIX CLAVE: esto DEBE ser entero
            "ocupacion": safe_int(row.get("id_ocupacion")),

            "temperatura": safe_float(row.get("temperatura")),
            "demanda_pico_kw": safe_float(row.get("demanda_pico_kw")),
            "factor_potencia": safe_float(row.get("factor_potencia")),
            "consumo_kwh": safe_float(row.get("consumo_kwh")),
            "pred_consumo_kwh": safe_float(row.get("pred_consumo_kwh")),
            "riesgo_sobreconsumo_prob": safe_float(row.get("riesgo_sobreconsumo_prob")),
            "riesgo_sobreconsumo_pred": str(row.get("riesgo_sobreconsumo_pred", "Bajo")),
            "fecha_proceso": fecha_proceso,
        }

        registros.append(registro)

    return registros


@app.route("/", methods=["GET"])
def home():
    return jsonify({
        "ok": True,
        "service": "Smart Campus IA Cloud Service",
        "message": "Servicio activo. Usa /health o /process."
    })


@app.route("/health", methods=["GET"])
def health():
    return jsonify({
        "ok": True,
        "configured": check_config(),
        "service": "smart-campus-ia",
    })


@app.route("/process", methods=["POST"])
def process():
    try:
        if not check_config():
            return jsonify({
                "ok": False,
                "error": "Faltan variables SUPABASE_URL o SUPABASE_KEY."
            }), 500

        data = supabase_get(
            "fact_consumo_energetico",
            params={
                "select": "*",
                "limit": "5000",
                "order": "id_fact.asc",
            }
        )

        df = pd.DataFrame(data)

        if df.empty:
            return jsonify({
                "ok": False,
                "message": "No hay datos en fact_consumo_energetico para procesar."
            }), 400

        df_pred = calcular_predicciones(df)
        registros = preparar_registros_prediccion(df_pred)

        resultado_insert = supabase_insert(
            "fact_consumo_energetico_pred",
            registros
        )

        return jsonify({
            "ok": True,
            "message": "Predicciones generadas y enviadas a Supabase.",
            "registros_leidos": len(df),
            "registros_insertados": len(registros),
            "insert_status": resultado_insert,
        })

    except Exception as e:
        return jsonify({
            "ok": False,
            "error": str(e),
        }), 500


@app.route("/debug/tables", methods=["GET"])
def debug_tables():
    try:
        if not check_config():
            return jsonify({
                "ok": False,
                "error": "Faltan variables SUPABASE_URL o SUPABASE_KEY."
            }), 500

        fact = supabase_get(
            "fact_consumo_energetico",
            params={"select": "*", "limit": "3"}
        )

        eventos = supabase_get(
            "web_eventos",
            params={"select": "*", "limit": "3"}
        )

        return jsonify({
            "ok": True,
            "fact_consumo_energetico_muestra": fact,
            "web_eventos_muestra": eventos,
        })

    except Exception as e:
        return jsonify({
            "ok": False,
            "error": str(e),
        }), 500


if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8080)