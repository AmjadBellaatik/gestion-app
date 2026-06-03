<style>
/* ── Shared report styles ─────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; }

.rpt-wrap      { display:flex; flex-direction:column; gap:1.5rem; }
.rpt-card      {
    background:#fff; border-radius:.875rem;
    padding:1.25rem 1.375rem;
    box-shadow:0 1px 3px rgba(0,0,0,.06);
    border:1px solid rgba(0,0,0,.06); min-width:0;
}
.dark .rpt-card { background:rgb(30,41,59); border-color:rgba(255,255,255,.07); }

/* KPI grid */
.rpt-kpi-grid { display:grid; gap:1rem; grid-template-columns:repeat(2,1fr); }
@media(min-width:768px) { .rpt-kpi-grid { grid-template-columns:repeat(3,1fr); } }
@media(min-width:1280px){ .rpt-kpi-grid { grid-template-columns:repeat(4,1fr); } }

.rpt-kpi { display:flex; align-items:flex-start; gap:.75rem; }
.rpt-kpi-icon {
    flex-shrink:0; width:2.5rem; height:2.5rem;
    border-radius:.5rem; display:flex; align-items:center; justify-content:center;
}
.rpt-kpi-icon svg { width:1.125rem; height:1.125rem; }
.rpt-kpi-body { flex:1; min-width:0; }
.rpt-kpi-lbl  { font-size:.75rem; color:rgb(107,114,128); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dark .rpt-kpi-lbl { color:rgb(156,163,175); }
.rpt-kpi-val  { font-size:1.375rem; font-weight:700; color:rgb(17,24,39); margin-top:.125rem; overflow-wrap:anywhere; }
.dark .rpt-kpi-val { color:rgb(248,250,252); }
.rpt-kpi-sub  { font-size:.6875rem; color:rgb(156,163,175); margin-top:.25rem; }

/* Accent bars */
.rpt-card-em { border-left:3px solid rgb(16,185,129); }
.rpt-card-re { border-left:3px solid rgb(239,68,68); }
.rpt-card-am { border-left:3px solid rgb(245,158,11); }
.rpt-card-bl { border-left:3px solid rgb(59,130,246); }
.rpt-card-vi { border-left:3px solid rgb(139,92,246); }
.rpt-card-cy { border-left:3px solid rgb(6,182,212); }

/* Icon backgrounds */
.bg-em { background:rgb(236,253,245); } .dark .bg-em { background:rgba(52,211,153,.12); }
.bg-re { background:rgb(254,242,242); } .dark .bg-re { background:rgba(248,113,113,.12); }
.bg-am { background:rgb(255,251,235); } .dark .bg-am { background:rgba(251,191,36,.12); }
.bg-bl { background:rgb(239,246,255); } .dark .bg-bl { background:rgba(96,165,250,.12); }
.bg-vi { background:rgb(245,243,255); } .dark .bg-vi { background:rgba(167,139,250,.12); }
.bg-cy { background:rgb(236,254,255); } .dark .bg-cy { background:rgba(34,211,238,.12); }

/* Icon text colours */
.cl-em { color:rgb(5,150,105); }  .dark .cl-em { color:rgb(52,211,153); }
.cl-re { color:rgb(220,38,38); }  .dark .cl-re { color:rgb(248,113,113); }
.cl-am { color:rgb(217,119,6); }  .dark .cl-am { color:rgb(251,191,36); }
.cl-bl { color:rgb(37,99,235); }  .dark .cl-bl { color:rgb(96,165,250); }
.cl-vi { color:rgb(124,58,237); } .dark .cl-vi { color:rgb(167,139,250); }
.cl-cy { color:rgb(8,145,178); }  .dark .cl-cy { color:rgb(34,211,238); }

/* Table */
.rpt-table-wrap { overflow-x:auto; }
.rpt-table { width:100%; border-collapse:collapse; font-size:.8125rem; }
.rpt-table th {
    text-align:left; padding:.625rem .875rem;
    font-size:.6875rem; font-weight:600; letter-spacing:.05em; text-transform:uppercase;
    color:rgb(107,114,128); background:rgb(249,250,251);
    border-bottom:1px solid rgb(229,231,235);
    white-space:nowrap;
}
.dark .rpt-table th { background:rgb(15,23,42); color:rgb(156,163,175); border-color:rgba(255,255,255,.06); }
.rpt-table td {
    padding:.625rem .875rem;
    border-bottom:1px solid rgb(243,244,246);
    color:rgb(55,65,81); vertical-align:middle;
}
.dark .rpt-table td { color:rgb(209,213,219); border-color:rgba(255,255,255,.04); }
.rpt-table tr:last-child td { border-bottom:none; }
.rpt-table tr:hover td { background:rgb(249,250,251); }
.dark .rpt-table tr:hover td { background:rgba(255,255,255,.02); }

/* Status badges */
.rpt-badge {
    display:inline-block; padding:.1875rem .5rem;
    border-radius:9999px; font-size:.6875rem; font-weight:600;
    white-space:nowrap;
}
.rpt-badge-em  { background:rgb(209,250,229); color:rgb(6,95,70); }
.rpt-badge-re  { background:rgb(254,226,226); color:rgb(153,27,27); }
.rpt-badge-am  { background:rgb(254,243,199); color:rgb(146,64,14); }
.rpt-badge-bl  { background:rgb(219,234,254); color:rgb(30,64,175); }
.rpt-badge-vi  { background:rgb(237,233,254); color:rgb(91,33,182); }
.rpt-badge-cy  { background:rgb(207,250,254); color:rgb(22,78,99); }
.rpt-badge-gr  { background:rgb(243,244,246); color:rgb(55,65,81); }
.dark .rpt-badge-em { background:rgba(52,211,153,.15); color:rgb(52,211,153); }
.dark .rpt-badge-re { background:rgba(248,113,113,.15); color:rgb(248,113,113); }
.dark .rpt-badge-am { background:rgba(251,191,36,.15); color:rgb(251,191,36); }
.dark .rpt-badge-bl { background:rgba(96,165,250,.15); color:rgb(96,165,250); }
.dark .rpt-badge-vi { background:rgba(167,139,250,.15); color:rgb(167,139,250); }
.dark .rpt-badge-cy { background:rgba(34,211,238,.15); color:rgb(34,211,238); }

/* Period bar */
.rpt-period-bar {
    display:flex; flex-wrap:wrap; align-items:center; gap:.5rem;
    background:#fff; border-radius:.875rem;
    padding:.875rem 1.125rem;
    border:1px solid rgba(0,0,0,.06);
    box-shadow:0 1px 3px rgba(0,0,0,.05);
}
.dark .rpt-period-bar { background:rgb(30,41,59); border-color:rgba(255,255,255,.07); }
.rpt-period-presets { display:flex; flex-wrap:wrap; gap:.375rem; flex:1; min-width:0; }
.rpt-period-btn {
    font-size:.75rem; font-weight:500;
    padding:.3125rem .75rem; border-radius:9999px;
    border:1px solid rgba(0,0,0,.12); background:transparent;
    color:rgb(75,85,99); cursor:pointer; transition:all .15s; white-space:nowrap;
}
.dark .rpt-period-btn { border-color:rgba(255,255,255,.12); color:rgb(156,163,175); }
.rpt-period-btn:hover  { background:rgb(243,244,246); border-color:rgba(0,0,0,.2); color:rgb(17,24,39); }
.dark .rpt-period-btn:hover { background:rgba(255,255,255,.06); }
.rpt-period-btn.active { background:rgb(16,185,129); border-color:rgb(16,185,129); color:#fff; }
.rpt-period-label { font-size:.75rem; color:rgb(107,114,128); white-space:nowrap; padding-left:.5rem; }
.dark .rpt-period-label { color:rgb(156,163,175); }
.rpt-custom-range  { display:flex; align-items:center; gap:.5rem; flex-wrap:wrap; margin-top:.5rem; width:100%; }
.rpt-custom-range label { font-size:.75rem; color:rgb(107,114,128); }
.rpt-custom-range input[type="date"] {
    font-size:.8125rem; padding:.3125rem .625rem;
    border:1px solid rgba(0,0,0,.15); border-radius:.5rem;
    background:#fff; color:rgb(17,24,39);
}
.dark .rpt-custom-range input[type="date"] {
    background:rgb(51,65,85); border-color:rgba(255,255,255,.12); color:rgb(248,250,252); color-scheme:dark;
}

/* Section header */
.rpt-section-lbl {
    font-size:.6875rem; font-weight:700; letter-spacing:.1em;
    text-transform:uppercase; color:rgb(156,163,175); margin-bottom:.5rem;
}
.dark .rpt-section-lbl { color:rgb(107,114,128); }

/* Empty state */
.rpt-empty { padding:2.5rem 1rem; text-align:center; color:rgb(156,163,175); font-size:.875rem; }
</style>
