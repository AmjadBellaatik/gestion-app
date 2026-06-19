"""Measure key block y-positions in the comparison PDFs (PyMuPDF).
Reports, in mm from the physical page top, where each landmark sits, and
whether the body content overlaps the fixed footer band."""
import fitz
import os

PDF_DIR = r"C:\laragon\www\gestion-app\storage\app\test-pdfs"
PT_PER_MM = 72 / 25.4
A4_H_MM = 297.0

# Footer band (from master.blade.php): bottom:3mm height:24mm => 3..27mm from physical bottom
FOOTER_TOP_FROM_BOTTOM = 27.0
FOOTER_TOP_FROM_TOP_MM = A4_H_MM - FOOTER_TOP_FROM_BOTTOM  # 270mm

PAIRS = [
    ("BL-company.pdf",  "BL-admin.pdf",  ["CLIENT", "Total TTC", "Livre par", "Av Moulay", "ICE :"]),
    ("GAR-company.pdf", "GAR-admin.pdf", ["autre part", "Signature client", "le :", "ICE :", "Patente"]),
    ("BL-company-ctl.pdf", "BL-admin-ctl.pdf", ["CLIENT", "Total TTC", "Livre par"]),
]

def pt_to_mm(pt): return pt / PT_PER_MM

def analyze(pdf_name):
    path = os.path.join(PDF_DIR, pdf_name)
    doc = fitz.open(path)
    page = doc[0]
    ph_pt = page.rect.height
    # Last text block bottom (lowest non-footer body text)
    blocks = page.get_text("blocks")
    # Footer text usually contains the legal info; find lowest body content above footer band
    footer_top_pt = FOOTER_TOP_FROM_TOP_MM * PT_PER_MM
    body_bottom_pt = 0.0
    footer_present = False
    for b in blocks:
        x0, y0, x1, y1, text = b[0], b[1], b[2], b[3], b[4]
        if not text.strip():
            continue
        # classify: is this block in the footer band?
        if y0 >= footer_top_pt - 6:  # within footer band (small tolerance)
            footer_present = True
        else:
            body_bottom_pt = max(body_bottom_pt, y1)
    info = {
        "pages": len(doc),
        "page_h_mm": round(pt_to_mm(ph_pt), 1),
        "body_bottom_mm": round(pt_to_mm(body_bottom_pt), 1),
        "footer_band_top_mm": round(FOOTER_TOP_FROM_TOP_MM, 1),
        "gap_body_to_footer_mm": round(FOOTER_TOP_FROM_TOP_MM - pt_to_mm(body_bottom_pt), 1),
        "footer_present_p1": footer_present,
    }
    doc.close()
    return info

def find_text_y(pdf_name, needle):
    path = os.path.join(PDF_DIR, pdf_name)
    doc = fitz.open(path)
    ys = []
    for pno in range(len(doc)):
        page = doc[pno]
        for r in page.search_for(needle):
            ys.append((pno + 1, round(pt_to_mm(r.y0), 1), round(pt_to_mm(r.y1), 1)))
    doc.close()
    return ys

print(f"Footer band starts at {FOOTER_TOP_FROM_TOP_MM:.0f}mm from page top "
      f"(= {FOOTER_TOP_FROM_BOTTOM:.0f}mm from bottom). Body must stay above this.\n")

for a, b, needles in PAIRS:
    print("=" * 78)
    print(f"PAIR: {a}  vs  {b}")
    print("=" * 78)
    for name in (a, b):
        info = analyze(name)
        overlap = info["gap_body_to_footer_mm"] < 0
        flag = "  <-- OVERLAP!" if overlap else "  OK"
        print(f"  {name:22s} pages={info['pages']} "
              f"body_bottom={info['body_bottom_mm']:6.1f}mm  "
              f"gap_to_footer={info['gap_body_to_footer_mm']:6.1f}mm{flag}")
    print("  landmark y-positions (mm from top, page#):")
    for needle in needles:
        ya = find_text_y(a, needle)
        yb = find_text_y(b, needle)
        print(f"    {needle:18s} {a[:14]:14s}={ya}   {b[:14]:14s}={yb}")
    print()
