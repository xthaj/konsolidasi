import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    modalMessage: "",
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
    selectedKdLevel: "01",
    pendingKdLevel: "01",
    sort: "kd_komoditas",
    direction: "asc",
    wilayahLevel: "",
    kd_wilayah: "",
    status_rekon: "00",
    isPusat: true,
    errorMessage: "",
    kdLevelTitles: {
        "01": "HK",
        "02": "HK Desa",
        "03": "HPB",
        "04": "HPed",
        "05": "HP",
    },

    async fetchWrapper(
        url,
        options = {},
        successMessage = "Operasi berhasil",
        showSuccessModal = false
    ) {
        try {
            const response = await fetch(url, {
                method: "GET",
                ...options,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    ...(options.method && options.method !== "GET"
                        ? {
                              "X-CSRF-TOKEN": document.querySelector(
                                  'meta[name="csrf-token"]'
                              )?.content,
                          }
                        : {}),
                    ...options.headers,
                },
            });
            const result = await response.json();

            if (!response.ok) {
                this.modalMessage =
                    result.message ||
                    "Terjadi kesalahan saat memproses permintaan.";
                this.$dispatch("open-modal", "error-modal");
                throw new Error(this.modalMessage);
            }

            if (showSuccessModal) {
                this.modalMessage = result.message || successMessage;
                this.$dispatch("open-modal", "success-modal");
            }
            return result;
        } catch (error) {
            console.error(`Fetch error at ${url}:`, error);
            this.modalMessage =
                result.message ||
                "Terjadi kesalahan saat memproses permintaan.";
            this.$dispatch("open-modal", "error-modal");
            throw error;
        }
    },

    async init() {
        this.loading = true;
        try {
            const [wilayahResponse, komoditasResponse, bulanTahunResponse] =
                await Promise.all([
                    this.fetchWrapper(
                        "/inflasi-segmented-wilayah",
                        {},
                        "Data wilayah berhasil dimuat",
                        false
                    ),
                    this.fetchWrapper(
                        "/all-komoditas",
                        {},
                        "Data komoditas berhasil dimuat",
                        false
                    ),
                    this.fetchWrapper(
                        "/bulan-tahun",
                        {},
                        "Data bulan dan tahun berhasil dimuat",
                        false
                    ),
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
            this.pendingKdLevel = "01";
            this.wilayahLevel = "semua-provinsi";
            this.kd_wilayah = "0";
            this.selectedKomoditas = "";
            this.status_rekon = "00";
            this.isPusat = true;
            this.selectedProvince = "";
            this.selectedKabkot = "";
            this.sort = "kd_komoditas";
            this.direction = "asc";

            this.updateKdWilayah();
            await this.fetchData();
        } catch (error) {
            console.error("Failed to load data:", error);
            this.message = "Gagal memuat data awal.";
            this.data.rekonsiliasi = [];
            this.data.title = "Pembahasan Rekonsiliasi";
        } finally {
            this.loading = false;
        }
    },

    setPageTitle(title) {
        this.pageTitle = `${title} - Pembahasan`;
        document.title = this.pageTitle;
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
        try {
            const params = new URLSearchParams({
                bulan: this.bulan,
                tahun: this.tahun,
                kd_level: this.pendingKdLevel,
                level_wilayah: this.wilayahLevel,
                kd_wilayah: this.kd_wilayah,
                kd_komoditas: this.selectedKomoditas,
                status_rekon: this.status_rekon,
                sort: this.sort,
                direction: this.direction,
            });

            const result = await this.fetchWrapper(
                `/api/rekonsiliasi/pembahasan?${params}`,
                {},
                "Data berhasil dimuat",
                false
            );

            this.selectedKdLevel = this.pendingKdLevel;
            this.data.rekonsiliasi = result.data.rekonsiliasi || [];
            this.data.title = result.data.title || "Pembahasan Rekonsiliasi";
            this.message = result.message || "Data berhasil dimuat.";

            const title =
                this.kdLevelTitles[this.selectedKdLevel] ||
                "Pembahasan Rekonsiliasi";
            this.setPageTitle(title);
        } catch (error) {
            console.error("Fetch error:", error);
            this.data.rekonsiliasi = [];
        }
    },

    async togglePembahasan(rekonsiliasiId, checked) {
        try {
            const result = await this.fetchWrapper(
                `/api/rekonsiliasi/${rekonsiliasiId}/pembahasan`,
                {
                    method: "PATCH",
                    body: JSON.stringify({
                        pembahasan: checked ? 1 : 0,
                    }),
                },
                "Data inflasi berhasil diperbarui",
                false
            );

            this.data.rekonsiliasi = this.data.rekonsiliasi.map((item) => {
                if (item.rekonsiliasi_id === rekonsiliasiId) {
                    return { ...item, pembahasan: checked ? 1 : 0 };
                }
                return item;
            });

            return true;
        } catch (error) {
            console.error("Failed to update data:", error);
            return false;
        }
    },
}));

Alpine.start();
