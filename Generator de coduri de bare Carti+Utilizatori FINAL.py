# generator_biblioteca_gui_v2.py
import tkinter as tk
from tkinter import ttk, messagebox, filedialog
import os
from datetime import datetime
import barcode
from barcode.writer import ImageWriter
from PIL import Image
from reportlab.lib.pagesizes import A4
from reportlab.pdfgen import canvas
from reportlab.lib.units import mm
import math
import re

class GeneratorBiblioteca:
    def __init__(self, root):
        self.root = root
        self.root.title("🏛️ Generator Coduri de Bare - Bibliotecă")
        self.root.geometry("800x750")
        self.root.configure(bg="#667eea")

        # Directoare pentru salvare
        self.folder_carti = "coduri_carti"
        self.folder_utilizatori = "coduri_utilizatori"
        self.folder_pdf = "pdf_print"

        # Fișiere de tracking
        self.tracking_carti = os.path.join(self.folder_carti, "_tracking.txt")
        self.tracking_users = os.path.join(self.folder_utilizatori, "_tracking.txt")

        # ✅ CREEAZĂ TOATE FOLDERELE LA ÎNCEPUT
        for folder in [self.folder_carti, self.folder_utilizatori, self.folder_pdf]:
            os.makedirs(folder, exist_ok=True)
            print(f"✓ Folder creat/verificat: {folder}")

        self.setup_ui()

    def setup_ui(self):
        # Container principal
        main_frame = tk.Frame(self.root, bg="white", padx=25, pady=25)
        main_frame.pack(expand=True, fill="both", padx=15, pady=15)

        # Titlu
        title = tk.Label(
            main_frame,
            text="📚 Generator Coduri de Bare",
            font=("Segoe UI", 20, "bold"),
            bg="white",
            fg="#667eea"
        )
        title.pack(pady=(0, 15))

        # ═══════════════════════════════════════
        # SECȚIUNEA CĂRȚI
        # ═══════════════════════════════════════
        carti_frame = tk.LabelFrame(
            main_frame,
            text="📖 Coduri pentru Cărți",
            font=("Segoe UI", 12, "bold"),
            bg="white",
            fg="#667eea",
            padx=15,
            pady=12
        )
        carti_frame.pack(fill="x", pady=(0, 12))

        # Input cantitate cărți
        tk.Label(
            carti_frame,
            text="Câte coduri de cărți să generez?",
            font=("Segoe UI", 10),
            bg="white"
        ).grid(row=0, column=0, sticky="w", pady=3)

        self.carti_count = tk.Entry(
            carti_frame,
            font=("Segoe UI", 11),
            width=12
        )
        self.carti_count.grid(row=0, column=1, padx=10, pady=3)
        self.carti_count.insert(0, "100")

        # Checkbox refacere coduri lipsă
        self.carti_refacere = tk.BooleanVar(value=True)
        tk.Checkbutton(
            carti_frame,
            text="✓ Refă codurile lipsă din secvență",
            variable=self.carti_refacere,
            font=("Segoe UI", 9),
            bg="white"
        ).grid(row=1, column=0, columnspan=2, sticky="w", pady=(5, 8))

        # Buton generare cărți (mai mic)
        btn_carti = tk.Button(
            carti_frame,
            text="🎯 Generează Coduri Cărți",
            font=("Segoe UI", 10, "bold"),
            bg="#28a745",
            fg="white",
            padx=15,
            pady=8,
            cursor="hand2",
            command=self.genereaza_carti
        )
        btn_carti.grid(row=2, column=0, columnspan=2, pady=3)

        # ═══════════════════════════════════════
        # SECȚIUNEA UTILIZATORI
        # ═══════════════════════════════════════
        users_frame = tk.LabelFrame(
            main_frame,
            text="👤 Coduri pentru Utilizatori",
            font=("Segoe UI", 12, "bold"),
            bg="white",
            fg="#667eea",
            padx=15,
            pady=12
        )
        users_frame.pack(fill="x", pady=(0, 12))

        # Input cantitate utilizatori
        tk.Label(
            users_frame,
            text="Câte coduri de utilizatori să generez?",
            font=("Segoe UI", 10),
            bg="white"
        ).grid(row=0, column=0, sticky="w", pady=3)

        self.users_count = tk.Entry(
            users_frame,
            font=("Segoe UI", 11),
            width=12
        )
        self.users_count.grid(row=0, column=1, padx=10, pady=3)
        self.users_count.insert(0, "50")

        # Checkbox refacere coduri lipsă
        self.users_refacere = tk.BooleanVar(value=True)
        tk.Checkbutton(
            users_frame,
            text="✓ Refă codurile lipsă din secvență",
            variable=self.users_refacere,
            font=("Segoe UI", 9),
            bg="white"
        ).grid(row=1, column=0, columnspan=2, sticky="w", pady=(5, 8))

        # Buton generare utilizatori (mai mic)
        btn_users = tk.Button(
            users_frame,
            text="🎯 Generează Coduri Utilizatori",
            font=("Segoe UI", 10, "bold"),
            bg="#ffc107",
            fg="#333",
            padx=15,
            pady=8,
            cursor="hand2",
            command=self.genereaza_utilizatori
        )
        btn_users.grid(row=2, column=0, columnspan=2, pady=3)

        # ═══════════════════════════════════════
        # SECȚIUNEA PRINTARE PDF
        # ═══════════════════════════════════════
        pdf_frame = tk.LabelFrame(
            main_frame,
            text="🖨️ Printare PDF",
            font=("Segoe UI", 12, "bold"),
            bg="white",
            fg="#667eea",
            padx=15,
            pady=12
        )
        pdf_frame.pack(fill="x", pady=(0, 10))

        # Layout în 2 coloane pentru opțiuni
        options_left = tk.Frame(pdf_frame, bg="white")
        options_left.pack(side="left", fill="both", expand=True)

        options_right = tk.Frame(pdf_frame, bg="white")
        options_right.pack(side="right", padx=10)

        # Coloana stângă - opțiuni
        tk.Label(
            options_left,
            text="Coduri pe pagină:",
            font=("Segoe UI", 10),
            bg="white"
        ).grid(row=0, column=0, sticky="w", pady=3)

        self.coduri_per_pagina = tk.Entry(
            options_left,
            font=("Segoe UI", 10),
            width=8
        )
        self.coduri_per_pagina.grid(row=0, column=1, padx=8, pady=3)
        self.coduri_per_pagina.insert(0, "42")

        tk.Label(
            options_left,
            text="Lungime cod (cm):",
            font=("Segoe UI", 10),
            bg="white"
        ).grid(row=1, column=0, sticky="w", pady=3)

        self.lungime_cod = tk.Entry(
            options_left,
            font=("Segoe UI", 10),
            width=8
        )
        self.lungime_cod.grid(row=1, column=1, padx=8, pady=3)
        self.lungime_cod.insert(0, "5")

        tk.Label(
            options_left,
            text="Tip coduri:",
            font=("Segoe UI", 10),
            bg="white"
        ).grid(row=2, column=0, sticky="w", pady=3)

        self.tip_pdf = ttk.Combobox(
            options_left,
            font=("Segoe UI", 9),
            width=12,
            state="readonly",
            values=["Cărți (BOOK)", "Utilizatori (USER)"]
        )
        self.tip_pdf.grid(row=2, column=1, padx=8, pady=3)
        self.tip_pdf.set("Cărți (BOOK)")

        # Coloana dreaptă - buton PDF
        btn_pdf = tk.Button(
            options_right,
            text="📄 Generează\nPDF",
            font=("Segoe UI", 11, "bold"),
            bg="#dc3545",
            fg="white",
            padx=20,
            pady=15,
            cursor="hand2",
            command=self.genereaza_pdf
        )
        btn_pdf.pack()

        # Status bar
        self.status = tk.Label(
            main_frame,
            text="✅ Gata de lucru",
            font=("Segoe UI", 9),
            bg="white",
            fg="#28a745",
            anchor="w"
        )
        self.status.pack(fill="x", pady=(10, 0))

    def analizeaza_coduri_existente(self, folder, prefix):
        """Analizează codurile existente și detectează lipsuri - VERIFICĂ IMAGINILE PNG!"""
        coduri_cu_imagini = set()  # Doar codurile care au și imagine

        # Scanează imaginile fizice (PRIORITATE 1)
        if os.path.exists(folder):
            for file in os.listdir(folder):
                if file.endswith('.png'):
                    cod = file.replace('.png', '')
                    if cod.startswith(prefix):
                        coduri_cu_imagini.add(cod)

        # Citește și din tracking pentru a vedea ce TREBUIA să existe
        coduri_tracking = set()
        tracking_file = self.tracking_carti if prefix == "BOOK" else self.tracking_users
        if os.path.exists(tracking_file):
            with open(tracking_file, 'r', encoding='utf-8') as f:
                for line in f:
                    line = line.strip()
                    if line.startswith(prefix):
                        coduri_tracking.add(line)

        # Detectează codurile care sunt în tracking dar nu au imagine
        coduri_fara_imagini = coduri_tracking - coduri_cu_imagini

        if coduri_fara_imagini:
            print(f"⚠️ ATENȚIE: {len(coduri_fara_imagini)} coduri din tracking nu au imagini!")
            for cod in sorted(coduri_fara_imagini)[:10]:  # Afișează primele 10
                print(f"  - {cod}")

        # Extrage TOATE numerele (din imagini + tracking)
        toate_numerele = set()

        # Din imagini
        for cod in coduri_cu_imagini:
            match = re.search(r'\d+$', cod)
            if match:
                toate_numerele.add(int(match.group()))

        # Din tracking
        for cod in coduri_tracking:
            match = re.search(r'\d+$', cod)
            if match:
                toate_numerele.add(int(match.group()))

        if not toate_numerele:
            return 0, []

        # Maximul este cel mai mare număr VREODATĂ generat (din tracking sau imagini)
        max_numar = max(toate_numerele)

        # Detectează TOATE lipsurile - verifică imaginile fizice
        lipsa = []
        for i in range(1, max_numar + 1):
            # Verifică dacă imaginea există
            if prefix == "BOOK":
                cod = f"{prefix}{i:04d}"
            else:
                cod = f"{prefix}{i:03d}"

            img_path = os.path.join(folder, f"{cod}.png")

            if not os.path.exists(img_path):
                lipsa.append(i)

        print(f"📊 Analiză {prefix}:")
        print(f"   Max număr generat vreodată: {max_numar}")
        print(f"   Imagini existente: {len(coduri_cu_imagini)}")
        print(f"   Imagini LIPSĂ: {len(lipsa)}")

        return max_numar, lipsa

    def genereaza_carti(self):
        # La începutul funcției, după try:
        try:
            count = int(self.carti_count.get())
            if count <= 0:
                raise ValueError("Numărul trebuie să fie pozitiv")

            # ✅ ASIGURĂ-TE CĂ FOLDERUL EXISTĂ (din nou, ca siguranță)
            os.makedirs(self.folder_carti, exist_ok=True)


            # Analizează codurile existente (PE BAZĂ DE IMAGINI PNG!)
            max_existent, coduri_lipsa = self.analizeaza_coduri_existente(self.folder_carti, "BOOK")

            refacere = self.carti_refacere.get()

            if max_existent > 0 or coduri_lipsa:
                mesaj_info = f"📊 Situație coduri CĂRȚI (bazat pe imagini PNG):\n\n"
                mesaj_info += f"• Maxim existent: BOOK{max_existent:04d}\n"
                mesaj_info += f"• Imagini lipsă: {len(coduri_lipsa)}\n"
                mesaj_info += f"• Coduri noi de generat: {count}\n\n"

                if coduri_lipsa:
                    mesaj_info += f"⚠️ IMAGINI LIPSĂ detectate:\n"
                    # Afișează primele 20 de coduri lipsă
                    for num in coduri_lipsa[:20]:
                        mesaj_info += f"   - BOOK{num:04d}.png\n"
                    if len(coduri_lipsa) > 20:
                        mesaj_info += f"   ... și încă {len(coduri_lipsa) - 20}\n"
                    mesaj_info += "\n"

                if coduri_lipsa and refacere:
                    mesaj_info += f"✓ Voi REGENERA {len(coduri_lipsa)} imagini lipsă\n"
                    mesaj_info += f"✓ Voi ADĂUGA {count} coduri noi\n"
                    mesaj_info += f"\nTotal de generat: {len(coduri_lipsa) + count} coduri\n\n"
                else:
                    mesaj_info += f"• Numerotare va continua de la BOOK{max_existent + 1:04d}\n\n"

                if not refacere and coduri_lipsa:
                    mesaj_info += f"⚠️ ATENȚIE: {len(coduri_lipsa)} imagini lipsă NU vor fi regenerate!\n"
                    mesaj_info += "Bifează 'Refă codurile lipsă' pentru a le regenera.\n\n"

                mesaj_info += "Continui?"

                if not messagebox.askyesno("Verificare Coduri", mesaj_info):
                    return

            # Pregătește lista de coduri de generat
            coduri_de_generat = []

            # Adaugă codurile lipsă dacă e cazul
            if refacere and coduri_lipsa:
                for num in coduri_lipsa:
                    coduri_de_generat.append(f"BOOK{num:04d}")

            # Adaugă codurile noi
            start_noi = max_existent + 1
            for i in range(start_noi, start_noi + count):
                coduri_de_generat.append(f"BOOK{i:04d}")

            total = len(coduri_de_generat)
            self.status.config(text=f"⏳ Generez {total} coduri pentru cărți...", fg="#ffc107")
            self.root.update()

            # Generează codurile
            txt_file = os.path.join(self.folder_carti, f"coduri_carti_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt")

            with open(txt_file, 'w', encoding='utf-8') as f:
                f.write(f"CODURI CĂRȚI - Generat: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
                f.write("="*50 + "\n")
                if refacere and coduri_lipsa:
                    f.write(f"REGENERATE (imagini lipsă): {len(coduri_lipsa)} coduri\n")
                f.write(f"NOI: {count} coduri\n")
                f.write(f"TOTAL: {total} coduri\n")
                f.write("="*50 + "\n\n")

                for idx, cod in enumerate(coduri_de_generat, 1):
                    f.write(f"{cod}\n")

                    # REGENEREAZĂ IMAGINEA (chiar dacă există în tracking)
                    self.genereaza_imagine_barcode(cod, self.folder_carti)

                    if idx % 50 == 0:
                        self.status.config(text=f"⏳ Generat {idx}/{total} coduri cărți...")
                        self.root.update()

            # Actualizează tracking
            self.actualizeaza_tracking(self.tracking_carti, coduri_de_generat)

            self.status.config(text=f"✅ {total} coduri cărți generate!", fg="#28a745")
            messagebox.showinfo(
                "Succes!",
                f"✅ {total} coduri pentru cărți generate!\n\n"
                f"{'🔄 Regenerate (imagini lipsă): ' + str(len(coduri_lipsa)) + chr(10) if coduri_lipsa and refacere else ''}"
                f"➕ Noi: {count}\n\n"
                f"📁 Imagini: {self.folder_carti}/\n"
                f"📄 Lista: {txt_file}"
            )

        except ValueError as e:
            messagebox.showerror("Eroare", f"Număr invalid: {e}")
            self.status.config(text="❌ Eroare la generare", fg="#dc3545")
        except Exception as e:
            messagebox.showerror("Eroare", f"Eroare: {e}")
            self.status.config(text="❌ Eroare la generare", fg="#dc3545")

    def genereaza_utilizatori(self):
        try:
            count = int(self.users_count.get())
            if count <= 0:
                raise ValueError("Numărul trebuie să fie pozitiv")

            # ✅ ASIGURĂ-TE CĂ FOLDERUL EXISTĂ
            os.makedirs(self.folder_utilizatori, exist_ok=True)



            # Analizează codurile existente (PE BAZĂ DE IMAGINI PNG!)
            max_existent, coduri_lipsa = self.analizeaza_coduri_existente(self.folder_utilizatori, "USER")

            refacere = self.users_refacere.get()

            if max_existent > 0 or coduri_lipsa:
                mesaj_info = f"📊 Situație coduri UTILIZATORI (bazat pe imagini PNG):\n\n"
                mesaj_info += f"• Maxim existent: USER{max_existent:03d}\n"
                mesaj_info += f"• Imagini lipsă: {len(coduri_lipsa)}\n"
                mesaj_info += f"• Coduri noi de generat: {count}\n\n"

                if coduri_lipsa:
                    mesaj_info += f"⚠️ IMAGINI LIPSĂ detectate:\n"
                    for num in coduri_lipsa[:15]:
                        mesaj_info += f"   - USER{num:03d}.png\n"
                    if len(coduri_lipsa) > 15:
                        mesaj_info += f"   ... și încă {len(coduri_lipsa) - 15}\n"
                    mesaj_info += "\n"

                if coduri_lipsa and refacere:
                    mesaj_info += f"✓ Voi REGENERA {len(coduri_lipsa)} imagini lipsă\n"
                    mesaj_info += f"✓ Voi ADĂUGA {count} coduri noi\n"
                    mesaj_info += f"\nTotal de generat: {len(coduri_lipsa) + count} coduri\n\n"
                else:
                    mesaj_info += f"• Numerotare va continua de la USER{max_existent + 1:03d}\n\n"

                if not refacere and coduri_lipsa:
                    mesaj_info += f"⚠️ ATENȚIE: {len(coduri_lipsa)} imagini lipsă NU vor fi regenerate!\n"
                    mesaj_info += "Bifează 'Refă codurile lipsă' pentru a le regenera.\n\n"

                mesaj_info += "Continui?"

                if not messagebox.askyesno("Verificare Coduri", mesaj_info):
                    return

            # Pregătește lista de coduri
            coduri_de_generat = []

            if refacere and coduri_lipsa:
                for num in coduri_lipsa:
                    coduri_de_generat.append(f"USER{num:03d}")

            start_noi = max_existent + 1
            for i in range(start_noi, start_noi + count):
                coduri_de_generat.append(f"USER{i:03d}")

            total = len(coduri_de_generat)
            self.status.config(text=f"⏳ Generez {total} coduri pentru utilizatori...", fg="#ffc107")
            self.root.update()

            # Generează codurile
            txt_file = os.path.join(self.folder_utilizatori, f"coduri_utilizatori_{datetime.now().strftime('%Y%m%d_%H%M%S')}.txt")

            with open(txt_file, 'w', encoding='utf-8') as f:
                f.write(f"CODURI UTILIZATORI - Generat: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
                f.write("="*50 + "\n")
                if refacere and coduri_lipsa:
                    f.write(f"REGENERATE (imagini lipsă): {len(coduri_lipsa)} coduri\n")
                f.write(f"NOI: {count} coduri\n")
                f.write(f"TOTAL: {total} coduri\n")
                f.write("="*50 + "\n\n")

                for idx, cod in enumerate(coduri_de_generat, 1):
                    f.write(f"{cod}\n")

                    # REGENEREAZĂ IMAGINEA
                    self.genereaza_imagine_barcode(cod, self.folder_utilizatori)

                    if idx % 25 == 0:
                        self.status.config(text=f"⏳ Generat {idx}/{total} coduri utilizatori...")
                        self.root.update()

            self.actualizeaza_tracking(self.tracking_users, coduri_de_generat)

            self.status.config(text=f"✅ {total} coduri utilizatori generate!", fg="#28a745")
            messagebox.showinfo(
                "Succes!",
                f"✅ {total} coduri pentru utilizatori generate!\n\n"
                f"{'🔄 Regenerate (imagini lipsă): ' + str(len(coduri_lipsa)) + chr(10) if coduri_lipsa and refacere else ''}"
                f"➕ Noi: {count}\n\n"
                f"📁 Imagini: {self.folder_utilizatori}/\n"
                f"📄 Lista: {txt_file}"
            )

        except ValueError as e:
            messagebox.showerror("Eroare", f"Număr invalid: {e}")
            self.status.config(text="❌ Eroare la generare", fg="#dc3545")
        except Exception as e:
            messagebox.showerror("Eroare", f"Eroare: {e}")
            self.status.config(text="❌ Eroare la generare", fg="#dc3545")

    def actualizeaza_tracking(self, tracking_file, coduri):
        """Actualizează fișierul de tracking cu codurile noi"""
        # Citește codurile existente
        coduri_existente = set()
        if os.path.exists(tracking_file):
            with open(tracking_file, 'r', encoding='utf-8') as f:
                for line in f:
                    cod = line.strip()
                    if cod:
                        coduri_existente.add(cod)

        # Adaugă codurile noi
        coduri_existente.update(coduri)

        # Scrie toate codurile sortate
        with open(tracking_file, 'w', encoding='utf-8') as f:
            for cod in sorted(coduri_existente):
                f.write(f"{cod}\n")

    def genereaza_imagine_barcode(self, cod, folder):
        """Generează imaginea codului de bare"""
        try:
            Code128 = barcode.get_barcode_class('code128')
            code = Code128(cod, writer=ImageWriter())
            filename = os.path.join(folder, cod)
            code.save(filename)
        except Exception as e:
            print(f"Eroare la generare {cod}: {e}")

    def genereaza_pdf(self):
        try:
            coduri_per_pagina = int(self.coduri_per_pagina.get())
            lungime_cm = float(self.lungime_cod.get())
            tip = self.tip_pdf.get()

            if coduri_per_pagina <= 0 or lungime_cm <= 0:
                raise ValueError("Valorile trebuie să fie pozitive")

            folder_sursa = self.folder_carti if "Cărți" in tip else self.folder_utilizatori
            prefix = "BOOK" if "Cărți" in tip else "USER"

            imagini = [f for f in os.listdir(folder_sursa) if f.endswith('.png')]

            if not imagini:
                messagebox.showwarning(
                    "Atenție",
                    f"Nu există coduri generate în {folder_sursa}!\n"
                    f"Mai întâi generează coduri pentru {tip}."
                )
                return

            self.status.config(text=f"⏳ Generez PDF cu {len(imagini)} coduri...", fg="#ffc107")
            self.root.update()

            pdf_file = os.path.join(
                self.folder_pdf,
                f"print_{prefix}_{len(imagini)}coduri_{datetime.now().strftime('%Y%m%d_%H%M%S')}.pdf"
            )

            self.creaza_pdf_print(
                imagini_paths=[os.path.join(folder_sursa, img) for img in sorted(imagini)],
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
        inaltime_barcode = lungime_barcode * 0.4

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
    app = GeneratorBiblioteca(root)
    root.mainloop()