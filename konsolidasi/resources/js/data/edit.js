import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,

    modalMessage: "",
    status: "no_filters",
    message: "Silakan pilih filter untuk menampilkan data.",
    data: { inflasi: null, title: null, kd_level: null, kd_wilayah: null },

    bulan: "",
    tahun: "",
    activeBulan: "",
    activeTahun: "",
    tahunOptions: [],

    provinces: [],
    kabkots: [],
    komoditas: [],
    wilayahLevel: "pusat",
    selectedProvince: "",
    selectedKabkot: "",
    selectedKomoditas: "",
    selectedKdLevel: "01", // Default to Harga Konsumen Kota
    isPusat: true,
    kd_wilayah: "0",
    sort: "kd_komoditas",
    direction: "asc",
    deleteRekonsiliasi: false,
    modalData: { id: "", komoditas: "" },
    data: {
        inflasi: {
            data: [],
            current_page: 1,
            last_page: 1,
            prev_page_url: null,
            next_page_url: null,
        },
        title: null,
        kd_level: null,
        kd_wilayah: null,
    },
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

    async init() {
        this.loading = true;
        try {
            const [wilayahResponse, komoditasResponse, bulanTahunResponse] =
                await Promise.all([
                    fetch("/api/wilayah").then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `Wilayah API error! status: ${res.status}`
                            );
                        return res.json();
                    }),
                    fetch("/api/komoditas").then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `Komoditas API error! status: ${res.status}`
                            );
                        return res.json();
                    }),
                    fetch("/api/bulan_tahun").then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `BulanTahun API error! status: ${res.status}`
                            );
                        return res.json();
                    }),
                ]);

            // Process wilayah data
            this.provinces = wilayahResponse.data.provinces || [];
            this.kabkots = wilayahResponse.data.kabkots || [];

            // Process komoditas data
            this.komoditas = komoditasResponse.data || [];

            // Process bulan and tahun data
            const aktifData = bulanTahunResponse.data.bt_aktif;
            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;
            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;
            this.tahunOptions =
                bulanTahunResponse.data.tahun ||
                (aktifData ? [aktifData.tahun] : []);

            // Set default values for form fields
            this.selectedKdLevel = "01";
            this.selectedKomoditas = "";
            this.wilayahLevel = "pusat";
            this.isPusat = true;
            this.selectedProvince = "";
            this.selectedKabkot = "";
            this.kd_wilayah = "0";
            this.sort = "kd_komoditas";
            this.direction = "asc";

            // Update kd_wilayah
            this.updateKdWilayah();

            // Fetch initial data
            // Improve:
            await this.fetchData();
        } catch (error) {
            console.error("Failed to load data:", error);
            this.status = "error";
            this.message = "Gagal memuat data awal.";
            this.data.inflasi = [];
        } finally {
            this.loading = false;
        }
    },

    get isActivePeriod() {
        const result =
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun;
        return result;
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    updateKdWilayah() {
        this.isPusat = this.wilayahLevel === "pusat";
        if (this.selectedKdLevel === "00") {
            this.wilayahLevel = "pusat";
            this.isPusat = true;
            this.kd_wilayah = "0";
        } else if (this.wilayahLevel === "pusat") {
            this.kd_wilayah = "0";
        } else if (
            this.wilayahLevel === "kabkot" &&
            this.selectedKabkot &&
            this.selectedKdLevel === "01"
        ) {
            this.kd_wilayah = this.selectedKabkot;
        } else if (this.wilayahLevel === "provinsi" && this.selectedProvince) {
            this.kd_wilayah = this.selectedProvince;
        } else {
            this.kd_wilayah = "";
        }
    },

    checkFormValidity() {
        if (!this.bulan || !this.tahun || !this.selectedKdLevel) {
            return false;
        }

        if (this.selectedKdLevel === "00") {
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
        if (this.selectedKdLevel === "00") return "";
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

    async fetchData(page) {
        if (!this.checkFormValidity()) {
            return;
        }

        // this.loading = true;

        try {
            const params = new URLSearchParams({
                bulan: this.bulan,
                tahun: this.tahun,
                kd_level: this.selectedKdLevel,
                kd_wilayah: this.kd_wilayah,
                kd_komoditas: this.selectedKomoditas,
                sort: this.sort,
                direction: this.direction,
                page: page,
            });

            const response = await fetch(`/api/data/edit?${params.toString()}`);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            this.status = result.status;
            this.message = result.message;
            this.data = {
                inflasi: result.data.inflasi,
                title: result.data.title,
                kd_level: this.selectedKdLevel,
                kd_wilayah: this.kd_wilayah,
            };
        } catch (error) {
            console.error("Failed to fetch data:", error);
            this.status = "error";
            this.message = "Gagal memuat data.";
            this.data.inflasi = null;
        }
    },

    openDeleteModal(id, komoditas) {
        this.modalData = { id, komoditas };
        this.deleteRekonsiliasi = false;
        this.$dispatch("open-modal", "confirm-delete");
    },

    async confirmDelete() {
        // Exit if deleteRekonsiliasi is not true
        if (!this.deleteRekonsiliasi) {
            return;
        }

        try {
            // Retrieve CSRF token
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");

            // Send DELETE request
            const response = await fetch(`/data/delete/${this.modalData.id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ delete_rekonsiliasi: true }),
            });

            // Close the confirmation modal
            this.$dispatch("close-modal", "confirm-delete");

            // Parse response body
            const result = await response.json();

            if (response.ok) {
                // On success, store the message and refresh data
                this.modalMessage = result.message || "Data berhasil dihapus!";
                await this.fetchData(this.data.inflasi.current_page);
                this.$dispatch("open-modal", "success-modal");
            } else {
                // On failure, store the error message
                this.modalMessage =
                    result.message ||
                    "Gagal menghapus data. Silakan coba lagi.";
                console.error("Delete failed:", response.status, result);
                this.$dispatch("open-modal", "error-modal");
            }
        } catch (error) {
            // Handle network or parsing errors
            this.modalMessage = "Terjadi kesalahan saat menghapus data.";
            console.error("Delete error:", error);
            this.$dispatch("open-modal", "error-modal");
        }
    },
}));

Alpine.start();
