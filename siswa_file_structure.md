# Struktur File Data Siswa (Terpisah per Aksi)

```
modules/master-data/siswa/
├── index.php              # List & Display siswa (READ)
├── add.php                 # Form tambah siswa
├── edit.php                # Form edit siswa  
├── view.php                # Detail/view siswa
├── delete.php              # Proses hapus siswa
├── process.php             # Handler untuk semua proses CRUD
├── export.php              # Export Excel/PDF
├── import.php              # Import dari Excel/CSV
├── ajax/                   # Folder untuk AJAX handlers
│   ├── get_siswa.php       # Get data siswa (JSON)
│   ├── delete_multiple.php # Delete multiple siswa
│   └── search.php          # Search autocomplete
└── includes/               # Shared components
    ├── form_fields.php     # Reusable form fields
    └── validation.php      # Validation functions
```

## Keuntungan Struktur Ini:

1. **🔧 Modular** - Setiap aksi terpisah, mudah maintenance
2. **👥 Team Work** - Bisa dikerjakan parallel oleh tim
3. **🔍 Easy Debug** - Error mudah dilacak per file
4. **♻️ Reusable** - Components bisa dipakai ulang
5. **🚀 Performance** - Load hanya yang diperlukan
6. **📝 Clean Code** - Kode lebih rapi dan terstruktur
