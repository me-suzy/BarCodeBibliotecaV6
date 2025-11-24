#!/usr/bin/env python3
# -*- coding: utf-8 -*-

"""
Generare coduri de bare pentru Biblioteca Academiei Române - Iași
"""

import barcode
from barcode.writer import ImageWriter
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import os

# ===== CONFIGURARE =====
PREFIX = "USER"
START_NUMBER = 16038

# Întreabă utilizatorul
print("🔷 Generare Coduri de Bare - Biblioteca Academiei Române - Iași")
print("=" * 70)

while True:
    try:
        count = int(input(f"\nCâte etichete vrei să generezi? (1-100): "))
        if 1 <= count <= 100:
            break
        print("❌ Te rog introdu un număr între 1 și 100")
    except ValueError:
        print("❌ Te rog introdu un număr valid")

# ===== GENERARE CODURI =====
codes = []
for i in range(count):
    code = f"{PREFIX}{START_NUMBER + i}"
    codes.append(code)

print(f"\n📋 Coduri generate:")
for code in codes[:5]:
    print(f"   {code}")
if len(codes) > 5:
    print(f"   ...")
    print(f"   {codes[-1]}")

# ===== GENERARE PDF =====
output_pdf = f'etichete_{PREFIX}_{START_NUMBER}-{START_NUMBER + count - 1}.pdf'

c = canvas.Canvas(output_pdf, pagesize=A4)
page_width, page_height = A4

# Înregistrează font UTF-8 pentru diacritice
try:
    # Încearcă să găsească Arial sau DejaVu Sans
    font_paths = [
        'C:/Windows/Fonts/arial.ttf',
        'C:/Windows/Fonts/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/System/Library/Fonts/Helvetica.ttc'
    ]

    font_found = False
    for font_path in font_paths:
        if os.path.exists(font_path):
            pdfmetrics.registerFont(TTFont('CustomFont', font_path))
            font_name = 'CustomFont'
            font_found = True
            break

    if not font_found:
        print("⚠️  Font UTF-8 nu găsit, folosesc Helvetica (fără diacritice complete)")
        font_name = 'Helvetica'

except Exception as e:
    print(f"⚠️  Eroare la încărcare font: {e}")
    font_name = 'Helvetica'

# Layout etichete
label_width = 63 * mm
label_height = 30 * mm
cols = 3
rows = 9
spacing = 2 * mm
margin_left = 10 * mm
margin_top = 10 * mm

labels_per_page = cols * rows

for i, code in enumerate(codes):

    # Calculează poziția
    label_index = i % labels_per_page
    row = label_index // cols
    col = label_index % cols

    x = margin_left + col * (label_width + spacing)
    y = page_height - margin_top - (row + 1) * label_height

    # Header cu diacritice UTF-8
    c.setFont(font_name, 8)
    c.drawCentredString(
        x + label_width / 2,
        y + label_height - 8 * mm,
        "Biblioteca Academiei Române - Iași"
    )

    # Generează barcode Code128
    CODE128 = barcode.get_barcode_class('code128')

    options = {
        'module_width': 0.3,
        'module_height': 12.0,
        'quiet_zone': 3.0,
        'font_size': 0,  # Fără text în barcode (îl punem noi)
        'write_text': False,
    }

    code_obj = CODE128(code, writer=ImageWriter())
    temp_file = f'temp_barcode_{code}'
    barcode_path = code_obj.save(temp_file, options=options)

    # Desenează barcode în PDF
    c.drawImage(
        barcode_path,
        x + 5 * mm,
        y + 8 * mm,
        width=label_width - 10 * mm,
        height=12 * mm,
        preserveAspectRatio=True
    )

    # Text cod sub barcode
    c.setFont(font_name, 10)
    c.drawCentredString(
        x + label_width / 2,
        y + 4 * mm,
        code
    )

    # Șterge fișierul temporar
    try:
        os.remove(barcode_path)
    except:
        pass

    # Pagină nouă la nevoie
    if (i + 1) % labels_per_page == 0 and i < len(codes) - 1:
        c.showPage()

# Salvează PDF
c.save()

print(f"\n✅ PDF generat: {output_pdf}")
print(f"   Total etichete: {len(codes)}")
print(f"   Primul cod: {codes[0]}")
print(f"   Ultimul cod: {codes[-1]}")
print(f"   Pagini: {(len(codes) - 1) // labels_per_page + 1}")