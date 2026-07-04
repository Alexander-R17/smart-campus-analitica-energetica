import streamlit as st
import pandas as pd
import plotly.express as px
import plotly.graph_objects as go

NAVY = "#071A4A"
BLUE = "#0A4DB3"
GOLD = "#D6A400"
LIGHT = "#F5F8FF"
BORDER = "#16406F"


def inject_css():
    st.markdown(f"""
    <style>
    .block-container {{padding-top: .4rem; padding-bottom: 1rem; max-width: 1500px;}}
    .main-header {{background:{NAVY}; color:white; padding:12px 18px; border-radius:0; display:flex; align-items:center; gap:18px; border-bottom:4px solid #ffffff;}}
    .main-header img {{height:55px;}}
    .main-header h1 {{font-size:25px; margin:0; line-height:1.12; font-weight:800;}}
    .section-title {{background:{NAVY}; color:#fff; text-align:center; padding:7px; font-weight:800; margin:8px 0; border:2px solid {NAVY};}}
    .panel {{border:2px solid {BORDER}; background:white; padding:10px; min-height:128px; margin-bottom:8px;}}
    .panel h4 {{text-align:center; color:{NAVY}; font-size:14px; margin:0 0 3px 0;}}
    .small-question {{font-size:10px; color:#333; text-align:center; min-height:28px;}}
    .metric-value {{font-size:18px; font-weight:800; color:#003dba; text-align:center; margin-top:5px;}}
    .alert-value {{font-size:18px; font-weight:800; color:#d9481e; text-align:center; margin-top:5px;}}
    .filter-box {{border:2px solid {NAVY}; border-radius:14px; padding:10px; background:#fff; margin-bottom:10px;}}
    .filter-title {{background:{NAVY}; color:white; padding:8px; border-radius:10px; font-weight:800; margin-bottom:8px;}}
    .olap-btn {{border:2px solid #111; background:#fff; text-align:center; font-size:12px; font-weight:700; padding:10px; margin:5px; color:{BLUE}; box-shadow:2px 2px 0 #ddd;}}
    .stTabs [data-baseweb="tab-list"] {{gap: 8px;}}
    .stTabs [data-baseweb="tab"] {{font-weight: 700; background:#f4f6fb; border-radius:0; padding:8px 18px;}}
    div[data-testid="stMetric"] {{border:2px solid {BORDER}; padding:8px; background:#fff;}}
    .dataframe {{font-size:12px;}}
    </style>
    """, unsafe_allow_html=True)


def header():
    st.markdown("""
    <div class="main-header">
        <img src="https://raw.githubusercontent.com/streamlit/streamlit/develop/lib/streamlit/static/favicon.png" style="display:none">
        <img src="app/static/logo.png" onerror="this.style.display='none'">
        <div><h1>PLATAFORMA INTEGRAL DE ANALÍTICA ENERGÉTICA - SMART<br>CAMPUS UNIVERSIDAD NUEVA ESPERANZA</h1></div>
    </div>
    """, unsafe_allow_html=True)


def header_with_local_logo():
    import base64
    from pathlib import Path
    logo = Path(__file__).resolve().parents[1] / "assets" / "logo.png"
    img = ""
    if logo.exists():
        img = f"data:image/png;base64,{base64.b64encode(logo.read_bytes()).decode()}"
    st.markdown(f"""
    <div class="main-header">
        {'<img src="'+img+'">' if img else ''}
        <div><h1>PLATAFORMA INTEGRAL DE ANALÍTICA ENERGÉTICA - SMART<br>CAMPUS UNIVERSIDAD NUEVA ESPERANZA</h1></div>
    </div>
    """, unsafe_allow_html=True)


def section(title: str):
    st.markdown(f'<div class="section-title">{title}</div>', unsafe_allow_html=True)


def card(title: str, question: str, value: str = "", alert: bool = False):
    cls = "alert-value" if alert else "metric-value"
    st.markdown(f"""
    <div class="panel">
        <h4>{title}</h4>
        <div class="small-question">{question}</div>
        <div class="{cls}">{value}</div>
    </div>
    """, unsafe_allow_html=True)


def fmt_kwh(v: float) -> str:
    try:
        v = float(v)
    except Exception:
        return "0 kWh"
    if abs(v) >= 1000:
        return f"{v/1000:,.2f} MWh".replace(',', ' ')
    return f"{v:,.1f} kWh".replace(',', ' ')


def fig_line(df, x, y, title=""):
    fig = px.line(df, x=x, y=y, markers=True, title=title)
    fig.update_layout(height=180, margin=dict(l=8,r=8,t=25,b=8), font=dict(size=10), showlegend=False)
    return fig


def fig_bar(df, x, y, title="", color=None, horizontal=False):
    if horizontal:
        fig = px.bar(df, x=y, y=x, orientation='h', title=title, color=color or y)
    else:
        fig = px.bar(df, x=x, y=y, title=title, color=color or x)
    fig.update_layout(height=210, margin=dict(l=8,r=8,t=25,b=8), font=dict(size=10), showlegend=False)
    return fig


def fig_pie(df, names, values, title=""):
    fig = px.pie(df, names=names, values=values, hole=.45, title=title)
    fig.update_layout(height=210, margin=dict(l=8,r=8,t=25,b=8), font=dict(size=10), legend=dict(orientation='h'))
    return fig
