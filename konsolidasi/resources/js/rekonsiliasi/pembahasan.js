import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    modalMessage: "",
    status: "no_filters", // API response status
    message: "Silakan pilih filter untuk menampilkan data.",
    data: { rekonsiliasi: null, title: null },

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

    provinces: [],
    kabkots: [],
    komoditas: [],
    selectedProvince: "",
    selectedKabkot: "",
    selectedKomoditas: "",
    selectedKdLevel: "",
    wilayahLevel: "",
    kd_wilayah: "",
    status_rekon: "00",
    isPusat: true,
    errorMessage: "",

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

            this.provinces = wilayahResponse.data.provinces || [];
            this.kabkots = wilayahResponse.data.kabkots || [];
            this.komoditas = komoditasResponse.data || [];

            const aktifData = bulanTahunResponse.data.bt_aktif;
            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;
            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;
            this.tahunOptions =
                bulanTahunResponse.data.tahun ||
                (aktifData ? [aktifData.tahun] : []);

            this.selectedKdLevel = "01";
            this.wilayahLevel = "semua-provinsi";
            this.kd_wilayah = "0";
            this.selectedKomoditas = "";
            this.status_rekon = "00";
            this.isPusat = true;
            this.selectedProvince = "";
            this.selectedKabkot = "";

            this.updateKdWilayah();
            await this.fetchData();
        } catch (error) {
            console.error("Failed to load data:", error);
            this.status = "error";
            this.message = "Gagal memuat data awal.";
            this.data.rekonsiliasi = [];
        } finally {
            this.loading = false;
        }
    },

    get isActivePeriod() {
        return (
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun
        );
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    updateWilayahOptions() {
        this.selectedProvince = "";
        this.selectedKabkot = "";
        this.updateKdWilayah();
    },

    updateKdWilayah() {
        this.kd_wilayah =
            this.wilayahLevel === "semua" ||
            this.wilayahLevel === "semua-provinsi" ||
            this.wilayahLevel === "semua-kabkot"
                ? "0"
                : this.wilayahLevel === "provinsi" && this.selectedProvince
                ? this.selectedProvince
                : this.wilayahLevel === "kabkot" &&
                  this.selectedKabkot &&
                  this.selectedKdLevel === "01"
                ? this.selectedKabkot
                : "";
        this.errorMessage = this.kd_wilayah
            ? ""
            : "Harap pilih wilayah yang valid.";
    },

    checkFormValidity() {
        if (
            !this.bulan ||
            !this.tahun ||
            !this.selectedKdLevel ||
            !this.wilayahLevel
        ) {
            this.errorMessage =
                "Harap isi bulan, tahun, level harga, dan level wilayah.";
            return false;
        }
        if (this.wilayahLevel === "provinsi" && !this.selectedProvince) {
            this.errorMessage = "Harap pilih provinsi.";
            return false;
        }
        if (
            this.wilayahLevel === "kabkot" &&
            (!this.selectedProvince || !this.selectedKabkot)
        ) {
            this.errorMessage = "Harap pilih provinsi dan kabupaten/kota.";
            return false;
        }
        if (this.wilayahLevel === "kabkot" && this.selectedKdLevel !== "01") {
            this.errorMessage =
                "Kabupaten/kota hanya tersedia untuk Harga Konsumen Kota.";
            return false;
        }
        return true;
    },

    async fetchData() {
        if (!this.checkFormValidity()) return;
        this.errorMessage = "";
        this.loading = true;
        try {
            const params = new URLSearchParams({
                bulan: this.bulan,
                tahun: this.tahun,
                kd_level: this.selectedKdLevel,
                level_wilayah: this.wilayahLevel,
                kd_wilayah: this.kd_wilayah,
                kd_komoditas: this.selectedKomoditas,
                status_rekon: this.status_rekon, // Updated to status_rekon
            });
            const response = await fetch(
                `/api/rekonsiliasi/pembahasan?${params}`
            );
            const result = await response.json();
            if (
                !response.ok ||
                ["validation_error", "unauthorized"].includes(result.status)
            ) {
                this.errorMessage = result.message;
                this.data = {
                    rekonsiliasi: [],
                    title: result.data?.title || "Pembahasan Rekonsiliasi",
                };
                this.status = result.status;
                return;
            }
            this.data.rekonsiliasi = result.data.rekonsiliasi || [];
            this.data.title = result.data.title || "Pembahasan Rekonsiliasi";
            this.status = result.status;
            this.message = result.message;
        } catch (error) {
            console.error("Fetch error:", error);
            this.errorMessage = "Gagal memuat data.";
            this.status = "error";
        } finally {
            this.loading = false;
        }
    },

    async togglePembahasan(rekonsiliasiId, checked) {
        this.loading = true;
        try {
            const response = await fetch(
                `/api/rekonsiliasi/${rekonsiliasiId}/pembahasan`,
                {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.content,
                    },
                    body: JSON.stringify({
                        pembahasan: checked ? 1 : 0,
                    }),
                }
            );

            if (!response.ok) {
                throw new Error(
                    `Failed to update pembahasan: ${response.statusText}`
                );
            }

            const result = await response.json();
            this.modalMessage =
                result.message || "Status pembahasan berhasil diperbarui.";
            this.$dispatch("open-modal", "success-modal");

            this.data.rekonsiliasi = this.data.rekonsiliasi.map((item) => {
                if (item.rekonsiliasi_id === rekonsiliasiId) {
                    return { ...item, pembahasan: checked ? 1 : 0 };
                }
                return item;
            });

            return true;
        } catch (error) {
            console.error("Error updating pembahasan:", error);
            this.modalMessage =
                "Gagal memperbarui status pembahasan. Silakan coba lagi.";
            this.$dispatch("open-modal", "error-modal");
            return false;
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();
