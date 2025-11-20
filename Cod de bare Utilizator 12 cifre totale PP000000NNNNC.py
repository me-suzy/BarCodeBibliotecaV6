# generator_upc_barcode.py
import tkinter as tk
from tkinter import ttk, messagebox
import os
from datetime import datetime
import barcode
from barcode.writer import ImageWriter
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.lib.units import mm
import math
import re

class GeneratorUPC:
    def __init__(self, root):
        self.root = root
        self.root.title("🔢 Generator Coduri UPC-A (12 Cifre)")
        self.root.geometry("700x600")
        self.root.configure(bg="#4A90E2")

        # Directoare pentru salvare
        self.folder_imagini = "coduri_upc"
        self.folder_pdf = "pdf_upc"

        # Fișier de tracking
        self.tracking_file = os.path.join(self.folder_imagini, "_tracking_upc.txt")

        # Creează folderele
        for folder in [self.folder_imagini, self.folder_pdf]:
            os.makedirs(folder, exist_ok=True)
            print(f"✓ Folder creat/verificat: {folder}")

        self.setup_ui()

    def setup_ui(self):
        # Container principal
        main_frame = tk.Frame(self.root, bg="white", padx=30, pady=30)
        main_frame.pack(expand=True, fill="both", padx=20, pady=20)

        # Titlu
        title = tk.Label(
            main_frame,
            text="🔢 Generator Coduri UPC-A",
            font=("Segoe UI", 22, "bold"),
            bg="white",
            fg="#4A90E2"
        )
        title.pack(pady=(0, 10))

        subtitle = tk.Label(
            main_frame,
            text="Format: 12 cifre cu checksum automat (seria 12xxxxxxxxxx)",
            font=("Segoe UI", 10, "italic"),
            bg="white",
            fg="#666"
        )
        subtitle.pack(pady=(0, 25))

        # ════════════════════════════════════════════════
        # SECȚIUNEA GENERARE CODURI
        # ════════════════════════════════════════════════
        gen_frame = tk.LabelFrame(
            main_frame,
            text="📊 Generare Coduri UPC-A",
            font=("Segoe UI", 13, "bold"),
            bg="white",
            fg="#4A90E2",
            padx=20,
            pady=15
        )
        gen_frame.pack(fill="x", pady=(0, 15))

        # Prefix (primele 2 cifre)
        tk.Label(
            gen_frame,
            text="Prefix (11-17):",
            font=("Segoe UI", 11),
            bg="white"
        ).grid(row=0, column=0, sticky="w", pady=8)

        self.prefix_entry = tk.Entry(
            gen_frame,
            font=("Segoe UI", 11),
            width=8
        )
        self.prefix_entry.grid(row=0, column=1, padx=10, pady=8, sticky="w")
        self.prefix_entry.insert(0, "12")

        # Cantitate de generat
        tk.Label(
            gen_frame,
            text="Câte coduri să generez:",
            font=("Segoe UI", 11),
            bg="white"
        ).grid(row=1, column=0, sticky="w", pady=8)

        self.count_entry = tk.Entry(
            gen_frame,
            font=("Segoe UI", 11),
            width=12
        )
        self.count_entry.grid(row=1, column=1, padx=10, pady=8, sticky="w")
        self.count_entry.insert(0, "50")

        # Checkbox refacere coduri lipsă
        self.refacere_var = tk.BooleanVar(value=True)
        tk.Checkbutton(
            gen_frame,
            text="✓ Refă codurile lipsă din secvență",
            variable=self.refacere_var,
            font=("Segoe UI", 10),
            bg="white"
        ).grid(row=2, column=0, columnspan=2, sticky="w", pady=(10, 5))

        # Buton generare
        btn_gen = tk.Button(
            gen_frame,
            text="🎯 Generează Coduri UPC-A",
            font=("Segoe UI", 12, "bold"),
            bg="#28a745",
            fg="white",
            padx=20,
            pady=12,
            cursor="hand2",
            command=self.genereaza_coduri
        )
        btn_gen.grid(row=3, column=0, columnspan=2, pady=(15, 5))

        # ════════════════════════════════════════════════
        # SECȚIUNEA PDF
        # ════════════════════════════════════════════════
        pdf_frame = tk.LabelFrame(
            main_frame,
            text="🖨️ Generare PDF pentru Print",
            font=("Segoe UI", 13, "bold"),
            bg="white",
            fg="#4A90E2",
            padx=20,
            pady=15
        )
        pdf_frame.pack(fill="x", pady=(0, 10))

        # Coduri per pagină
        tk.Label(
            pdf_frame,
            text="Coduri pe pagină:",
            font=("Segoe UI", 11),
            bg="white"
        ).grid(row=0, column=0, sticky="w", pady=8)

        self.coduri_pagina_entry = tk.Entry(
            pdf_frame,
            font=("Segoe UI", 11),
            width=10
        )
        self.coduri_pagina_entry.grid(row=0, column=1, padx=10, pady=8)
        self.coduri_pagina_entry.insert(0, "30")

        # Lungime cod
        tk.Label(
            pdf_frame,
            text="Lungime cod (cm):",
            font=("Segoe UI", 11),
            bg="white"
        ).grid(row=1, column=0, sticky="w", pady=8)

        self.lungime_entry = tk.Entry(
            pdf_frame,
            font=("Segoe UI", 11),
            width=10
        )
        self.lungime_entry.grid(row=1, column=1, padx=10, pady=8)
        self.lungime_entry.insert(0, "6")

        # Buton PDF
        btn_pdf = tk.Button(
            pdf_frame,
            text="📄 Generează PDF",
            font=("Segoe UI", 12, "bold"),
            bg="#dc3545",
            fg="white",
            padx=25,
            pady=12,
            cursor="hand2",
            command=self.genereaza_pdf
        )
        btn_pdf.grid(row=2, column=0, columnspan=2, pady=(15, 5))

        # Status bar
        self.status = tk.Label(
            main_frame,
            text="✅ Gata de lucru",
            font=("Segoe UI", 10),
            bg="white",
            fg="#28a745",
            anchor="w"
        )
        self.status.pack(fill="x", pady=(15, 0))

    def calculeaza_checksum_upc(self, cod_11_cifre):
        """Calculează cifra de control pentru UPC-A (11 cifre -> 12 cifre)"""
        if len(cod_11_cifre) != 11:
            raise ValueError("Codul trebuie să aibă exact 11 cifre")

        # Algoritmul Modulo 10 pentru UPC-A
        suma = 0
        for i, digit in enumerate(cod_11_cifre):
            val = int(digit)
            if i % 2 == 0:  # Poziții impare (0, 2, 4, ...) - înmulțesc cu 3
                suma += val * 3
            else:  # Poziții pare (1, 3, 5, ...) - înmulțesc cu 1
                suma += val

        # Cifra de control = (10 - (suma % 10)) % 10
        checksum = (10 - (suma % 10)) % 10
        return str(checksum)

    def genereaza_cod_upc(self, prefix, numar):
        """Generează cod UPC-A complet cu checksum"""
        # Format: PP + 00000 + NNNN = 11 cifre (NU 6 zerouri, ci 5!)
        # PP = prefix (2 cifre)
        # 00000 = padding (5 cifre)
        # NNNN = număr secvențial (4 cifre)

        cod_11_cifre = f"{prefix}00000{numar:04d}"  # ✅ 5 zerouri, nu 6!
        checksum = self.calculeaza_checksum_upc(cod_11_cifre)
        cod_complet = cod_11_cifre + checksum

        return cod_complet

    def analizeaza_coduri_existente(self, prefix):
        """Analizează codurile existente și detectează lipsuri"""
        coduri_cu_imagini = set()

        # Scanează imaginile fizice
        if os.path.exists(self.folder_imagini):
            for file in os.listdir(self.folder_imagini):
                if file.endswith('.png'):
                    # Extrage codul din numele fișierului
                    cod = file.replace('.png', '')
                    if cod.startswith(prefix):
                        coduri_cu_imagini.add(cod)

        # Citește din tracking
        coduri_tracking = set()
        if os.path.exists(self.tracking_file):
            with open(self.tracking_file, 'r', encoding='utf-8') as f:
                for line in f:
                    line = line.strip()
                    if line.startswith(prefix):
                        coduri_tracking.add(line)

        # Detectează coduri fără imagini
        coduri_fara_imagini = coduri_tracking - coduri_cu_imagini

        if coduri_fara_imagini:
            print(f"⚠️ {len(coduri_fara_imagini)} coduri din tracking nu au imagini!")

        # Extrage toate numerele secvențiale
        toate_numerele = set()
        for cod in coduri_cu_imagini.union(coduri_tracking):
            # Extrage ultimele 5 cifre (4 cifre număr + 1 cifră checksum)
            # Apoi ia primele 4 din acestea
            match = re.search(r'(\d{4})\d$', cod)
            if match:
                toate_numerele.add(int(match.group(1)))

        if not toate_numerele:
            return 0, []

        max_numar = max(toate_numerele)

        # Detectează lipsuri
        lipsa = []
        for i in range(1, max_numar + 1):
            cod_test = self.genereaza_cod_upc(prefix, i)
            img_path = os.path.join(self.folder_imagini, f"{cod_test}.png")

            if not os.path.exists(img_path):
                lipsa.append(i)

        print(f"📊 Analiză UPC-A (prefix {prefix}):")
        print(f"   Max număr generat: {max_numar}")
        print(f"   Imagini existente: {len(coduri_cu_imagini)}")
        print(f"   Imagini LIPSĂ: {len(lipsa)}")

        return max_numar, lipsa

    def genereaza_coduri(self):
        try:
            prefix = self.prefix_entry.get().strip()
            count = int(self.count_entry.get())

            # Validări
            if not prefix.isdigit() or len(prefix) != 2:
                raise ValueError("Prefix-ul trebuie să fie 2 cifre (11-17)")

            prefix_num = int(prefix)
            if prefix_num < 11 or prefix_num > 17:
                raise ValueError("Prefix-ul trebuie să fie între 11 și 17")

            if count <= 0:
                raise ValueError("Numărul trebuie să fie pozitiv")

            # Asigură-te că folderul există
            os.makedirs(self.folder_imagini, exist_ok=True)

            # Analizează codurile existente
            max_existent, coduri_lipsa = self.analizeaza_coduri_existente(prefix)
            refacere = self.refacere_var.get()

            # Afișează informații
            if max_existent > 0 or coduri_lipsa:
                cod_max = self.genereaza_cod_upc(prefix, max_existent)
                mesaj_info = f"📊 Situație coduri UPC-A (prefix {prefix}):\n\n"
                mesaj_info += f"• Maxim existent: {cod_max}\n"
                mesaj_info += f"• Imagini lipsă: {len(coduri_lipsa)}\n"
                mesaj_info += f"• Coduri noi de generat: {count}\n\n"

                if coduri_lipsa:
                    mesaj_info += f"⚠️ IMAGINI LIPSĂ detectate:\n"
                    for num in coduri_lipsa[:15]:
                        cod = self.genereaza_cod_upc(prefix, num)
                        mesaj_info += f"   - {cod}.png\n"
                    if len(coduri_lipsa) > 15:
                        mesaj_info += f"   ... și încă {len(coduri_lipsa) - 15}\n"
                    mesaj_info += "\n"

                if coduri_lipsa and refacere:
                    mesaj_info += f"✓ Voi REGENERA {len(coduri_lipsa)} imagini lipsă\n"
                    mesaj_info += f"✓ Voi ADĂUGA {count} coduri noi\n"
                    mesaj_info += f"\nTotal de generat: {len(coduri_lipsa) + count} coduri\n\n"
                else:
                    cod_urmator = self.genereaza_cod_upc(prefix, max_existent + 1)
                    mesaj_info += f"• Numerotare va continua de la {cod_urmator}\n\n"

                if not refacere and coduri_lipsa:
                    mesaj_info += f"⚠️ ATENȚIE: {len(coduri_lipsa)} imagini lipsă NU vor fi regenerate!\n\n"

                mesaj_info += "Continui?"

                if not messagebox.askyesno("Verificare Coduri", mesaj_info):
                    return

            # Pregătește lista de coduri
            coduri_de_generat = []

            # Adaugă codurile lipsă
            if refacere and coduri_lipsa:
                for num in coduri_lipsa:
                    cod = self.genereaza_cod_upc(prefix, num)
                    coduri_de_generat.append(cod)

            # Adaugă codurile noi
            start_noi = max_existent + 1
            for i in range(start_noi, start_noi + count):
                cod = self.genereaza_cod_upc(prefix, i)
                coduri_de_generat.append(cod)

            total = len(coduri_de_generat)
            self.status.config(text=f"⏳ Generez {total} coduri UPC-A...", fg="#ffc107")
            self.root.update()

            # Generează codurile
            txt_file = os.path.join(
                self.folder_imagini,
                f"coduri_upc_{prefix}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt"
            )

            with open(txt_file, 'w', encoding='utf-8') as f:
                f.write(f"CODURI UPC-A (Prefix: {prefix}) - Generat: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
                f.write("="*70 + "\n")
                if refacere and coduri_lipsa:
                    f.write(f"REGENERATE (imagini lipsă): {len(coduri_lipsa)} coduri\n")
                f.write(f"NOI: {count} coduri\n")
                f.write(f"TOTAL: {total} coduri\n")
                f.write("="*70 + "\n\n")
                f.write("Format: 12 cifre (11 cifre date + 1 cifră checksum)\n")
                f.write(f"Structură: {prefix}000000NNNNC (unde NNNN = număr secvențial, C = checksum)\n\n")

                for idx, cod in enumerate(coduri_de_generat, 1):
                    f.write(f"{cod}\n")

                    # Generează imaginea
                    self.genereaza_imagine_barcode(cod)

                    if idx % 25 == 0:
                        self.status.config(text=f"⏳ Generat {idx}/{total} coduri...")
                        self.root.update()

            # Actualizează tracking
            self.actualizeaza_tracking(coduri_de_generat)

            self.status.config(text=f"✅ {total} coduri UPC-A generate!", fg="#28a745")
            messagebox.showinfo(
                "Succes!",
                f"✅ {total} coduri UPC-A generate!\n\n"
                f"Prefix: {prefix}\n"
                f"{'📄 Regenerate: ' + str(len(coduri_lipsa)) + chr(10) if coduri_lipsa and refacere else ''}"
                f"➕ Noi: {count}\n\n"
                f"📁 Imagini: {self.folder_imagini}/\n"
                f"📄 Lista: {os.path.basename(txt_file)}"
            )

        except ValueError as e:
            messagebox.showerror("Eroare", f"Valoare invalidă: {e}")
            self.status.config(text="❌ Eroare la generare", fg="#dc3545")
        except Exception as e:
            messagebox.showerror("Eroare", f"Eroare: {e}")
            self.status.config(text="❌ Eroare la generare", fg="#dc3545")

    def genereaza_imagine_barcode(self, cod):
        """Generează imaginea codului de bare UPC-A folosind EAN13"""
        try:
            # UPC-A este compatibil cu EAN13 (se adaugă un 0 în față)
            EAN13 = barcode.get_barcode_class('ean13')
            code = EAN13(cod, writer=ImageWriter())
            filename = os.path.join(self.folder_imagini, cod)

            # Salvează cu opțiuni personalizate
            options = {
                'module_width': 0.3,
                'module_height': 15,
                'quiet_zone': 6.5,
                'font_size': 10,
                'text_distance': 5,
                'write_text': True
            }

            code.save(filename, options=options)

        except Exception as e:
            print(f"Eroare la generare {cod}: {e}")

    def actualizeaza_tracking(self, coduri):
        """Actualizează fișierul de tracking"""
        coduri_existente = set()
        if os.path.exists(self.tracking_file):
            with open(self.tracking_file, 'r', encoding='utf-8') as f:
                for line in f:
                    cod = line.strip()
                    if cod:
                        coduri_existente.add(cod)

        coduri_existente.update(coduri)

        with open(self.tracking_file, 'w', encoding='utf-8') as f:
            for cod in sorted(coduri_existente):
                f.write(f"{cod}\n")

    def genereaza_pdf(self):
        try:
            coduri_per_pagina = int(self.coduri_pagina_entry.get())
            lungime_cm = float(self.lungime_entry.get())

            if coduri_per_pagina <= 0 or lungime_cm <= 0:
                raise ValueError("Valorile trebuie să fie pozitive")

            imagini = [f for f in os.listdir(self.folder_imagini)
                      if f.endswith('.png')]

            if not imagini:
                messagebox.showwarning(
                    "Atenție",
                    f"Nu există coduri generate în {self.folder_imagini}!\n"
                    f"Mai întâi generează coduri UPC-A."
                )
                return

            self.status.config(text=f"⏳ Generez PDF cu {len(imagini)} coduri...", fg="#ffc107")
            self.root.update()

            pdf_file = os.path.join(
                self.folder_pdf,
                f"print_UPC_{len(imagini)}coduri_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
            )

            self.creaza_pdf_print(
                imagini_paths=[os.path.join(self.folder_imagini, img) for img in sorted(imagini)],
                pdf_output=pdf_file,
                coduri_per_pagina=coduri_per_pagina,
                lungime_cm=lungime_cm
            )

            self.status.config(text=f"✅ PDF generat: {os.path.basename(pdf_file)}", fg="#28a745")
            messagebox.showinfo(
                "Succes!",
                f"📄 PDF generat cu succes!\n\n"
                f"Coduri: {len(imagini)}\n"
                f"Per pagină: {coduri_per_pagina}\n"
                f"Lungime: {lungime_cm} cm\n\n"
                f"📁 {pdf_file}"
            )

        except ValueError as e:
            messagebox.showerror("Eroare", f"Valori invalide: {e}")
            self.status.config(text="❌ Eroare la generare PDF", fg="#dc3545")
        except Exception as e:
            messagebox.showerror("Eroare", f"Eroare: {e}")
            self.status.config(text="❌ Eroare", fg="#dc3545")

    def creaza_pdf_print(self, imagini_paths, pdf_output, coduri_per_pagina, lungime_cm):
        """Creează PDF cu layout adaptiv"""
        c = canvas.Canvas(pdf_output, pagesize=A4)
        width, height = A4

        lungime_barcode = lungime_cm * 10 * mm
        inaltime_barcode = lungime_barcode * 0.35

        margine = 10 * mm
        spatiu_util_width = width - 2 * margine
        spatiu_util_height = height - 2 * margine

        cols = int(spatiu_util_width / (lungime_barcode + 5*mm))
        rows = int(spatiu_util_height / (inaltime_barcode + 5*mm))

        max_per_page = cols * rows
        if max_per_page > coduri_per_pagina:
            cols = int(math.sqrt(coduri_per_pagina * (spatiu_util_width / spatiu_util_height)))
            rows = math.ceil(coduri_per_pagina / cols)

        coduri_pe_pagina = min(cols * rows, coduri_per_pagina)

        index = 0
        pagina = 0

        for img_path in imagini_paths:
            if index % coduri_pe_pagina == 0 and index > 0:
                c.showPage()
                pagina += 1
                self.status.config(
                    text=f"⏳ PDF: pagina {pagina}, {index}/{len(imagini_paths)} coduri...",
                    fg="#ffc107"
                )
                self.root.update()

            pozitie_pe_pagina = index % coduri_pe_pagina
            row = pozitie_pe_pagina // cols
            col = pozitie_pe_pagina % cols

            x = margine + col * (lungime_barcode + 5*mm)
            y = height - margine - (row + 1) * (inaltime_barcode + 5*mm)

            try:
                c.drawImage(
                    img_path,
                    x, y,
                    width=lungime_barcode,
                    height=inaltime_barcode,
                    preserveAspectRatio=True
                )
            except Exception as e:
                print(f"Eroare la desenare {img_path}: {e}")

            index += 1

        c.save()

if __name__ == "__main__":
    root = tk.Tk()
    app = GeneratorUPC(root)
    root.mainloop()
