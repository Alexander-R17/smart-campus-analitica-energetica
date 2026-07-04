import base64
import time
from pathlib import Path
import pandas as pd
import streamlit as st
import plotly.express as px

from modules.conexion_supabase import load_energy_data, load_web_events, configured
from modules.componentes import (
    inject_css,
    section,
    fmt_kwh,
    fig_line,
    fig_bar,
    fig_pie,
)

# ============================================================
# CONFIGURACIÓN GENERAL
# ============================================================

st.set_page_config(page_title="Smart Campus BI", layout="wide", page_icon="📊")
inject_css()

# CSS adicional para aproximar el dashboard al diseño Power BI original.
st.markdown(
    """
    <style>
    :root {
        --azul: #061b55;
        --azul2: #09266e;
        --borde: #061b55;
        --gris: #f5f7fb;
    }

    .block-container {
        padding-top: 1.05rem !important;
        padding-left: 0.55rem !important;
        padding-right: 0.55rem !important;
        max-width: 1280px !important;
    }

    div[data-testid="column"]:has(.header-title-marker),
    div[data-testid="column"]:has(.header-controls-marker) {
        background: var(--azul) !important;
        min-height: 86px !important;
        padding: 9px 16px 7px 16px !important;
        box-sizing: border-box !important;
    }

    .smart-header-left-only {
        display: flex;
        align-items: center;
        gap: 16px;
        min-width: 0;
        min-height: 66px;
    }

    .smart-header-logo {
        width: 64px;
        height: auto;
        object-fit: contain;
        flex: 0 0 auto;
    }

    .smart-header-title {
        font-size: 24px;
        font-weight: 900;
        line-height: 1.12;
        letter-spacing: .2px;
        text-transform: uppercase;
    }

    .header-subnote { display: none !important; }

    div[data-testid="column"]:has(.header-controls-marker) .stButton > button {
        min-height: 28px !important;
        height: 30px !important;
        padding: 3px 8px !important;
        border-radius: 7px !important;
        font-size: 12px !important;
        font-weight: 900 !important;
        box-shadow: none !important;
    }

    div[data-testid="column"]:has(.header-controls-marker) div[data-testid="stHorizontalBlock"] .stButton > button {
        background: transparent !important;
        color: white !important;
        border: 0px solid transparent !important;
        font-size: 29px !important;
        line-height: 1 !important;
        padding: 0 !important;
        -webkit-text-stroke: 1.1px #061b55;
        text-shadow: 0 0 1px white;
    }

    div[data-testid="column"]:has(.header-controls-marker) div[data-testid="stHorizontalBlock"] .stButton > button:disabled {
        opacity: .25 !important;
        color: white !important;
    }

    .page-indicator {
        color: white;
        font-size: 11px;
        font-weight: 900;
        text-align: center;
        padding-top: 7px;
        white-space: nowrap;
    }

    [data-testid="stSidebar"] {
        background: #eef3fb;
        border-right: 1px solid #d8e0ef;
        min-width: 220px !important;
        max-width: 220px !important;
    }

    [data-testid="stSidebar"] .stButton > button {
        width: 100%;
        border: 1.5px solid var(--azul) !important;
        border-radius: 0px !important;
        background: white !important;
        color: var(--azul) !important;
        font-size: 11px !important;
        font-weight: 800 !important;
        height: 38px !important;
        text-transform: uppercase;
    }

    [data-testid="stSidebar"] .stButton > button:hover {
        background: var(--azul) !important;
        color: white !important;
    }

    [data-testid="stSidebar"] .stButton > button[kind="primary"],
    [data-testid="stSidebar"] .stButton > button[data-testid="baseButton-primary"] {
        background: var(--azul) !important;
        color: white !important;
        border-color: var(--azul) !important;
    }


    .olap-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px 10px;
        margin-top: 6px;
        margin-bottom: 8px;
    }

    .olap-link {
        border: 1.5px solid var(--azul) !important;
        background: white !important;
        color: var(--azul) !important;
        text-align: center;
        display: block;
        padding: 10px 5px;
        text-decoration: none !important;
        font-size: 11px;
        font-weight: 850;
        text-transform: uppercase;
        min-height: 18px;
    }

    .olap-link:hover, .olap-link-active {
        background: var(--azul) !important;
        color: white !important;
    }

    .olap-wide {
        grid-column: span 2;
    }

    .filter-title {
        background: var(--azul);
        color: white;
        font-size: 13px;
        font-weight: 900;
        border-radius: 8px;
        padding: 9px 10px;
        margin: 8px 0 12px 0;
        text-align: left;
    }

    .olap-active {
        background: #061b55;
        color: white;
        border: 1px solid #061b55;
        padding: 7px;
        margin-top: 7px;
        font-size: 11px;
        text-align: center;
        font-weight: 800;
    }

    .power-section {
        background: var(--azul);
        color: white;
        text-align: center;
        font-weight: 900;
        font-size: 14px;
        padding: 8px 4px;
        margin-top: 0px;
        margin-bottom: 7px;
        border: 1px solid var(--azul);
        letter-spacing: .2px;
    }

    .pbi-box {
        border: 2px solid var(--borde);
        background: white;
        padding: 8px 8px 6px 8px;
        min-height: 218px;
        box-sizing: border-box;
    }

    .pbi-box-small {
        border: 2px solid var(--borde);
        background: white;
        padding: 8px 8px 4px 8px;
        min-height: 188px;
        box-sizing: border-box;
    }

    .pbi-title {
        text-align: center;
        font-weight: 900;
        color: var(--azul);
        font-size: 13px;
        line-height: 1.15;
        min-height: 30px;
    }

    .pbi-question {
        text-align: center;
        color: #222;
        font-size: 10.5px;
        line-height: 1.15;
        min-height: 26px;
        margin-top: 2px;
    }

    .pbi-value {
        text-align: center;
        font-weight: 900;
        color: #003fce;
        font-size: 18px;
        margin-top: 4px;
        margin-bottom: 2px;
    }

    .pbi-value-alert {
        color: #e14123;
    }

    .pbi-note {
        font-size: 11px;
        text-align: center;
        line-height: 1.2;
        color: #1d1d1d;
        min-height: 28px;
    }

    .top-note {
        font-size: 11px;
        color: #7c869a;
        text-align: right;
    }

    .metric-frame {
        border: 2px solid var(--borde);
        background: white;
        padding: 8px;
        min-height: 74px;
    }

    .metric-label {
        color: var(--azul);
        font-weight: 700;
        font-size: 12px;
        margin-bottom: 2px;
    }

    .metric-number {
        color: var(--azul);
        font-size: 32px;
        font-weight: 500;
        line-height: 1;
    }

    div[data-testid="stHorizontalBlock"] {
        gap: 0.60rem;
    }

    div[data-testid="stRadio"] label {
        display: none !important;
    }

    div[data-testid="stRadio"] [role="radiogroup"] {
        display: flex !important;
        justify-content: flex-end !important;
        gap: 6px !important;
        background: transparent !important;
    }

    div[data-testid="stRadio"] [role="radio"] {
        border: 1px solid #c8d2e8 !important;
        padding: 6px 12px !important;
        background: #f3f5fa !important;
        color: #061b55 !important;
        font-size: 12px !important;
        font-weight: 800 !important;
    }

    .stTabs [data-baseweb="tab-list"] {
        gap: 6px;
        border-bottom: 1px solid #c8d2e8;
    }

    .stTabs [data-baseweb="tab"] {
        background: #f3f5fa;
        border-radius: 0px;
        padding: 8px 18px;
        border: 1px solid #e0e5f1;
        color: var(--azul);
        font-weight: 700;
    }

    .stTabs [aria-selected="true"] {
        border-bottom: 3px solid var(--azul) !important;
        background: white !important;
    }

    .caption-center {
        font-size: 10px;
        color: #6b6f7d;
        text-align:center;
    }

    /* Marco real de cada visual, similar a Power BI. */
    div[data-testid="stVerticalBlockBorderWrapper"] {
        border: 2px solid #061b55 !important;
        border-radius: 0px !important;
        background: #ffffff !important;
        padding: 7px 8px 5px 8px !important;
        box-shadow: none !important;
    }

    div[data-testid="stVerticalBlockBorderWrapper"] [data-testid="stMarkdownContainer"] p {
        margin-bottom: 0.1rem !important;
    }

    .js-plotly-plot .plotly .modebar {
        display: none !important;
    }



    /* ===== AJUSTE FINAL DEL ENCABEZADO AZUL =====
       Cubre título, botón Actualizar y flechas dentro de una sola franja azul.
       No usa posición fija ni absoluta para que no se pierda al hacer zoom. */
    div[data-testid="stHorizontalBlock"]:has(.header-title-marker):has(.header-controls-marker) {
        background: var(--azul) !important;
        border: 1px solid var(--azul) !important;
        padding: 12px 16px 12px 16px !important;
        margin-top: 34px !important;
        margin-bottom: 3px !important;
        min-height: 96px !important;
        align-items: center !important;
        box-sizing: border-box !important;
        overflow: visible !important;
    }

    div[data-testid="stHorizontalBlock"]:has(.header-title-marker):has(.header-controls-marker) > div[data-testid="column"] {
        background: transparent !important;
        padding: 0 !important;
        min-height: auto !important;
        box-sizing: border-box !important;
    }

    div[data-testid="column"]:has(.header-title-marker),
    div[data-testid="column"]:has(.header-controls-marker) {
        background: transparent !important;
        padding: 0 !important;
        min-height: auto !important;
    }

    .smart-header-left-only {
        min-height: 72px !important;
        color: #ffffff !important;
    }

    .smart-header-title {
        color: #ffffff !important;
        font-size: clamp(18px, 1.8vw, 25px) !important;
        line-height: 1.12 !important;
        overflow-wrap: anywhere;
    }

    .smart-header-logo {
        width: clamp(42px, 5vw, 64px) !important;
    }

    div[data-testid="column"]:has(.header-controls-marker) .stButton > button,
    div[data-testid="stHorizontalBlock"]:has(.header-title-marker):has(.header-controls-marker) .stButton > button {
        box-shadow: none !important;
    }

    div[data-testid="column"]:has(.header-controls-marker) > div {
        color: white !important;
    }

    /* Botón actualizar dentro del encabezado */
    div[data-testid="column"]:has(.header-controls-marker) .stButton:first-of-type > button {
        background: #ffffff !important;
        color: var(--azul) !important;
        border: 1px solid #ffffff !important;
        font-weight: 900 !important;
    }

    /* Flechas: blancas, transparentes y compactas */
    div[data-testid="column"]:has(.header-controls-marker) div[data-testid="stHorizontalBlock"] .stButton > button {
        background: transparent !important;
        border: 0 !important;
        color: #ffffff !important;
        font-size: 25px !important;
        font-weight: 900 !important;
        min-height: 28px !important;
        padding: 0 !important;
    }

    div[data-testid="column"]:has(.header-controls-marker) div[data-testid="stHorizontalBlock"] .stButton > button:disabled {
        opacity: .25 !important;
        color: #ffffff !important;
    }

    .page-indicator {
        color: #ffffff !important;
        padding-top: 6px !important;
        font-size: 11px !important;
    }

    /* Compactar espacio entre encabezado azul y bloque de página */
    div[data-testid="stHorizontalBlock"]:has(.header-title-marker):has(.header-controls-marker) + div {
        margin-top: 0px !important;
        padding-top: 0px !important;
    }

    div[data-testid="stVerticalBlock"] > div:has(.power-section) {
        margin-top: 0px !important;
        padding-top: 0px !important;
    }

    </style>
    """,
    unsafe_allow_html=True,
)


# ============================================================
# ENCABEZADO SUPERIOR + NAVEGACIÓN POR FLECHAS SIN CAMBIAR URL
# ============================================================

PAGES = ["Página 1", "Página 2", "Página 3"]

if "page_num" not in st.session_state:
    st.session_state["page_num"] = 1

if "olap_mode" not in st.session_state:
    st.session_state["olap_mode"] = "NORMAL"

page_num = max(1, min(3, int(st.session_state.get("page_num", 1))))
st.session_state["page_num"] = page_num
pagina_actual = PAGES[page_num - 1]


def _logo_base64():
    logo = Path(__file__).resolve().parent / "assets" / "logo.png"
    if logo.exists():
        return "data:image/png;base64," + base64.b64encode(logo.read_bytes()).decode("utf-8")
    return ""


def render_main_header():
    logo_src = _logo_base64()
    logo_html = f"<img class='smart-header-logo' src='{logo_src}'>" if logo_src else ""

    h_left, h_right = st.columns([5.2, 1.6], gap="small")

    with h_left:
        st.markdown(
            f"""
            <div class="header-title-marker"></div>
            <div class="smart-header-left-only">
                {logo_html}
                <div class="smart-header-title">
                    PLATAFORMA INTEGRAL DE ANALÍTICA ENERGÉTICA - SMART<br>
                    CAMPUS UNIVERSIDAD NUEVA ESPERANZA
                </div>
            </div>
            """,
            unsafe_allow_html=True,
        )

    with h_right:
        st.markdown("<div class='header-controls-marker'></div>", unsafe_allow_html=True)
        if st.button("🔄 Actualizar datos", key="btn_header_refresh", width="stretch"):
            st.cache_data.clear()
            st.rerun()

        a1, a2, a3 = st.columns([1, 1.25, 1], gap="small")
        with a1:
            if st.button("◀", key="btn_page_prev", disabled=page_num <= 1, width="stretch"):
                st.session_state["page_num"] = max(1, page_num - 1)
                st.rerun()
        with a2:
            st.markdown(f"<div class='page-indicator'>Pág. {page_num}/3</div>", unsafe_allow_html=True)
        with a3:
            if st.button("▶", key="btn_page_next", disabled=page_num >= 3, width="stretch"):
                st.session_state["page_num"] = min(3, page_num + 1)
                st.rerun()


render_main_header()


# ============================================================
# FUNCIONES DE UI / GRÁFICOS
# ============================================================

_chart_counter = 0


def smart_plotly_chart(fig, height=135, key=None, showlegend=None):
    """Evita StreamlitDuplicateElementId y normaliza tamaños."""
    global _chart_counter
    _chart_counter += 1

    if fig is None:
        fig = px.line()

    fig.update_layout(
        height=height,
        margin=dict(l=6, r=6, t=6, b=6),
        font=dict(size=9),
        paper_bgcolor="white",
        plot_bgcolor="white",
    )

    if showlegend is not None:
        fig.update_layout(showlegend=showlegend)

    st.plotly_chart(fig, width="stretch", key=key or f"plotly_smartcampus_{_chart_counter}")


def pbi_section(title):
    st.markdown(f"<div class='power-section'>{title}</div>", unsafe_allow_html=True)


def kpi_chart_box(title, question, value=None, fig=None, key=None, alert=False, note=None, height=112, min_spacer=True, box_height=235):
    # Marco completo del KPI. Si no hay gráfico, se agrega espacio interno para que
    # Diagnóstica y Prescriptiva tengan la misma proporción visual que las demás cajas.
    with st.container(border=True, height=box_height):
        st.markdown(f"<div class='pbi-title'>{title}</div>", unsafe_allow_html=True)
        st.markdown(f"<div class='pbi-question'>{question}</div>", unsafe_allow_html=True)
        if value is not None:
            cls = "pbi-value pbi-value-alert" if alert else "pbi-value"
            st.markdown(f"<div class='{cls}'>{value}</div>", unsafe_allow_html=True)
        if note:
            st.markdown(f"<div class='pbi-note'>{note}</div>", unsafe_allow_html=True)
        if fig is not None:
            smart_plotly_chart(fig, height=height, key=key, showlegend=False)
        elif min_spacer:
            st.markdown(f"<div style='height:{height + 22}px'></div>", unsafe_allow_html=True)


def chart_box(title, subtitle, fig, key, height=145, horizontal_caption=None, box_height=None):
    with st.container(border=True):
        st.markdown(f"<div class='pbi-title'>{title}</div>", unsafe_allow_html=True)
        if subtitle:
            st.markdown(f"<div class='pbi-question'>{subtitle}</div>", unsafe_allow_html=True)
        smart_plotly_chart(fig, height=height, key=key, showlegend=False)
        if horizontal_caption:
            st.markdown(f"<div class='caption-center'>{horizontal_caption}</div>", unsafe_allow_html=True)

def metric_frame(label, value):
    st.markdown(
        f"""
        <div class="metric-frame">
            <div class="metric-label">{label}</div>
            <div class="metric-number">{value}</div>
        </div>
        """,
        unsafe_allow_html=True,
    )


def safe_line(data, x, y):
    if data is None or data.empty or x not in data.columns or y not in data.columns:
        return px.line()
    return fig_line(data, x, y)


def safe_bar(data, x, y, horizontal=False):
    if data is None or data.empty or x not in data.columns or y not in data.columns:
        return px.bar()
    return fig_bar(data, x, y, horizontal=horizontal)


def safe_pie(data, names, values):
    if data is None or data.empty or names not in data.columns or values not in data.columns:
        return px.pie()
    return fig_pie(data, names, values)


def set_olap_mode(mode):
    st.session_state["olap_mode"] = mode


if "olap_mode" not in st.session_state:
    st.session_state["olap_mode"] = "NORMAL"

# ============================================================
# CARGA DE DATOS
# ============================================================

df, pred = load_energy_data()
web = load_web_events()

# Normalización mínima de columnas.
if not df.empty:
    for col in ["consumo_kwh", "demanda_pico_kw", "factor_potencia", "temperatura", "eficiencia", "ocupacion"]:
        if col in df.columns:
            df[col] = pd.to_numeric(df[col], errors="coerce")
    if "riesgo_sobreconsumo" in df.columns:
        df["riesgo_sobreconsumo"] = pd.to_numeric(df["riesgo_sobreconsumo"], errors="coerce").fillna(0)

    # El filtro MES debe mostrarse por nombre, no por número.
    # Se fuerza una etiqueta textual estable para filtros y gráficos.
    MES_NOMBRES = {
        1: "Ene", 2: "Feb", 3: "Mar", 4: "Abr", 5: "May", 6: "Jun",
        7: "Jul", 8: "Ago", 9: "Set", 10: "Oct", 11: "Nov", 12: "Dic"
    }
    MES_ORDEN = list(MES_NOMBRES.values())
    if "mes" in df.columns:
        df["mes"] = pd.to_numeric(df["mes"], errors="coerce").astype("Int64")
        df["nombre_mes"] = df["mes"].map(MES_NOMBRES)
    elif "nombre_mes" not in df.columns:
        df["nombre_mes"] = "Sin mes"
    df["nombre_mes"] = df["nombre_mes"].astype(str)
    df["nombre_mes"] = pd.Categorical(df["nombre_mes"], categories=MES_ORDEN, ordered=True)

if not web.empty and "fecha" in web.columns:
    web["fecha"] = pd.to_datetime(web["fecha"], errors="coerce").dt.date.astype(str)

# ============================================================
# SIDEBAR: FILTROS + OLAP REAL
# ============================================================

with st.sidebar:
    st.markdown('<div class="filter-title">🔎 FILTROS DIMENSIONALES</div>', unsafe_allow_html=True)

    anios = sorted([int(x) for x in df.get("anio", pd.Series([2026])).dropna().unique()]) if not df.empty else [2026]
    if not df.empty and "nombre_mes" in df.columns:
        orden_meses = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Set", "Oct", "Nov", "Dic"]
        meses = [m for m in orden_meses if m in df["nombre_mes"].astype(str).dropna().unique().tolist()]
    else:
        meses = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Set", "Oct", "Nov", "Dic"]
    edificios = sorted(df.get("nombre_edificio", pd.Series(dtype=str)).dropna().unique()) if not df.empty else []
    ambientes = sorted(df.get("tipo_ambiente", pd.Series(dtype=str)).dropna().unique()) if not df.empty else []
    riesgo_opts = ["Todas", "Alto", "Medio", "Bajo"]

    f_anio = st.selectbox("AÑO", ["Todas"] + anios)
    f_mes = st.selectbox("MES", ["Todas"] + meses)
    f_edificio = st.selectbox("EDIFICIO", ["Todas"] + edificios)
    f_ambiente = st.selectbox("AMBIENTE", ["Todas"] + ambientes)
    f_riesgo = st.selectbox("RIESGO ENERGÉTICO", riesgo_opts)

    if not df.empty and "hora" in df.columns:
        hora_min = int(df["hora"].min())
        hora_max = int(df["hora"].max())
    else:
        hora_min, hora_max = 7, 21

    if hora_min < hora_max:
        f_hora = st.slider("HORA", min_value=hora_min, max_value=hora_max, value=(hora_min, hora_max))
    else:
        st.caption(f"HORA: {hora_min}")
        f_hora = (hora_min, hora_max)

    if not df.empty and "consumo_kwh" in df.columns:
        cmin = float(df["consumo_kwh"].min())
        cmax = float(df["consumo_kwh"].max())
    else:
        cmin, cmax = 0.0, 1.0

    if cmin < cmax:
        f_consumo = st.slider(
            "CONSUMO MÍNIMO (kWh)",
            min_value=float(round(cmin, 2)),
            max_value=float(round(cmax, 2)),
            value=(float(round(cmin, 2)), float(round(cmax, 2))),
        )
    else:
        st.caption(f"CONSUMO: {round(cmin, 2)} kWh")
        f_consumo = (cmin, cmax)

    st.markdown('<div class="filter-title">⚙ OPERACIONES OLAP</div>', unsafe_allow_html=True)

    def olap_button(label, mode, key):
        active = st.session_state.get("olap_mode", "NORMAL") == mode
        if st.button(label, key=key, type="primary" if active else "secondary", width="stretch"):
            st.session_state["olap_mode"] = mode
            st.rerun()

    b1, b2 = st.columns(2)
    with b1:
        olap_button("SLICE", "SLICE", "olap_slice")
        olap_button("DICE", "DICE", "olap_dice")
    with b2:
        olap_button("DRILL DOWN", "DRILL DOWN", "olap_drill")
        olap_button("ROLL UP", "ROLL UP", "olap_roll")
    olap_button("PIVOT", "PIVOT", "olap_pivot")
    olap_button("LIMPIAR OLAP", "NORMAL", "olap_normal")

    st.markdown(f"<div class='olap-active'>MODO ACTUAL: {st.session_state.get('olap_mode', 'NORMAL')}</div>", unsafe_allow_html=True)
    st.caption(
        "SLICE filtra el ambiente más crítico; DICE cruza edificio y ambiente críticos; "
        "DRILL DOWN muestra detalle por hora; ROLL UP resume por mes; PIVOT cambia la matriz."
    )

# ============================================================
# APLICACIÓN DE FILTROS Y OPERACIONES OLAP
# ============================================================

fdf = df.copy()

if f_anio != "Todas" and "anio" in fdf.columns:
    fdf = fdf[fdf["anio"] == int(f_anio)]
if f_mes != "Todas" and "nombre_mes" in fdf.columns:
    fdf = fdf[fdf["nombre_mes"].astype(str) == str(f_mes)]
if f_edificio != "Todas" and "nombre_edificio" in fdf.columns:
    fdf = fdf[fdf["nombre_edificio"] == f_edificio]
if f_ambiente != "Todas" and "tipo_ambiente" in fdf.columns:
    fdf = fdf[fdf["tipo_ambiente"] == f_ambiente]
if "hora" in fdf.columns:
    fdf = fdf[(fdf["hora"] >= f_hora[0]) & (fdf["hora"] <= f_hora[1])]
if "consumo_kwh" in fdf.columns:
    fdf = fdf[(fdf["consumo_kwh"] >= f_consumo[0]) & (fdf["consumo_kwh"] <= f_consumo[1])]
if f_riesgo != "Todas":
    if f_riesgo == "Alto" and "riesgo_sobreconsumo" in fdf.columns:
        fdf = fdf[fdf["riesgo_sobreconsumo"] == 1]
    if f_riesgo == "Bajo" and "riesgo_sobreconsumo" in fdf.columns:
        fdf = fdf[fdf["riesgo_sobreconsumo"] == 0]

olap_mode = st.session_state["olap_mode"]

# Operaciones OLAP aplicadas sobre el dataframe filtrado.
if not fdf.empty and olap_mode == "SLICE" and "tipo_ambiente" in fdf.columns:
    top_amb = fdf.groupby("tipo_ambiente")["consumo_kwh"].sum().idxmax()
    fdf = fdf[fdf["tipo_ambiente"] == top_amb]

if not fdf.empty and olap_mode == "DICE" and {"nombre_edificio", "tipo_ambiente"}.issubset(fdf.columns):
    top_pair = (
        fdf.groupby(["nombre_edificio", "tipo_ambiente"])["consumo_kwh"]
        .sum()
        .reset_index()
        .sort_values("consumo_kwh", ascending=False)
        .iloc[0]
    )
    fdf = fdf[(fdf["nombre_edificio"] == top_pair["nombre_edificio"]) & (fdf["tipo_ambiente"] == top_pair["tipo_ambiente"])]

if fdf.empty:
    st.error("No hay datos para los filtros seleccionados.")
    st.stop()

# ============================================================
# KPIs Y AGREGADOS
# ============================================================

consumo_total = fdf["consumo_kwh"].sum() if "consumo_kwh" in fdf.columns else 0
demanda_prom = fdf["demanda_pico_kw"].mean() if "demanda_pico_kw" in fdf.columns else 0
pred_total = pred.get("pred_consumo_kwh", pred.get("consumo_kwh", pd.Series(dtype=float))).sum() if not pred.empty else consumo_total
riesgo_pct = (fdf.get("riesgo_sobreconsumo", pd.Series([0])).mean() * 100)
prom_ocupacion = fdf.get("ocupacion", pd.Series([0])).mean()
prom_temp = fdf.get("temperatura", pd.Series([0])).mean()
prom_fp = fdf.get("factor_potencia", pd.Series([1])).mean()

if riesgo_pct >= 50:
    causa = "Alto riesgo de sobreconsumo"
    accion_principal = "Priorizar zonas críticas"
    recomendacion = "Reducir cargas en ambientes críticos, revisar horarios pico y activar alertas de consumo."
elif prom_fp < 0.90:
    causa = "Factor de potencia bajo"
    accion_principal = "Corregir factor de potencia"
    recomendacion = "Revisar equipos de alto consumo y compensar factor de potencia para reducir pérdidas."
elif prom_ocupacion > 65:
    causa = "Alta ocupación en horas pico"
    accion_principal = "Optimizar horarios de uso"
    recomendacion = "Distribuir la ocupación por franjas horarias y controlar ambientes con mayor demanda."
elif prom_temp > 26:
    causa = "Temperatura elevada en ambientes"
    accion_principal = "Regular climatización"
    recomendacion = "Ajustar climatización y ventilación para evitar consumo adicional por temperatura."
else:
    causa = "Consumo dentro de parámetros"
    accion_principal = "Continuar operaciones normales"
    recomendacion = "Mantener monitoreo, revisar tendencias mensuales y conservar controles operativos actuales."

mes_df = (
    fdf.groupby("nombre_mes", dropna=False, as_index=False)
    .agg(
        consumo_kwh=("consumo_kwh", "sum"),
        demanda_pico_kw=("demanda_pico_kw", "mean"),
        factor_potencia=("factor_potencia", "mean"),
    )
    if "nombre_mes" in fdf.columns
    else pd.DataFrame()
)

amb_df = (
    fdf.groupby("tipo_ambiente", dropna=False, as_index=False)
    .agg(
        consumo_kwh=("consumo_kwh", "sum"),
        demanda_pico_kw=("demanda_pico_kw", "mean"),
        riesgo_sobreconsumo=("riesgo_sobreconsumo", "mean"),
        ocupacion=("ocupacion", "mean"),
    )
    .sort_values("consumo_kwh", ascending=False)
    if "tipo_ambiente" in fdf.columns
    else pd.DataFrame()
)

edif_df = (
    fdf.groupby("nombre_edificio", dropna=False, as_index=False)
    .agg(
        consumo_kwh=("consumo_kwh", "sum"),
        demanda_pico_kw=("demanda_pico_kw", "mean"),
        riesgo_sobreconsumo=("riesgo_sobreconsumo", "mean"),
    )
    .sort_values("consumo_kwh", ascending=False)
    if "nombre_edificio" in fdf.columns
    else pd.DataFrame()
)

hora_df = (
    fdf.groupby("hora", as_index=False)
    .agg(consumo_kwh=("consumo_kwh", "sum"), demanda_pico_kw=("demanda_pico_kw", "mean"))
    if "hora" in fdf.columns
    else pd.DataFrame()
)

# ============================================================
# PÁGINAS
# ============================================================



# ------------------------------------------------------------
# PÁGINA 1
# ------------------------------------------------------------

if pagina_actual == "Página 1":
    pbi_section("KPI's PRINCIPALES — UNA PREGUNTA POR TIPO DE ANÁLISIS")
    k1, k2, k3, k4 = st.columns(4)

    with k1:
        kpi_chart_box(
            "Descriptiva",
            "¿Cuál es el consumo energético total mensual (kWh) por edificio?",
            fmt_kwh(consumo_total),
            safe_line(mes_df, "nombre_mes", "consumo_kwh"),
            "p1_kpi_descriptiva",
            height=112,
        )

    with k2:
        kpi_chart_box(
            "Diagnóstica",
            "¿Por qué se registran mayores consumos energéticos?",
            f"{demanda_prom:.2f} kW",
            None,
            "p1_kpi_diagnostica",
            alert=True,
            note=f"<b>Causa principal:</b><br>{causa}",
            height=112,
        )

    with k3:
        if not pred.empty and "periodo" in pred.columns:
            p = pred.groupby("periodo", as_index=False).agg(pred_consumo_kwh=("pred_consumo_kwh", "sum"))
            fig_pred = safe_line(p, "periodo", "pred_consumo_kwh")
        else:
            fig_pred = safe_line(mes_df, "nombre_mes", "consumo_kwh")

        kpi_chart_box(
            "Predictiva",
            "¿Cuál será el consumo energético proyectado en los próximos meses?",
            fmt_kwh(pred_total),
            fig_pred,
            "p1_kpi_predictiva",
            height=112,
        )

    with k4:
        kpi_chart_box(
            "Prescriptiva",
            "¿Qué acciones deben implementarse para reducir consumo y costos?",
            accion_principal,
            None,
            "p1_kpi_prescriptiva",
            note=recomendacion,
            height=112,
        )

    pbi_section("ANÁLISIS PREDICTIVO — VISUALIZACIONES CLAVE")
    p1, p2, p3, p4 = st.columns(4)

    with p1:
        chart_box(
            "Predicción 1",
            "Consumo energético futuro (kWh)",
            safe_line(mes_df, "nombre_mes", "consumo_kwh"),
            "p1_pred_1",
            horizontal_caption="Pregunta predictiva: ¿Cuál será el consumo energético proyectado?",
        )
    with p2:
        chart_box(
            "Predicción 2",
            "Demanda de ambientes (%)",
            safe_bar(amb_df.head(6), "tipo_ambiente", "ocupacion"),
            "p1_pred_2",
            horizontal_caption="Pregunta predictiva: ¿Qué ambientes presentan mayor demanda?",
        )
    with p3:
        risk_line = (
            fdf.groupby("periodo", as_index=False).agg(riesgo=("riesgo_sobreconsumo", "mean"))
            if "periodo" in fdf.columns
            else mes_df.copy()
        )
        if not risk_line.empty and "riesgo" not in risk_line.columns and "riesgo_sobreconsumo" in risk_line.columns:
            risk_line["riesgo"] = risk_line["riesgo_sobreconsumo"]

        x_col = "periodo" if "periodo" in risk_line.columns else "nombre_mes"
        chart_box(
            "Predicción 3",
            "Riesgo de sobreconsumo",
            safe_line(risk_line, x_col, "riesgo") if "riesgo" in risk_line.columns else px.line(),
            "p1_pred_3",
            horizontal_caption="Pregunta predictiva: ¿Qué zonas presentan mayor riesgo?",
        )
    with p4:
        chart_box(
            "Predicción 4",
            "Periodos de alto consumo (kWh)",
            safe_bar(hora_df.sort_values("consumo_kwh", ascending=False).head(8), "hora", "consumo_kwh"),
            "p1_pred_4",
            horizontal_caption="Pregunta predictiva: ¿Qué periodos tendrán mayor consumo?",
        )

# ------------------------------------------------------------
# PÁGINA 2
# ------------------------------------------------------------

if pagina_actual == "Página 2":
    left, right = st.columns([3.7, 1.25])

    with left:
        pbi_section("ANÁLISIS OLAP Y CONTROL OPERATIVO")
        c1, c2, c3, c4 = st.columns(4)

        with c1:
            chart_box(
                "5. Demanda pico promedio por mes",
                f"{demanda_prom:.1f} kW",
                safe_line(mes_df, "nombre_mes", "demanda_pico_kw"),
                "p2_demanda_mes",
                height=128,
            )
        with c2:
            fp = fdf["factor_potencia"].mean() if "factor_potencia" in fdf.columns else 0
            chart_box(
                "6. Factor de potencia promedio",
                f"{fp:.3f}",
                safe_line(mes_df, "nombre_mes", "factor_potencia"),
                "p2_factor_potencia",
                height=128,
            )
        with c3:
            chart_box(
                "7. Ranking de ambientes por consumo",
                fmt_kwh(amb_df["consumo_kwh"].max() if not amb_df.empty else 0),
                safe_bar(amb_df.head(5), "tipo_ambiente", "consumo_kwh"),
                "p2_ranking_ambientes",
                height=128,
            )
        with c4:
            r = edif_df.copy()
            if not r.empty and "riesgo_sobreconsumo" in r.columns:
                r["riesgo_pct"] = r["riesgo_sobreconsumo"] * 100
            chart_box(
                "8. Ranking de riesgo por edificio (%)",
                f"{riesgo_pct:.1f}%",
                safe_bar(r.head(6), "nombre_edificio", "riesgo_pct") if not r.empty and "riesgo_pct" in r.columns else px.bar(),
                "p2_ranking_riesgo",
                height=128,
            )

        c5, c6 = st.columns([1.25, 1])
        with c5:
            pbi_section("9. Relación temperatura vs consumo")
            fig_scatter = px.scatter(
                fdf,
                x="temperatura",
                y="consumo_kwh",
                color="tipo_ambiente" if "tipo_ambiente" in fdf.columns else None,
            )
            smart_plotly_chart(fig_scatter, height=270, key="p2_temp_consumo", showlegend=False)

        with c6:
            pbi_section("10. Matriz hora-mes / consumo energético")
            if olap_mode == "PIVOT":
                pivot = (
                    pd.pivot_table(fdf, values="consumo_kwh", index="hora", columns="nombre_mes", aggfunc="sum", fill_value=0)
                    if "nombre_mes" in fdf.columns and "hora" in fdf.columns
                    else pd.DataFrame()
                )
            else:
                pivot = (
                    pd.pivot_table(fdf, values="consumo_kwh", index="nombre_mes", columns="hora", aggfunc="sum", fill_value=0)
                    if "nombre_mes" in fdf.columns and "hora" in fdf.columns
                    else pd.DataFrame()
                )

            if not pivot.empty:
                st.dataframe(pivot.style.format("{:.1f}"), width="stretch", height=270)
            else:
                st.info("Sin matriz disponible.")

    with right:
        pbi_section("ANÁLISIS ESTADÍSTICO — VISUALIZACIONES CLAVE")
        chart_box(
            "1. Distribución de consumo por tipo de ambiente",
            "",
            safe_pie(amb_df, "tipo_ambiente", "consumo_kwh"),
            "p2_dist_ambiente",
            height=130,
        )
        chart_box(
            "2. Tendencia mensual de consumo energético",
            "",
            safe_line(mes_df, "nombre_mes", "consumo_kwh"),
            "p2_tendencia_mensual",
            height=118,
        )
        chart_box(
            "3. Ranking de edificios por consumo",
            "",
            safe_bar(edif_df.head(8), "nombre_edificio", "consumo_kwh"),
            "p2_ranking_edificios",
            height=118,
        )
        chart_box(
            "4. Histograma / frecuencia por hora",
            "",
            safe_bar(hora_df, "hora", "consumo_kwh"),
            "p2_hist_hora",
            height=118,
        )

# ------------------------------------------------------------
# PÁGINA 3
# ------------------------------------------------------------

if pagina_actual == "Página 3":
    pbi_section("DATA WAREHOUSE — ANALÍTICA WEB Y TABLA DE HECHOS/DIMENSIONES")

    sesiones = web["sesion_id"].nunique() if "sesion_id" in web.columns else 0
    usuarios = web["usuario_id"].nunique() if "usuario_id" in web.columns else 0
    sesiones_completas = web.loc[web["evento"].eq("visualizacion_dashboard"), "sesion_id"].nunique() if not web.empty and "evento" in web.columns else 0
    abandono_count = web.loc[web["evento"].eq("abandono_flujo"), "sesion_id"].nunique() if not web.empty and "evento" in web.columns else 0
    conversion = (sesiones_completas / sesiones * 100) if sesiones else 0
    abandono = (abandono_count / sesiones * 100) if sesiones else 0

    m1, m2, m3, m4 = st.columns(4)
    with m1:
        metric_frame("Usuarios únicos", usuarios)
    with m2:
        metric_frame("Sesiones", sesiones)
    with m3:
        metric_frame("Tasa de abandono", f"{abandono:.1f}%")
    with m4:
        metric_frame("Conversión a dashboard", f"{conversion:.1f}%")

    eventos_avance = web[
        web["evento"].isin(
            [
                "carga_archivo",
                "validacion_staging",
                "etl_completado",
                "modelo_copo_nieve_completado",
                "colab_ia_completado",
                "visualizacion_dashboard",
                "click_continuar",
                "avance_etapa",
            ]
        )
    ] if not web.empty and "evento" in web.columns else pd.DataFrame()

    etapa = (
        eventos_avance.groupby(["etapa_numero", "etapa_nombre"], as_index=False)
        .agg(interacciones=("evento", "count"))
        .sort_values("etapa_numero")
        if not eventos_avance.empty
        else pd.DataFrame()
    )

    abandonos = (
        web[web["evento"].isin(["abandono_flujo", "error_proceso"])]
        .groupby(["etapa_numero", "etapa_nombre"], as_index=False)
        .agg(abandonos=("evento", "count"))
        .sort_values("abandonos", ascending=False)
        if not web.empty and "evento" in web.columns
        else pd.DataFrame()
    )

    conv_fecha = web.groupby("fecha", as_index=False).agg(sesiones=("sesion_id", "nunique")) if "fecha" in web.columns and not web.empty else pd.DataFrame()
    comp_fecha = (
        web[web["evento"].eq("visualizacion_dashboard")]
        .groupby("fecha", as_index=False)
        .agg(completadas=("sesion_id", "nunique"))
        if "fecha" in web.columns and "evento" in web.columns and not web.empty
        else pd.DataFrame()
    )
    if not conv_fecha.empty:
        conv_fecha = conv_fecha.merge(comp_fecha, on="fecha", how="left").fillna(0)
        conv_fecha["conversion"] = conv_fecha["completadas"] / conv_fecha["sesiones"] * 100

    disp = web.groupby("dispositivo", as_index=False).agg(sesiones=("sesion_id", "nunique")) if "dispositivo" in web.columns and not web.empty else pd.DataFrame()

    g1, g2, g3, g4 = st.columns(4)
    with g1:
        chart_box(
            "1. ¿Qué etapas generan más interacción?",
            "",
            safe_bar(etapa, "etapa_nombre", "interacciones"),
            "p3_interaccion_etapa",
            height=135,
        )
    with g2:
        chart_box(
            "2. ¿Dónde abandonan más los usuarios?",
            "",
            safe_bar(abandonos, "etapa_nombre", "abandonos", horizontal=True),
            "p3_abandono_etapa",
            height=135,
        )
    with g3:
        chart_box(
            "3. ¿Cuántos completan el proceso?",
            "",
            safe_line(conv_fecha, "fecha", "conversion"),
            "p3_conversion_dashboard",
            height=135,
        )
    with g4:
        chart_box(
            "4. ¿Qué dispositivos utilizan?",
            "",
            safe_pie(disp, "dispositivo", "sesiones"),
            "p3_dispositivos",
            height=135,
        )

    pbi_section("DATA WAREHOUSE — TABLA DE HECHOS Y DIMENSIONES")
    cols = [
        c
        for c in [
            "hora",
            "nombre_edificio",
            "nombre_ambiente",
            "tipo_ambiente",
            "ocupacion",
            "temperatura",
            "demanda_pico_kw",
            "factor_potencia",
            "consumo_kwh",
            "eficiencia",
            "riesgo_sobreconsumo",
        ]
        if c in fdf.columns
    ]

    table = fdf[cols].copy()
    table = table.rename(
        columns={
            "hora": "Hora",
            "nombre_edificio": "Edificio",
            "nombre_ambiente": "Ambiente",
            "tipo_ambiente": "Tipo",
            "ocupacion": "Ocupación",
            "temperatura": "Temperatura (°C)",
            "demanda_pico_kw": "Demanda pico (kW)",
            "factor_potencia": "Factor potencia",
            "consumo_kwh": "Consumo (kWh)",
            "eficiencia": "Eficiencia",
            "riesgo_sobreconsumo": "Riesgo",
        }
    )
    st.dataframe(table.head(1000), width="stretch", height=390)

    with st.expander("Últimos eventos de comportamiento registrados"):
        if not web.empty:
            sort_col = "fecha_hora" if "fecha_hora" in web.columns else web.columns[0]
            st.dataframe(web.sort_values(sort_col, ascending=False).head(300), width="stretch")
        else:
            st.info("Aún no hay eventos de comportamiento registrados.")
