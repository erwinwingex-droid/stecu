## Dokumentasi Fitur: Pengiriman Pesanan ke Kurir oleh Admin

### Ringkasan Fitur
Fitur ini memungkinkan admin untuk mengirim pesanan spesifik ke kurir tertentu, sehingga:
- Data pesanan tidak akan bentrok di semua kurir
- Setiap kurir hanya melihat pesanan yang dikirim kepadanya oleh admin
- Pesanan hanya bisa dikirim jika status masih "Konfirmasi" (pending)
- Data pesanan diambil dari hari ini saja

---

### 📊 Struktur Database

#### Tabel Baru: `order_assignments`
```sql
CREATE TABLE order_assignments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    courier_id INT NOT NULL,
    assigned_by VARCHAR(100) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_order_courier (order_id, courier_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (courier_id) REFERENCES couriers(id) ON DELETE CASCADE,
    
    INDEX idx_courier_id (courier_id),
    INDEX idx_assigned_at (assigned_at)
)
```

**Penjelasan Kolom:**
- `id`: Primary key
- `order_id`: ID pesanan yang di-assign
- `courier_id`: ID kurir penerima
- `assigned_by`: Username admin yang melakukan assignment
- `assigned_at`: Timestamp kapan assignment dilakukan
- `unique_order_courier`: Memastikan satu pesanan hanya di-assign sekali per kurir

---

### 🔄 Alur Kerja

#### 1️⃣ **Admin Mengirim Pesanan ke Kurir**

**File:** `admin/orders.php`
**Fitur:**
- Admin melihat daftar pesanan dari hari ini dengan status "Konfirmasi" (tracking_status = 'pending')
- Untuk setiap pesanan, ada dropdown "Pilih Kurir"
- Admin memilih kurir dan mengklik, sistem akan:
  - Validasi bahwa pesanan masih status 'pending' (konfirmasi)
  - Cek apakah pesanan sudah di-assign ke kurir lain
  - Menyimpan assignment ke tabel `order_assignments`
  - Mengubah tracking status menjadi 'picked_up' (Kurir Menjemput)
  - Mencatat di `tracking_history`

**Validasi:**
```
✓ Status tracking = 'pending' (Konfirmasi)
✓ Pesanan dari hari ini (DATE(created_at) = CURDATE())
✓ Kurir ada dan aktif
✓ Pesanan belum di-assign ke kurir yang dipilih
```

---

#### 2️⃣ **Kurir Melihat Pesanan yang Dikirim Admin**

**File:** `kurir/dashboard.php`
**Fitur:**
- Kurir HANYA melihat pesanan yang:
  - Ada di tabel `order_assignments` dengan `courier_id` = kurir itu
  - Dari hari ini (DATE(created_at) = CURDATE())
- Query menggunakan INNER JOIN dengan `order_assignments`:

```php
// Ambil pesanan yang di-assign ke kurir ini
SELECT o.* FROM orders o
INNER JOIN order_assignments oa ON o.id = oa.order_id
WHERE oa.courier_id = ? AND DATE(o.created_at) = CURDATE()
```

**Manfaat:**
- Pesanan tidak akan "bentrok" tampil di semua kurir
- Setiap kurir fokus pada pesanan mereka saja
- Admin memiliki kontrol penuh siapa yang dapat pesanan apa

---

### 📁 File yang Diubah/Dibuat

#### File Baru:
1. **`Api/assign_order_to_courier.php`**
   - Menerima POST request dari admin
   - Validasi status pesanan dan kurir
   - Menyimpan assignment ke database
   - Update tracking status menjadi 'picked_up'

#### File Diubah:
1. **`admin/orders.php`**
   - Mengganti fungsi `sendToCourier()` dari WhatsApp langsung menjadi AJAX call
   - Menambahkan validasi status 'pending' sebelum mengirim ke kurir
   - Menampilkan pesan konfirmasi yang lebih jelas

2. **`kurir/dashboard.php`**
   - Mengubah 3 query untuk mengambil pesanan
   - Menambahkan INNER JOIN dengan `order_assignments`
   - Memfilter berdasarkan `courier_id` dari kurir yang login

#### File Setup:
- **`setup_order_assignments.php`** - Membuat tabel `order_assignments` (sudah auto-create via API jika belum ada)
- **`test_assignment_feature.php`** - Untuk testing dan verifikasi

---

### 🔐 Keamanan & Validasi

**Di `Api/assign_order_to_courier.php`:**
```php
1. Hanya admin yang boleh akses (check $_SESSION['role'] === 'admin')
2. Hanya POST request yang diterima
3. Validasi pesanan:
   - Harus ada di database
   - Harus dari hari ini
   - Status tracking harus 'pending'
4. Validasi kurir:
   - Harus ada di database
   - Harus aktif (is_active = 1)
5. Cek duplikasi:
   - Pesanan tidak boleh di-assign 2x ke kurir yang sama
```

---

### 💾 Status Pesanan yang Berubah

Ketika admin mengirim pesanan ke kurir:
- **Sebelum:** tracking_status = 'pending' (Pesanan Dibuat)
- **Sesudah:** tracking_status = 'picked_up' (Kurir Menjemput)

⚠️ **Perlu Diingat:** Pesanan hanya bisa dikirim jika tracking_status = 'pending'. Jika sudah di-ubah status-nya (misalnya menjadi 'washing'), admin tidak bisa mengirimnya ke kurir.

---

### 📝 Contoh Query untuk Cek Data

**Cek pesanan yang sudah di-assign:**
```sql
SELECT oa.order_id, c.name as courier_name, oa.assigned_at
FROM order_assignments oa
LEFT JOIN couriers c ON oa.courier_id = c.id
ORDER BY oa.assigned_at DESC;
```

**Cek pesanan yang belum di-assign (masih pending):**
```sql
SELECT o.id, o.customer_id, o.tracking_status
FROM orders o
LEFT JOIN order_assignments oa ON o.id = oa.order_id
WHERE o.tracking_status = 'pending' AND oa.id IS NULL;
```

**Cek pesanan per kurir hari ini:**
```sql
SELECT o.id, u.username, c.name as courier_name
FROM orders o
INNER JOIN order_assignments oa ON o.id = oa.order_id
LEFT JOIN couriers c ON oa.courier_id = c.id
LEFT JOIN users u ON o.customer_id = u.id
WHERE DATE(o.created_at) = CURDATE() AND oa.courier_id = ?;
```

---

### 🧪 Langkah Testing

1. **Buka Dashboard Admin**
   - Navigasi ke `/admin/orders.php`
   - Lihat daftar pesanan hari ini

2. **Cari Pesanan dengan Status 'Konfirmasi'**
   - Lihat kolom "Tracking" untuk status "Pesanan Dibuat" (pending)
   - Atau buka tab "Pesanan Baru"

3. **Kirim Pesanan ke Kurir**
   - Pada pesanan tertentu, pilih kurir dari dropdown "Pilih Kurir"
   - Konfirmasi dialog yang muncul
   - Lihat alert sukses dan halaman akan reload

4. **Verifikasi Assignment**
   - Buka `/test_assignment_feature.php`
   - Lihat section "Assignment Pesanan ke Kurir"
   - Pesanan harus muncul di sana dengan courier_id

5. **Login Sebagai Kurir**
   - Login dengan akun kurir yang menerima assignment
   - Navigasi ke `/kurir/dashboard.php`
   - Pesanan yang di-assign akan muncul di tab "Pesanan Baru"
   - Kurir lain tidak akan melihat pesanan ini

6. **Verifikasi Isolasi Data**
   - Login dengan kurir yang berbeda
   - Verifikasi bahwa pesanan yang di-assign ke kurir lain tidak muncul

---

### ❌ Kasus yang Ditolak

**Pesanan TIDAK bisa dikirim ke kurir jika:**
1. Status tracking bukan 'pending' (sudah diubah oleh admin)
   ```
   ❌ Pesan: "Pesanan hanya bisa dikirim ke kurir jika status masih 'Konfirmasi'"
   ```

2. Pesanan bukan dari hari ini
   ```
   ❌ Pesan: "Pesanan tidak ditemukan atau bukan dari hari ini"
   ```

3. Kurir tidak ditemukan atau tidak aktif
   ```
   ❌ Pesan: "Kurir tidak ditemukan atau tidak aktif"
   ```

4. Pesanan sudah di-assign ke kurir yang dipilih
   ```
   ❌ Pesan: "Pesanan sudah dikirim ke kurir ini"
   ```

---

### 📊 Statistik & Monitoring

Untuk monitoring sistem, bisa gunakan:

**Pesanan yang menunggu assignment:**
```sql
SELECT COUNT(*) as waiting_count
FROM orders o
LEFT JOIN order_assignments oa ON o.id = oa.order_id
WHERE o.tracking_status = 'pending' AND oa.id IS NULL;
```

**Distribusi pesanan per kurir hari ini:**
```sql
SELECT c.name, COUNT(o.id) as order_count
FROM couriers c
LEFT JOIN order_assignments oa ON c.id = oa.courier_id
LEFT JOIN orders o ON oa.order_id = o.id AND DATE(o.created_at) = CURDATE()
WHERE c.is_active = 1
GROUP BY c.id, c.name;
```

---

### 🚀 Kesimpulan

Fitur ini memberikan kontrol penuh kepada admin dalam mendistribusikan pesanan ke kurir, mencegah duplikasi, dan memastikan setiap kurir hanya melihat pesanan yang seharusnya mereka tangani.

**Status:** ✅ Siap Digunakan
