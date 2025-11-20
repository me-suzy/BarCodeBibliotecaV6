# barcode_scanner_simple.py
import tkinter as tk
from tkinter import messagebox
from PIL import Image, ImageGrab, ImageTk
import json
import os
from datetime import datetime
import base64
from io import BytesIO

class SimpleBarcodeScanner:
    def __init__(self):
        self.root = tk.Tk()
        self.root.title("🔍 Barcode Scanner Simple")
        self.root.geometry("400x250")
        self.root.configure(bg="#667eea")

        self.start_x = None
        self.start_y = None
        self.selection_window = None
        self.canvas = None
        self.rect = None
        self.json_file = "coduri_scanate.json"

        self.setup_ui()

    def setup_ui(self):
        main_frame = tk.Frame(self.root, bg="#667eea")
        main_frame.pack(expand=True, fill="both", padx=20, pady=20)

        title = tk.Label(
            main_frame,
            text="🔍 Scanner Coduri de Bare",
            font=("Segoe UI", 18, "bold"),
            bg="#667eea",
            fg="white"
        )
        title.pack(pady=10)

        instructions = tk.Label(
            main_frame,
            text="1. Apasă butonul\n2. Trage cu mouse-ul peste codul de bare\n3. Introdu codul manual",
            font=("Segoe UI", 10),
            bg="#667eea",
            fg="white",
            justify="center"
        )
        instructions.pack(pady=10)

        scan_button = tk.Button(
            main_frame,
            text="📸 Selectează Zonă",
            font=("Segoe UI", 14, "bold"),
            bg="white",
            fg="#667eea",
            padx=20,
            pady=15,
            cursor="hand2",
            command=self.start_selection
        )
        scan_button.pack(pady=15)

        view_button = tk.Button(
            main_frame,
            text="📋 Vezi Coduri",
            font=("Segoe UI", 10),
            bg="#764ba2",
            fg="white",
            padx=15,
            pady=8,
            cursor="hand2",
            command=self.show_codes
        )
        view_button.pack()

    def start_selection(self):
        self.root.withdraw()
        self.root.after(200, self.create_overlay)

    def create_overlay(self):
        self.selection_window = tk.Toplevel()
        self.selection_window.attributes('-fullscreen', True)
        self.selection_window.attributes('-alpha', 0.3)
        self.selection_window.attributes('-topmost', True)

        self.canvas = tk.Canvas(
            self.selection_window,
            cursor="cross",
            bg="gray",
            highlightthickness=0
        )
        self.canvas.pack(fill="both", expand=True)

        self.canvas.create_text(
            self.selection_window.winfo_screenwidth() // 2,
            50,
            text="🖱️ Trage peste codul de bare\nESC = Anulare",
            font=("Segoe UI", 16, "bold"),
            fill="yellow"
        )

        self.canvas.bind("<ButtonPress-1>", self.on_press)
        self.canvas.bind("<B1-Motion>", self.on_drag)
        self.canvas.bind("<ButtonRelease-1>", self.on_release)
        self.selection_window.bind("<Escape>", self.cancel)

    def on_press(self, event):
        self.start_x = event.x
        self.start_y = event.y
        if self.rect:
            self.canvas.delete(self.rect)

    def on_drag(self, event):
        if self.start_x and self.start_y:
            if self.rect:
                self.canvas.delete(self.rect)
            self.rect = self.canvas.create_rectangle(
                self.start_x, self.start_y, event.x, event.y,
                outline="lime", width=3, dash=(5, 5)
            )

    def on_release(self, event):
        x1 = min(self.start_x, event.x)
        y1 = min(self.start_y, event.y)
        x2 = max(self.start_x, event.x)
        y2 = max(self.start_y, event.y)

        self.selection_window.destroy()
        self.root.after(100, lambda: self.capture(x1, y1, x2, y2))

    def cancel(self, event=None):
        if self.selection_window:
            self.selection_window.destroy()
        self.root.deiconify()

    def capture(self, x1, y1, x2, y2):
        try:
            screenshot = ImageGrab.grab(bbox=(x1, y1, x2, y2))

            # Salvează imaginea
            img_filename = f"barcode_{datetime.now().strftime('%Y%m%d_%H%M%S')}.png"
            screenshot.save(img_filename)

            # Cere cod manual
            self.root.deiconify()
            input_window = tk.Toplevel(self.root)
            input_window.title("Introdu Codul")
            input_window.geometry("400x200")

            tk.Label(
                input_window,
                text="Introdu codul de bare:",
                font=("Segoe UI", 12)
            ).pack(pady=10)

            code_entry = tk.Entry(input_window, font=("Segoe UI", 14), width=30)
            code_entry.pack(pady=10)
            code_entry.focus()

            def save_code():
                code = code_entry.get().strip()
                if code:
                    self.save_to_json(code, "MANUAL", img_filename)
                    input_window.destroy()
                    messagebox.showinfo(
                        "✅ Salvat!",
                        f"Cod: {code}\nImagine: {img_filename}"
                    )
                else:
                    messagebox.showwarning("Atenție", "Introdu un cod!")

            tk.Button(
                input_window,
                text="💾 Salvează",
                command=save_code,
                font=("Segoe UI", 12),
                bg="#667eea",
                fg="white",
                padx=20,
                pady=10
            ).pack(pady=10)

            code_entry.bind("<Return>", lambda e: save_code())

        except Exception as e:
            self.root.deiconify()
            messagebox.showerror("Eroare", str(e))

    def save_to_json(self, code, barcode_type, image_file):
        if os.path.exists(self.json_file):
            with open(self.json_file, 'r', encoding='utf-8') as f:
                try:
                    data = json.load(f)
                except:
                    data = []
        else:
            data = []

        entry = {
            "cod": code,
            "tip": barcode_type,
            "imagine": image_file,
            "data_scanare": datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        }
        data.append(entry)

        with open(self.json_file, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=4, ensure_ascii=False)

    def show_codes(self):
        if not os.path.exists(self.json_file):
            messagebox.showinfo("Info", "Nu există coduri scanate!")
            return

        with open(self.json_file, 'r', encoding='utf-8') as f:
            data = json.load(f)

        if not data:
            messagebox.showinfo("Info", "Nu există coduri scanate!")
            return

        msg = f"Total: {len(data)} coduri\n\n"
        for i, item in enumerate(data[-10:], 1):
            msg += f"{i}. {item['cod']} ({item['data_scanare']})\n"

        messagebox.showinfo("📋 Coduri Scanate", msg)

    def run(self):
        self.root.mainloop()

if __name__ == "__main__":
    app = SimpleBarcodeScanner()
    app.run()