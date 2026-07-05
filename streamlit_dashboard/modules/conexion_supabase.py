import os
import requests
import pandas as pd
import streamlit as st


MESES = {
    1: "Ene", 2: "Feb", 3: "Mar", 4: "Abr",
    5: "May", 6: "Jun", 7: "Jul", 8: "Ago",
    9: "Set", 10: "Oct", 11: "Nov", 12: "Dic"
}


def _get_secret(name: str, default: str = "") -> str:
    try:
        if name in st.secrets:
            return str(st.secrets[name]).strip()
    except Exception:
        pass
    return os.getenv(name, default).strip()


def _supabase_url() -> str:
    return _get_secret("SUPABASE_URL").rstrip("/")


def _supabase_key() -> str:
    return _get_secret("SUPABASE_KEY") or _get_secret("SUPABASE_API_KEY")


def configured() -> bool:
    return bool(_supabase_url() and _supabase_key())


def _headers() -> dict:
    key = _supabase_key()
    return {
        "apikey": key,
        "Authorization": f"Bearer {key}",
        "Content-Type": "application/json",
        "Accept": "application/json",
    }


@st.cache_data(ttl=20, show_spinner=False)
def _fetch_table(table_name: str, limit: int = 10000) -> pd.DataFrame:
    if not configured():
        return pd.DataFrame()

    url = f"{_supabase_url()}/rest/v1/{table_name}"
    params = {
        "select": "*",
        "limit": str(limit)
    }

    try:
        response = requests.get(
            url,
            headers=_headers(),
            params=params,
            timeout=25
        )

        if response.status_code >= 400:
            st.warning(
                f"No se pudo leer la tabla {table_name}. "
                f"Estado: {response.status_code}. Respuesta: {response.text[:200]}"
            )
            return pd.DataFrame()

        data = response.json()
        return pd.DataFrame(data)

    except Exception as e:
        st.warning(f"Error conectando con Supabase en {table_name}: {e}")
        return pd.DataFrame()


def _demo_energy_data():
    data = []
    edificios = ["Edificio 1", "Edificio 4", "Pabellón B", "Edificio 7", "Laboratorio Central"]
    ambientes = ["Aula", "Biblioteca", "Oficina", "Laboratorio", "Auditorio"]

    for i in range(1, 41):
        mes = ((i - 1) % 4) + 1
        hora = [7, 8, 10, 13, 17, 19, 21][i % 7]
        consumo = 120 + (i * 11.5)
        demanda = 22 + (i * 0.7)
        fp = 0.80 + ((i % 10) * 0.01)

        data.append({
            "hora": hora,
            "mes": mes,
            "anio": 2026,
            "nombre_mes": MESES[mes],
            "periodo": f"2026-{mes:02d}",
            "nombre_edificio": edificios[i % len(edificios)],
            "nombre_ambiente": f"Ambiente {i}",
            "tipo_ambiente": ambientes[i % len(ambientes)],
            "ocupacion": 30 + (i % 70),
            "temperatura": 18 + (i % 12),
            "demanda_pico_kw": round(demanda, 2),
            "factor_potencia": round(fp, 3),
            "consumo_kwh": round(consumo, 2),
            "eficiencia": round(consumo / max(demanda, 1), 2),
            "riesgo_sobreconsumo": 1 if i % 3 == 0 else 0,
        })

    df = pd.DataFrame(data)

    pred = pd.DataFrame({
        "periodo": ["2026-03", "2026-04"],
        "pred_consumo_kwh": [10950, 12010],
        "riesgo_sobreconsumo_pred": ["Bajo", "Medio"]
    })

    return df, pred


def _demo_web_events():
    return pd.DataFrame([
        {
            "fecha_hora": "2026-07-04 17:02:59",
            "fecha": "2026-07-04",
            "usuario_id": "Ingeniero1",
            "sesion_id": "S001",
            "evento": "login_usuario",
            "etapa_numero": 0,
            "etapa_nombre": "Login",
            "dispositivo": "Desktop",
            "navegador": "Chrome",
            "resultado": "exitoso",
            "tiempo_seg": 0,
            "url_pagina": "/",
        },
        {
            "fecha_hora": "2026-07-04 17:04:59",
            "fecha": "2026-07-04",
            "usuario_id": "Ingeniero1",
            "sesion_id": "S001",
            "evento": "carga_archivo",
            "etapa_numero": 1,
            "etapa_nombre": "Fuentes de datos",
            "dispositivo": "Desktop",
            "navegador": "Chrome",
            "resultado": "exitoso",
            "tiempo_seg": 90,
            "url_pagina": "/",
        },
        {
            "fecha_hora": "2026-07-04 17:39:59",
            "fecha": "2026-07-04",
            "usuario_id": "Usuario2",
            "sesion_id": "S002",
            "evento": "abandono_flujo",
            "etapa_numero": 2,
            "etapa_nombre": "Staging Area",
            "dispositivo": "Mobile",
            "navegador": "Chrome",
            "resultado": "incompleto",
            "tiempo_seg": 120,
            "url_pagina": "/",
        },
        {
            "fecha_hora": "2026-07-04 17:45:59",
            "fecha": "2026-07-04",
            "usuario_id": "Usuario3",
            "sesion_id": "S003",
            "evento": "visualizacion_dashboard",
            "etapa_numero": 7,
            "etapa_nombre": "Dashboard Streamlit",
            "dispositivo": "Tablet",
            "navegador": "Edge",
            "resultado": "completado",
            "tiempo_seg": 300,
            "url_pagina": "/",
        },
    ])


def load_energy_data():
    fact = _fetch_table("fact_consumo_energetico")
    pred = _fetch_table("fact_consumo_energetico_pred")

    if fact.empty:
        return _demo_energy_data()

    dim_tiempo = _fetch_table("dimtiempo")
    dim_edificio = _fetch_table("dimedificio")
    dim_ambiente = _fetch_table("dimambiente")
    dim_ocupacion = _fetch_table("dimocupacion")

    df = fact.copy()

    if not dim_tiempo.empty and "id_tiempo" in df.columns:
        df = df.merge(dim_tiempo, on="id_tiempo", how="left")

    if not dim_edificio.empty and "id_edificio" in df.columns:
        dim_edificio = dim_edificio.rename(columns={
            "nombre": "nombre_edificio",
            "tipo": "tipo_edificio"
        })
        df = df.merge(dim_edificio, on="id_edificio", how="left")

    if not dim_ambiente.empty and "id_ambiente" in df.columns:
        dim_ambiente = dim_ambiente.rename(columns={
            "nombre": "nombre_ambiente",
            "tipo": "tipo_ambiente"
        })
        df = df.merge(dim_ambiente, on="id_ambiente", how="left")

    if not dim_ocupacion.empty and "id_ocupacion" in df.columns:
        dim_ocupacion = dim_ocupacion.rename(columns={
            "cantidad_personas": "ocupacion",
            "porcentaje": "porcentaje_ocupacion"
        })
        df = df.merge(dim_ocupacion, on="id_ocupacion", how="left")

    for col in ["consumo_kwh", "demanda_pico_kw", "factor_potencia", "temperatura", "eficiencia"]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce").fillna(0)

    if "riesgo_sobreconsumo" in df.columns:
        df["riesgo_sobreconsumo"] = pd.to_numeric(df["riesgo_sobreconsumo"], errors="coerce").fillna(0)

    if "mes" in df.columns:
        df["mes"] = pd.to_numeric(df["mes"], errors="coerce").fillna(1).astype(int)
        df["nombre_mes"] = df["mes"].map(MESES).fillna("Mes")

    if "anio" in df.columns and "mes" in df.columns:
        df["periodo"] = df["anio"].astype(str) + "-" + df["mes"].astype(str).str.zfill(2)

    if "hora" in df.columns:
        df["hora"] = pd.to_numeric(df["hora"], errors="coerce").fillna(0).astype(int)

    if pred.empty:
        pred = pd.DataFrame({
            "periodo": sorted(df["periodo"].dropna().unique())[-2:] if "periodo" in df.columns else ["2026-01"],
            "pred_consumo_kwh": [df["consumo_kwh"].sum()],
        })
    else:
        if "pred_consumo_kwh" in pred.columns:
            pred["pred_consumo_kwh"] = pd.to_numeric(pred["pred_consumo_kwh"], errors="coerce").fillna(0)
        elif "consumo_kwh" in pred.columns:
            pred["pred_consumo_kwh"] = pd.to_numeric(pred["consumo_kwh"], errors="coerce").fillna(0)

        if "periodo" not in pred.columns:
            if "fecha_proceso" in pred.columns:
                pred["periodo"] = pd.to_datetime(pred["fecha_proceso"], errors="coerce").dt.strftime("%Y-%m")
            elif "mes" in pred.columns and "anio" in pred.columns:
                pred["periodo"] = pred["anio"].astype(str) + "-" + pred["mes"].astype(str).str.zfill(2)
            else:
                pred["periodo"] = "2026-01"

    return df, pred


def load_web_events():
    web = _fetch_table("web_eventos")

    if web.empty:
        return _demo_web_events()

    if "fecha_hora" in web.columns:
        web["fecha_hora"] = pd.to_datetime(web["fecha_hora"], errors="coerce")

    if "fecha" not in web.columns or web["fecha"].isna().all():
        if "fecha_hora" in web.columns:
            web["fecha"] = web["fecha_hora"].dt.date.astype(str)
        else:
            web["fecha"] = pd.Timestamp.today().date().isoformat()

    if "sesion_id" not in web.columns and "session_id" in web.columns:
        web["sesion_id"] = web["session_id"]

    columnas_necesarias = {
        "usuario_id": "Usuario",
        "sesion_id": "Sesion",
        "evento": "evento",
        "etapa_numero": 0,
        "etapa_nombre": "Sin etapa",
        "dispositivo": "Desktop",
        "navegador": "Navegador",
        "resultado": "exitoso",
        "tiempo_seg": 0,
        "url_pagina": "/",
    }

    for col, default in columnas_necesarias.items():
        if col not in web.columns:
            web[col] = default

    web["tiempo_seg"] = pd.to_numeric(web["tiempo_seg"], errors="coerce").fillna(0)
    web["etapa_numero"] = pd.to_numeric(web["etapa_numero"], errors="coerce").fillna(0).astype(int)

    return web