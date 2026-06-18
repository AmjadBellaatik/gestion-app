"""Convert test PDFs to PNG using PyMuPDF (fitz). Renders every page at 150 DPI."""
import fitz  # PyMuPDF
import os
import sys

PDF_DIR = r"C:\laragon\www\gestion-app\storage\app\test-pdfs"
IMG_DIR = os.path.join(PDF_DIR, "screenshots")
os.makedirs(IMG_DIR, exist_ok=True)

PDFS = [
    "FAC-2026-0001.pdf",
    "DEV-2026-0001.pdf",
    "BL-2026-0001.pdf",
    "BC-2026-0001.pdf",
    "GAR-2026-0001.pdf",
    "PRSK-2026-0001.pdf",
    "FAC-REP-2026-0001.pdf",
    "FAC-2026-MULTI.pdf",
]

DPI = 150
# A4 at 150 DPI: 595pt * 150/72 ≈ 1240px wide
MATRIX = fitz.Matrix(DPI / 72, DPI / 72)

for pdf_name in PDFS:
    pdf_path = os.path.join(PDF_DIR, pdf_name)
    base = os.path.splitext(pdf_name)[0]

    try:
        doc = fitz.open(pdf_path)
        n = len(doc)
        print(f"\n{pdf_name}: {n} page(s)")

        # Always render: page 1, last page (if multi-page)
        pages_to_render = [0]
        if n > 1:
            pages_to_render.append(n - 1)

        for i in pages_to_render:
            page = doc[i]
            label = f"p{i+1}"
            out_path = os.path.join(IMG_DIR, f"{base}-{label}.png")
            pix = page.get_pixmap(matrix=MATRIX, alpha=False)
            pix.save(out_path)
            size_kb = os.path.getsize(out_path) // 1024
            print(f"  page {i+1}/{n}: {pix.width}x{pix.height}px -> {out_path} ({size_kb} KB)")

        doc.close()
    except Exception as e:
        print(f"  ERROR: {e}", file=sys.stderr)

print(f"\nDone. Screenshots in: {IMG_DIR}")
