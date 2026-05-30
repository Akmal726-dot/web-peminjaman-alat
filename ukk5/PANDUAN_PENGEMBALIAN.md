# Panduan Penggunaan Fitur Pengembalian Alat

## Alur Pengembalian Alat

### 1. Login sebagai Peminjam
- Buka aplikasi dan login dengan akun peminjam
- Pastikan role Anda adalah 'peminjam'

### 2. Akses Halaman Pengembalian
- Dari dashboard peminjam, klik menu "Pengembalian alat"
- Atau akses langsung: `peminjam/pengembalian.php`

### 3. Pilih Alat yang Akan Dikembalikan
- Pada tab "Sedang Dipinjam", Anda akan melihat daftar alat yang sedang dipinjam
- Klik tombol **"Kembalikan"** pada alat yang ingin dikembalikan
- **Catatan**: Tombol kembalikan hanya muncul untuk alat dengan status "Disetujui"

### 4. Isi Form Pengembalian
- Modal akan muncul dengan informasi alat
- Pilih **Kondisi Alat** saat dikembalikan:
  - Baik (Seperti saat dipinjam)
  - Rusak Ringan (Masih bisa digunakan)
  - Rusak Berat (Tidak bisa digunakan)
- Tambahkan **Catatan Pengembalian** (opsional)
- Klik **"Konfirmasi Pengembalian"**

### 5. Pengembalian Langsung Selesai
- Sistem akan langsung memproses pengembalian
- Status peminjaman berubah menjadi **"Dikembalikan"**
- Stok alat akan langsung dikembalikan ke sistem
- Peminjaman akan hilang dari daftar "Sedang Dipinjam"
- Peminjaman akan muncul di tab "Riwayat Pengembalian"

## Status Peminjaman

- **Disetujui**: Alat sedang dipinjam, bisa dikembalikan
- **Dikembalikan**: Pengembalian sudah selesai, stok alat sudah kembali

## Manajemen Stok Alat

- **Saat Peminjaman Disetujui**: Stok alat berkurang sesuai jumlah yang dipinjam
- **Saat Pengembalian**: Stok alat bertambah kembali sesuai jumlah yang dikembalikan

Contoh:
- Alat A memiliki stok 15
- User pinjam 2 unit → Stok menjadi 13
- User kembalikan 2 unit → Stok kembali menjadi 15

## Tips

1. Pastikan kondisi alat yang dipilih sesuai dengan keadaan sebenarnya
2. Tambahkan catatan jika ada kerusakan atau hal penting lainnya
3. Setelah pengembalian, alat akan langsung tersedia untuk peminjaman lainnya
4. Cek riwayat pengembalian untuk melihat data pengembalian sebelumnya

## Troubleshooting

- **Tidak bisa melihat tombol Kembalikan**: Pastikan status peminjaman adalah "Disetujui"
- **Modal tidak muncul**: Pastikan JavaScript aktif di browser
- **Error saat submit**: Periksa semua field sudah diisi dengan benar
- **Stok tidak bertambah**: Cek apakah pengembalian berhasil (status berubah ke "Dikembalikan")

## Catatan Penting

Sistem pengembalian alat sekarang menggunakan **pengembalian langsung** tanpa perlu konfirmasi petugas. Ketika peminjam klik "Kembalikan", alat langsung dikembalikan dan stok akan bertambah.

Sistem pengembalian alat sudah berfungsi dengan baik. Pastikan mengikuti langkah-langkah di atas.