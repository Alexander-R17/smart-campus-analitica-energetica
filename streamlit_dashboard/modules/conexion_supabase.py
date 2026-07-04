import os
from typing import Dict, List, Any

import pandas as pd
import requests
import streamlit as st


def get_secret(name: str, default: str = "") -> str:
    try:
        value = st.secrets.get(name, "")
        if value:
            return str(value)
    except Exception:
        pass
    return os.getenv(name, default)


def supabase_headers() -> Dict[str, str]:
    key = get_secret("SUPABASE_API_KEY")
    return {
        "apikey": key,
        "Authorization": f"Bearer {key}",
        "Accept": "application/json",
        "Content-Type": "application/json",
    }


def configured() -> bool:
    url = get_secret("SUPABASE_URL")
    key = get_secret("SUPABASE_API_KEY")
    return bool(url and key and "TU-PROYECTO" not in url and "PEGAR" not in key)


@st.cache_data(ttl=30, show_spinner=False)
def fetch_table(table: str, select: str = "*", limit: int = 10000, order: str | None = None) -> pd.DataFrame:
    if not configured():
        return pd.DataFrame()
    base = get_secret("SUPABASE_URL").rstrip("/")
    params = {"select": select, "limit": str(limit)}
    if order:
        params["order"] = order
    response = requests.get(f"{base}/rest/v1/{table}", headers=supabase_headers(), params=params, timeout=60)
    if response.status_code >= 400:
        st.warning(f"No se pudo leer {table}: HTTP {response.status_code}")
        return pd.DataFrame()
    data: List[Dict[str, Any]] = response.json()
    return pd.DataFrame(data)


def sample_energy() -> tuple[pd.DataFrame, pd.DataFrame]:
    # Dataset de respaldo para que el dashboard abra aunque Supabase aún no esté configurado.
    rng = pd.date_range("2026-01-01", periods=120, freq="D")
    edificios = ["Edificio 1", "Edificio 4", "Edificio 7", "Edificio 10", "Laboratorio Central", "Pabellón B"]
    ambientes = ["Aula", "Biblioteca", "Laboratorio", "Oficina", "Auditorio"]
    rows = []
    for i, fecha in enumerate(rng):
        rows.append({
            "id_fact": i + 1,
            "hora": [7, 8, 10, 13, 14, 17, 19, 21][i % 8],
            "mes": int(fecha.month),
            "anio": int(fecha.year),
            "nombre_mes": fecha.strftime("%b"),
            "periodo": fecha.strftime("%Y-%m"),
            "franja_horaria": ["Mañana", "Tarde", "Noche"][i % 3],
            "nombre_edificio": edificios[i % len(edificios)],
            "tipo_ambiente": ambientes[i % len(ambientes)],
            "ocupacion": 35 + (i * 7) % 85,
            "temperatura": 18 + (i % 12) * 0.9,
            "demanda_pico_kw": 22 + (i % 18) * 2.1,
            "factor_potencia": 0.80 + ((i % 18) / 100),
            "consumo_kwh": 120 + (i % 40) * 11.5,
            "eficiencia": 2 + (i % 22) * 0.8,
            "riesgo_sobreconsumo": 1 if i % 5 in [0, 1] else 0,
        })
    df = pd.DataFrame(rows)
    pred = df.tail(60).copy()
    pred["pred_consumo_kwh"] = pred["consumo_kwh"] * (1 + ((pred.index % 7) - 3) / 100)
    pred["riesgo_sobreconsumo_prob"] = pred["riesgo_sobreconsumo"].map({1: 0.86, 0: 0.12})
    pred["riesgo_sobreconsumo_pred"] = pred["riesgo_sobreconsumo"].map({1: "Alto", 0: "Bajo"})
    pred["fecha_proceso"] = pd.Timestamp.now()
    return df, pred


@st.cache_data(ttl=30, show_spinner=False)
def load_energy_data() -> tuple[pd.DataFrame, pd.DataFrame]:
    if not configured():
        return sample_energy()

    fact = fetch_table("factconsumoenergetico", limit=50000, order="id_fact.asc")
    tiempo = fetch_table("dimtiempo", limit=50000)
    edificio = fetch_table("dimedificio", limit=50000)
    ambiente = fetch_table("dimambiente", limit=50000)
    ocupacion = fetch_table("dimocupacion", limit=50000)
    pred = fetch_table("fact_consumo_energetico_pred", limit=50000, order="id.asc")

    if fact.empty:
        return sample_energy()

    df = fact.copy()
    if not tiempo.empty and "id_tiempo" in df.columns:
        t = tiempo.rename(columns={"año": "anio"})
        df = df.merge(t, how="left", on="id_tiempo")
    if not edificio.empty and "id_edificio" in df.columns:
        e = edificio.rename(columns={"nombre": "nombre_edificio", "tipo": "tipo_edificio"})
        df = df.merge(e, how="left", on="id_edificio")
    if not ambiente.empty and "id_ambiente" in df.columns:
        a = ambiente.rename(columns={"nombre": "nombre_ambiente", "tipo": "tipo_ambiente"})
        df = df.merge(a, how="left", on="id_ambiente")
    if not ocupacion.empty and "id_ocupacion" in df.columns:
        o = ocupacion.rename(columns={"cantidad_personas": "ocupacion", "porcentaje": "ocupacion_pct"})
        df = df.merge(o, how="left", on="id_ocupacion")

    for col in ["consumo_kwh", "demanda_pico_kw", "factor_potencia", "temperatura", "eficiencia", "ocupacion"]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    if "mes" in df.columns:
        df["mes"] = pd.to_numeric(df["mes"], errors="coerce").fillna(1).astype(int)
        meses = {1:"Ene",2:"Feb",3:"Mar",4:"Abr",5:"May",6:"Jun",7:"Jul",8:"Ago",9:"Set",10:"Oct",11:"Nov",12:"Dic"}
        df["nombre_mes"] = df["mes"].map(meses)
        df["periodo"] = df.get("anio", 2026).astype(str) + "-" + df["mes"].astype(str).str.zfill(2)
    if "hora" in df.columns:
        df["franja_horaria"] = pd.cut(pd.to_numeric(df["hora"], errors="coerce"), bins=[-1, 11, 17, 24], labels=["Mañana", "Tarde", "Noche"])

    if pred.empty:
        pred = pd.DataFrame()
    else:
        for col in ["consumo_kwh", "pred_consumo_kwh", "riesgo_sobreconsumo_prob", "ocupacion", "temperatura", "demanda_pico_kw", "factor_potencia"]:
            if col in pred.columns:
                pred[col] = pd.to_numeric(pred[col], errors="coerce")
    return df, pred


@st.cache_data(ttl=20, show_spinner=False)
def load_web_events() -> pd.DataFrame:
    if not configured():
        return sample_web_events()
    web = fetch_table("web_eventos", limit=50000, order="fecha_hora.asc")
    if web.empty:
        return sample_web_events()
    if "fecha_hora" in web.columns:
        web["fecha_hora"] = pd.to_datetime(web["fecha_hora"], errors="coerce")
    if "fecha" in web.columns:
        web["fecha"] = pd.to_datetime(web["fecha"], errors="coerce").dt.date
    if "etapa_numero" in web.columns:
        web["etapa_numero"] = pd.to_numeric(web["etapa_numero"], errors="coerce").fillna(0).astype(int)
    if "tiempo_seg" in web.columns:
        web["tiempo_seg"] = pd.to_numeric(web["tiempo_seg"], errors="coerce")
    return web


def sample_web_events() -> pd.DataFrame:
    data = [
        ["2026-07-01 08:00", "U001", "S001", "login_usuario", 0, "Login", "Desktop", "exitoso", 0],
        ["2026-07-01 08:01", "U001", "S001", "carga_archivo", 1, "Fuentes de datos", "Desktop", "exitoso", 70],
        ["2026-07-01 08:02", "U001", "S001", "validacion_staging", 2, "Staging Area", "Desktop", "exitoso", 120],
        ["2026-07-01 08:03", "U001", "S001", "etl_completado", 3, "Proceso ETL", "Desktop", "exitoso", 190],
        ["2026-07-01 08:04", "U001", "S001", "modelo_copo_nieve_completado", 4, "Data Warehouse", "Desktop", "exitoso", 260],
        ["2026-07-01 08:05", "U001", "S001", "colab_ia_completado", 5, "Capa IA", "Desktop", "exitoso", 330],
        ["2026-07-01 08:07", "U001", "S001", "visualizacion_dashboard", 7, "Dashboard Streamlit", "Desktop", "completado", 480],
        ["2026-07-01 09:00", "U002", "S002", "login_usuario", 0, "Login", "Mobile", "exitoso", 0],
        ["2026-07-01 09:02", "U002", "S002", "carga_archivo", 1, "Fuentes de datos", "Mobile", "exitoso", 100],
        ["2026-07-01 09:03", "U002", "S002", "abandono_flujo", 2, "Staging Area", "Mobile", "incompleto", 155],
        ["2026-07-01 10:00", "U003", "S003", "login_usuario", 0, "Login", "Tablet", "exitoso", 0],
        ["2026-07-01 10:05", "U003", "S003", "error_proceso", 3, "Proceso ETL", "Tablet", "error", 230],
    ]
    df = pd.DataFrame(data, columns=["fecha_hora", "usuario_id", "sesion_id", "evento", "etapa_numero", "etapa_nombre", "dispositivo", "resultado", "tiempo_seg"])
    df["fecha_hora"] = pd.to_datetime(df["fecha_hora"])
    df["fecha"] = df["fecha_hora"].dt.date
    df["navegador"] = "Demo"
    df["url_pagina"] = "/"
    return df
