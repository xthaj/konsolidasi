import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    alasanList: [
        "Kondisi Alam",
        "Masa Panen",
        "Gagal Panen",
        "Promo dan Diskon",
        "Harga Stok Melimpah",
        "Stok Menipis/Langka",
        "Harga Kembali Normal",
        "Turun Harga dari Distributor",
        "Kenaikan Harga dari Distributor",
        "Perbedaan Kualitas",
        "Supplier Menaikkan Harga",
        "Supplier Menurunkan Harga",
        "Persaingan Harga",
        "Permintaan Meningkat",
        "Permintaan Menurun",
        "Operasi Pasar",
        "Kebijakan Pemerintah Pusat",
        "Kebijakan Pemerintah Daerah",
        "Kesalahan Petugas Mencacah",
        "Penurunan Produksi",
        "Kenaikan Produksi",
        "Salah Entri Data",
        "Penggantian Responden",
        "Lainnya",
    ],
    successMessage: "",
    failMessage: "",

    init() {
        // No API calls needed; alasanList is static
    },
}));

Alpine.start();
