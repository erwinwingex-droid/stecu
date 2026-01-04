# 🚀 QUICK START GUIDE - FITUR ASSIGNMENT PESANAN KE KURIR

## ⚡ Setup (Lakukan 1x Saja)

### Step 1: Buat Tabel Database
```
1. Buka browser: http://localhost/Adin-Laundry/setup_order_assignments.php
2. Tunggu sampai muncul: "✅ Tabel 'order_assignments' berhasil dibuat!"
3. Selesai! Tabel sudah ready
```

### Step 2: Verifikasi Data
```
1. Buka: http://localhost/Adin-Laundry/test_assignment_feature.php
2. Periksa apakah ada kurir dan pesanan
3. Jika belum ada, buat pesanan dan kurir terlebih dahulu
```

---

## 👨‍💼 Cara Admin Mengirim Pesanan ke Kurir

### 📋 Langkah-Langkah:

1. **Login Admin**
   - Buka: `http://localhost/Adin-Laundry/admin/`
   - Masukkan username & password admin

2. **Buka Data Pesanan**
   - Klik menu: **"Data Pesanan"**
   - Atau akses langsung: `http://localhost/Adin-Laundry/admin/orders.php`

3. **Lihat Pesanan Baru**
   - Klik tab: **"Pesanan Baru"** (jika ada)
   - Atau pilih pesanan dari tabel "Semua Pesanan" dengan status = "Konfirmasi"

4. **Cari Pesanan yang Status "Konfirmasi"**
   - Lihat kolom "Tracking" 
   - Cari yang status: **"Pesanan Dibuat"** (ini = pending)
   - ⚠️ Pesanan dengan status lain TIDAK bisa dikirim ke kurir

5. **Pilih Kurir**
   - Pada pesanan tersebut, lihat kolom "WA Pelanggan"
   - Ada dropdown: **"Pilih Kurir"**
   - Klik dropdown dan pilih kurir yang diinginkan

6. **Konfirmasi**
   - Dialog akan muncul
   - Klik **"OK"** untuk confirm
   - Atau **"Cancel"** untuk batal

7. **Selesai!**
   - Akan muncul popup: **"✅ Pesanan berhasil dikirim ke kurir [Nama]"**
   - Halaman akan reload otomatis
   - Tracking status berubah menjadi: **"Kurir Menjemput"**

---

## 👨‍🔧 Cara Kurir Melihat Pesanan yang Di-Assign

### 📋 Langkah-Langkah:

1. **Login Kurir**
   - Buka: `http://localhost/Adin-Laundry/kurir/`
   - Masukkan username & password kurir

2. **Buka Dashboard**
   - Sistem akan otomatis menampilkan halaman dashboard
   - Atau akses: `http://localhost/Adin-Laundry/kurir/dashboard.php`

3. **Lihat Pesanan Hari Ini**
   - Pesanan akan tampil di tab: **"Pesanan Baru"**
   - HANYA pesanan yang di-assign oleh admin yang muncul
   - Pesanan kurir lain TIDAK akan muncul

4. **Update Tracking**
   - Klik tombol **"Route"** (warna kuning)
   - Pilih status:
     - **"Kurir Menjemput"** → sedang menjemput barang
     - **"Dalam Pengantaran"** → sudah siap dikirim
     - **"Selesai"** → barang sudah diterima pelanggan
   - Klik **"Simpan"**
   - Tracking history akan tercatat

---

## ✅ Checklist: Verifikasi Sistem Bekerja

### Setup OK?
- [ ] Tabel `order_assignments` sudah dibuat
- [ ] Ada minimal 1 kurir aktif
- [ ] Ada minimal 1 pesanan dengan status "pending"

### Admin OK?
- [ ] Bisa login admin
- [ ] Bisa lihat tab "Pesanan Baru"
- [ ] Dropdown "Pilih Kurir" muncul
- [ ] Bisa klik dropdown dan pilih kurir
- [ ] Muncul dialog konfirmasi
- [ ] Setelah OK, muncul pesan sukses
- [ ] Halaman reload & tracking status berubah

### Kurir OK?
- [ ] Bisa login kurir
- [ ] Dashboard menampilkan pesanan yang di-assign
- [ ] Kurir lain tidak melihat pesanan ini
- [ ] Bisa update tracking status

---

## 🔍 Troubleshooting

### ❌ Masalah: Dropdown "Pilih Kurir" tidak muncul

**Solusi:**
- Pastikan pesanan memiliki status tracking = **"pending"** (Pesanan Dibuat)
- Jika sudah berubah status, pesanan tidak bisa dikirim lagi
- Cek di kolom "Tracking" - harus "Pesanan Dibuat"

### ❌ Masalah: Pesanan tidak muncul di dashboard kurir

**Solusi:**
- Verifikasi pesanan sudah di-assign ke kurir yang login
- Periksa akun kurir yang login (phone/whatsapp harus sesuai dengan couriers table)
- Pesanan harus dari hari ini
- Buka: `http://localhost/Adin-Laundry/test_assignment_feature.php`
  - Lihat section "Assignment Pesanan ke Kurir"
  - Verifikasi pesanan ada di sana dengan courier_id yang benar

### ❌ Masalah: Dropdown kurir kosong / tidak ada pilihan

**Solusi:**
- Pastikan ada kurir yang active (`is_active = 1`)
- Buka: `http://localhost/Adin-Laundry/debug_kurir_relation.php`
- Lihat section "Data Couriers" - pastikan ada kurir dengan `is_active = 1`

### ❌ Masalah: Error "Pesanan tidak ditemukan atau bukan dari hari ini"

**Solusi:**
- Pastikan pesanan dibuat HARI INI
- Kalau pesanan lama, tidak bisa di-assign ulang

### ❌ Masalah: Error "Pesanan sudah dikirim ke kurir ini"

**Solusi:**
- Pesanan sudah di-assign ke kurir ini sebelumnya
- Jika ingin assign ke kurir lain, harus buat pesanan baru

---

## 📊 Tools untuk Testing & Debugging

### 1. **Setup Database**
```
http://localhost/Adin-Laundry/setup_order_assignments.php
→ Buat/verifikasi tabel order_assignments
```

### 2. **Lihat Data**
```
http://localhost/Adin-Laundry/test_assignment_feature.php
→ Lihat struktur tabel, kurir, pesanan, assignment
```

### 3. **Debug Relasi**
```
http://localhost/Adin-Laundry/debug_kurir_relation.php
→ Lihat matching user-kurir
```

### 4. **Simulasi Lengkap**
```
http://localhost/Adin-Laundry/test_assignment_simulation.php
→ Jalankan simulasi lengkap assignment
→ Test query dari perspektif kurir
```

---

## 📚 File & Lokasi

### File Utama:
- `admin/orders.php` - Admin mengirim pesanan ke kurir
- `kurir/dashboard.php` - Kurir lihat pesanan
- `Api/assign_order_to_courier.php` - Backend processing

### File Testing:
- `setup_order_assignments.php` - Setup tabel
- `test_assignment_feature.php` - Verifikasi struktur
- `debug_kurir_relation.php` - Debug relasi
- `test_assignment_simulation.php` - Simulasi
- `test_api_assignment.php` - Test API

### File Dokumentasi:
- `DOKUMENTASI_FITUR_ASSIGNMENT_KURIR.md` - Dokumentasi lengkap
- `RINGKASAN_IMPLEMENTASI_ASSIGNMENT.md` - Ringkasan teknis

---

## 💡 Tips & Tricks

### 📌 Tip 1: Bulk Assignment
Jika ada banyak pesanan, lakukan assignment satu-satu dengan urutan kurir bergantian untuk distribusi merata.

### 📌 Tip 2: Monitoring
Akses `test_assignment_feature.php` secara berkala untuk:
- Lihat pesanan yang belum di-assign
- Lihat distribusi pesanan per kurir
- Monitor assignment history

### 📌 Tip 3: Kurir Log
Setiap tracking update dicatat di `tracking_history` table dengan timestamp dan kurir yang update.

### 📌 Tip 4: Reset Pesanan
Jika ingin assign pesanan ke kurir lain:
- Harus buat pesanan baru (tidak bisa "pindah" kurir)
- Atau delete assignment di database (advanced)

---

## 🎯 Ringkas

| Siapa | Tempat | Aksi | Hasil |
|---|---|---|---|
| **Admin** | Admin Dashboard | Pilih kurir → Send | Pesanan masuk ke kurir |
| **Kurir** | Kurir Dashboard | Lihat pesanan | Hanya pesanan yang di-assign |
| **Database** | order_assignments | Auto-create | Tracking history tercatat |

---

**Versi:** 1.0  
**Status:** ✅ PRODUCTION READY  
**Terakhir Update:** 13 Desember 2025

Pertanyaan? Lihat file dokumentasi atau debug tools di atas! 🚀
