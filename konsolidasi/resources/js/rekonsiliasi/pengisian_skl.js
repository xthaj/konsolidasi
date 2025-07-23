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
    wilayahLevel: "kabkot",
    kd_wilayah: "",
    status_rekon: "00",
    isProvinsi: false,
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
                userResponse,
                wilayahResponse,
                komoditasResponse,
                bulanTahunResponse,
                alasanResponse,
            ] = await Promise.all([
                this.fetchWrapper(
                    "/rekonsiliasi/user-provinsi",
                    {},
                    "User data berhasil dimuat",
                    false
                ),
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
                this.fetchWrapper(
                    "/all-alasan",
                    {},
                    "Data alasan berhasil dimuat",
                    false
                ),
            ]);

            // Process user data
            if (!userResponse.success) {
                throw new Error("Failed to fetch user data");
            }
            const userData = userResponse.data;
            this.isProvinsi = userData.is_provinsi;
            this.kd_wilayah = userData.kd_wilayah;
            this.wilayahLevel = userData.wilayah_level;
            this.selectedProvince = userData.kd_wilayah;
            this.selectedKabkot = userData.is_provinsi
                ? ""
                : userData.kd_wilayah;

            // Process wilayah data
            this.provinces = wilayahResponse.data.provinces || [];
            this.kabkots = wilayahResponse.data.kabkots || [];

            // Process komoditas data
            this.komoditas = komoditasResponse.data || [];

            // Process bulan and tahun data (restrict to active period)
            const aktifData = bulanTahunResponse.data.bt_aktif;
            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;

            // Process alasan data
            this.alasan = alasanResponse.data || [];
            this.filteredAlasan = [...this.alasan];

            // Set default values
            this.selectedKomoditas = "";
            this.updateKdWilayah();
            await this.fetchData();
        } catch (error) {
            console.error("Failed to load data:", error);
            this.message = "Gagal memuat data awal.";
            this.data.rekonsiliasi = [];
        } finally {
            this.loading = false;
        }
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

    get isActivePeriod() {
        return true; // Always true, only active period is allowed
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    updateWilayahOptions() {
        if (!this.isProvinsi) return; // Kabkot users cannot change wilayahLevel
        this.selectedKabkot = "";
        this.updateKdWilayah();
    },

    updateKdWilayah() {
        this.kd_wilayah = !this.isProvinsi
            ? this.selectedKabkot
            : this.wilayahLevel === "provinsi"
            ? this.selectedProvince
            : this.wilayahLevel === "kabkot" &&
              this.selectedKabkot &&
              this.selectedKdLevel === "01"
            ? this.selectedKabkot
            : "";
    },

    checkFormValidity() {
        if (
            !this.bulan ||
            !this.tahun ||
            !this.selectedKdLevel ||
            !this.wilayahLevel ||
            !this.kd_wilayah
        ) {
            this.errorMessage = "Harap lengkapi semua field yang diperlukan.";
            return false;
        }
        if (this.wilayahLevel === "kabkot" && this.selectedKdLevel !== "01") {
            this.errorMessage =
                "Data kabupaten/kota hanya tersedia untuk Harga Konsumen Kota.";
            return false;
        }
        if (
            this.wilayahLevel === "kabkot" &&
            !this.selectedKabkot &&
            this.isProvinsi
        ) {
            this.errorMessage = "Harap pilih kabupaten/kota.";
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
                kd_level: this.selectedKdLevel,
                level_wilayah: this.wilayahLevel,
                kd_wilayah: this.kd_wilayah,
                kd_komoditas: this.selectedKomoditas,
                status_rekon: this.status_rekon,
            });

            const result = await this.fetchWrapper(
                `/api/rekonsiliasi/pengisian?${params}`,
                {},
                "Data rekonsiliasi berhasil dimuat",
                false
            );

            this.data.rekonsiliasi = result.data.rekonsiliasi || [];
            this.data.title = result.data.title || "Rekonsiliasi";
            this.message = result.message;
        } catch (error) {
            console.error("Fetch error:", error);
            this.errorMessage = this.modalMessage || "Gagal memuat data."; // EDIT HERE: Use modalMessage
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

    async submitEditRekon() {
        if (this.selectedAlasan.length === 0) {
            this.modalMessage = "Pilih minimal satu alasan.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }
        if (this.selectedAlasan.join(", ").length > 500) {
            this.modalMessage = "Terlalu banyak alasan yang dipilih.";
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
            const result = await this.fetchWrapper(
                `/rekonsiliasi/update/${this.modalData.rekonsiliasi_id}`,
                {
                    method: "PUT",
                    body: JSON.stringify(data),
                },
                "Data rekonsiliasi berhasil diperbarui",
                true
            );

            this.$dispatch("close-modal", "edit-rekonsiliasi");
            this.fetchData();
        } catch (error) {
            console.error("Submit error:", error);
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
