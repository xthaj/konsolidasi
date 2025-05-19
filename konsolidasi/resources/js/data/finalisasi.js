import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,

    bulan: "",
    tahun: "",
    activeBulan: "",
    activeTahun: "",
    tahunOptions: [],
    bulanOptions: [
        ["Januari", 1],
        ["Februari", 2],
        ["Maret", 3],
        ["April", 4],
        ["Mei", 5],
        ["Juni", 6],
        ["Juli", 7],
        ["Agustus", 8],
        ["September", 9],
        ["Oktober", 10],
        ["November", 11],
        ["Desember", 12],
    ],

    get isActivePeriod() {
        const result =
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun;
        return result;
    },

    async init() {
        this.loading = true;
        try {
            // Fetch Bulan and Tahun
            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();

            const aktifData = bulanTahunData.data.bt_aktif;
            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            this.tahunOptions =
                bulanTahunData.data.tahun ||
                (aktifData ? [aktifData.tahun] : []);
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    checkFormValidity() {
        if (!this.bulan || !this.tahun || !this.selectedKdLevel) {
            return false;
        }
        if (this.selectedKdLevel === "all") {
            this.wilayahLevel = "pusat";
            this.isPusat = true;
            return true;
        }
        if (this.wilayahLevel === "pusat") {
            return true;
        }
        if (this.wilayahLevel === "provinsi" && this.selectedProvince) {
            return true;
        }
        if (
            this.wilayahLevel === "kabkot" &&
            this.selectedProvince &&
            this.selectedKabkot &&
            this.selectedKdLevel === "01"
        ) {
            return true;
        }
        return false;
    },

    getValidationMessage() {
        if (!this.bulan) return "Bulan belum dipilih.";
        if (!this.tahun) return "Tahun belum dipilih.";
        if (!this.selectedKdLevel) return "Level harga belum dipilih.";
        if (this.selectedKdLevel === "all") return "";
        if (this.wilayahLevel === "pusat") return "";
        if (this.wilayahLevel === "provinsi" && !this.selectedProvince) {
            return "Provinsi belum dipilih.";
        }
        if (this.wilayahLevel === "kabkot") {
            if (!this.selectedProvince) return "Provinsi belum dipilih.";
            if (!this.selectedKabkot) return "Kabupaten/Kota belum dipilih.";
            if (this.selectedKdLevel !== "01") {
                return "Kabupaten/Kota hanya tersedia untuk Harga Konsumen Kota.";
            }
        }
        return "";
    },
}));

Alpine.start();
