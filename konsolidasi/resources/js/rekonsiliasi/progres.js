import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,

    modalMessage: "",
    status: "no_filters",
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
    isPusat: false,
    message: "",
    errorMessage: "",
    modalData: {
        rekonsiliasi_id: "",
        nama_wilayah: "",
        nama_komoditas: "",
        kd_level: "",
        alasan: "",
        detail: "",
        sumber: "",
    },
    detail: "",
    linkTerkait: "",
    alasan: [],
    filteredAlasan: [],
    selectedAlasan: [],

    async init() {
        this.loading = true;
        try {
            const [
                wilayahResponse,
                komoditasResponse,
                bulanTahunResponse,
                alasanResponse,
            ] = await Promise.all([
                fetch("/segmented-wilayah").then((res) => {
                    if (!res.ok)
                        throw new Error(
                            `Wilayah API error! status: ${res.status}`
                        );
                    return res.json();
                }),
                fetch("/all-komoditas").then((res) => {
                    if (!res.ok)
                        throw new Error(
                            `Komoditas API error! status: ${res.status}`
                        );
                    return res.json();
                }),
                fetch("/bulan-tahun").then((res) => {
                    if (!res.ok)
                        throw new Error(
                            `BulanTahun API error! status: ${res.status}`
                        );
                    return res.json();
                }),
                fetch("/all-alasan").then((res) => {
                    if (!res.ok)
                        throw new Error(
                            `Alasan API error! status: ${res.status}`
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

            this.alasan = alasanResponse.data || [];
            this.filteredAlasan = [...this.alasan];

            // Set default values for form fields
            this.selectedKdLevel = "01";
            this.wilayahLevel = "semua-provinsi";
            this.kd_wilayah = "0";
            this.selectedKomoditas = "";
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
            // If user selected "all provinces" or "all kabkot", set code to "0"
            this.wilayahLevel === "semua" ||
            this.wilayahLevel === "semua-provinsi" ||
            this.wilayahLevel === "semua-kabkot"
                ? "0"
                : // Else, if user selected a specific province, use that province code
                this.wilayahLevel === "provinsi" && this.selectedProvince
                ? this.selectedProvince
                : // Else, if user selected a specific kabkot AND the selected level is "01", use that kabkot code
                this.wilayahLevel === "kabkot" &&
                  this.selectedKabkot &&
                  this.selectedKdLevel === "01"
                ? this.selectedKabkot
                : // If none of the conditions match, leave it as an empty string
                  "";
    },

    checkFormValidity() {
        // if (
        //     !this.bulan ||
        //     !this.tahun ||
        //     !this.selectedKdLevel ||
        //     !this.status ||
        //     !this.wilayahLevel
        // ) {
        //     this.errorMessage =
        //         "Harap isi bulan, tahun, level harga, status, dan level wilayah.";
        //     return false;
        // }
        if (
            this.wilayahLevel === "semua" ||
            this.wilayahLevel === "semua-provinsi" ||
            this.wilayahLevel === "semua-kabkot"
        ) {
            if (!this.isPusat) {
                this.errorMessage =
                    "Hanya pengguna pusat yang dapat mengakses semua provinsi atau kabupaten/kota.";
                return false;
            }
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

    async fetchData() {
        if (!this.checkFormValidity()) return;
        this.errorMessage = "";

        try {
            const params = new URLSearchParams({
                bulan: this.bulan,
                tahun: this.tahun,
                kd_level: this.selectedKdLevel,
                level_wilayah: this.wilayahLevel,
                kd_wilayah: this.kd_wilayah,
                kd_komoditas: this.selectedKomoditas,
                status_rekon: this.status_rekon,
            });
            const response = await fetch(`/api/rekonsiliasi/progres?${params}`);
            const result = await response.json();

            if (!response.ok) {
                throw new Error(
                    result.message || `HTTP error! status: ${response.status}`
                );
            }
            
            this.message = result.message;
            this.data = {
                rekonsiliasi: result.data.rekonsiliasi || [],
                title: result.data.title || "Rekonsiliasi",
            }

        } catch (error) {
            console.error("Failed to fetch data:", error);
            this.message = error.message || "Gagal memuat data.";
            this.data.rekonsiliasi = [];
        }
    },

    openEditRekonModal(
        rekonsiliasi_id,
        nama_komoditas,
        kd_level,
        alasan,
        detail,
        sumber,
        nama_wilayah
    ) {
        this.modalData = {
            rekonsiliasi_id,
            nama_komoditas,
            kd_level,
            alasan,
            detail,
            sumber,
            nama_wilayah,
        };
        this.selectedAlasan = alasan ? alasan.split(", ") : [];
        this.detail = detail || "";
        this.linkTerkait = sumber || "";
        this.$dispatch("open-modal", "edit-rekonsiliasi");
    },

    openDeleteModal(rekonsiliasi_id, nama_komoditas, nama_wilayah, kd_level) {
        this.modalData = {
            rekonsiliasi_id,
            nama_komoditas,
            nama_wilayah,
            kd_level,
        };
        this.$dispatch("open-modal", "delete-rekonsiliasi");
    },

    async submitEditRekon() {
        if (
            !Array.isArray(this.selectedAlasan) ||
            this.selectedAlasan.length === 0
        ) {
            this.modalMessage = "Isi minimal 1 alasan.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        const isValidUrl = (string) => {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        };

        if (this.linkTerkait && !isValidUrl(this.linkTerkait)) {
            this.modalMessage =
                "Tautan tidak valid. Harap masukkan URL yang benar.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        const sanitizeInput = (input) =>
            typeof input === "string"
                ? input
                      .trim()
                      .replace(/[\r\n]+/g, " ")
                      .replace(/`+/g, "'")
                      .replace(/\s+/g, " ")
                : input;

        const data = {
            alasan: this.selectedAlasan.join(", "),
            detail: sanitizeInput(this.detail),
            media: this.linkTerkait,
        };

        try {
            const response = await fetch(
                `/rekonsiliasi/update/${this.modalData.rekonsiliasi_id}`,
                {
                    method: "PUT",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data),
                }
            );

            const result = await response.json();

            if (!response.ok) {
                this.modalMessage =
                    result.message || "Gagal memperbarui data.";
                this.$dispatch("open-modal", "error-modal");
            } else {
                this.modalMessage = result.message;
                this.$dispatch("close");
                this.fetchData();
                this.$dispatch("open-modal", "success-modal");
            }
        } catch (error) {
            this.modalMessage =
                "Terjadi kesalahan saat memperbarui rekonsiliasi.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    async confirmDelete(id) {
        try {
            const response = await fetch(`/rekonsiliasi/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    "Content-Type": "application/json",
                },
            });
            const result = await response.json();
            if (response.ok) {
                this.modalMessage = result.message;
                this.$dispatch("close");
                this.$dispatch("open-modal", "success-modal");
                this.fetchData();
            } else {
                this.modalMessage =
                    result.message || "Gagal menghapus rekonsiliasi.";
                this.$dispatch("open-modal", "error-modal");
            }
        } catch (error) {
            this.modalMessage =
                "Terjadi kesalahan saat menghapus rekonsiliasi.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    searchAlasan(query) {
        query = query.toLowerCase();
        this.filteredAlasan = this.alasan.filter((alasan) =>
            alasan.keterangan.toLowerCase().includes(query)
        );
    },
}));

Alpine.start();
