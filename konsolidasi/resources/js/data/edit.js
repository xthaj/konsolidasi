import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,

    modalMessage: "",
    message: "Silakan pilih filter untuk menampilkan data.",
    data: { inflasi: [], title: null, kd_level: null, kd_wilayah: null },

    bulan: "",
    tahun: "",
    activeBulan: "",
    activeTahun: "",
    edit_nilai_inflasi: "",
    edit_andil: "",
    tahunOptions: [],

    provinces: [],
    kabkots: [],
    komoditas: [],
    wilayahLevel: "pusat",
    selectedProvince: "",
    selectedKabkot: "",

    selectedKomoditas: "",
    selectedKdLevel: "01",
    isPusat: true,
    kd_wilayah: "0",
    sort: "kd_komoditas",
    direction: "asc",
    deleteRekonsiliasi: false,
    modalData: { id: "", komoditas: "" },
    inflasi_id: null,
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

    openEditModal(inflasi_id, nilai_inflasi, andil) {
        this.inflasi_id = inflasi_id;
        this.edit_nilai_inflasi = nilai_inflasi;
        this.edit_andil = andil;
        this.$dispatch("open-modal", "edit-modal");
    },

    checkEditFormValidity() {
        // 1. Check if nilai inflasi is empty
        if (
            this.edit_nilai_inflasi === null ||
            this.edit_nilai_inflasi === ""
        ) {
            this.modalMessage = "Data inflasi baru tidak boleh kosong.";
            this.$dispatch("open-modal", "error-modal");
            return false;
        }

        // 2. Find original inflasi data
        const originalItem = this.data.inflasi?.find(
            (item) => item.inflasi_id === this.inflasi_id
        );

        if (!originalItem) {
            this.modalMessage = "Data asli tidak ditemukan.";
            this.$dispatch("open-modal", "error-modal");
            return false;
        }

        // 3. Check if nilai inflasi changed
        const nilaiInflasiChanged =
            parseFloat(this.edit_nilai_inflasi) !==
            parseFloat(originalItem.nilai_inflasi);

        // 4. If wilayah is 0, also check andil
        if (this.data.kd_wilayah === "0") {
            if (this.edit_andil === null || this.edit_andil === "") {
                this.modalMessage = "Data andil baru tidak boleh kosong.";
                this.$dispatch("open-modal", "error-modal");
                return false;
            }

            const andilChanged =
                parseFloat(this.edit_andil) !==
                parseFloat(originalItem.andil || 0);

            // ✅ If nothing changed, show message
            if (!nilaiInflasiChanged && !andilChanged) {
                this.modalMessage = "Tidak ada perubahan yang terdeteksi.";
                this.$dispatch("open-modal", "error-modal");
                return false;
            }

            return true;
        }

        // 5. If wilayah is not 0, only check nilai inflasi
        if (!nilaiInflasiChanged) {
            this.modalMessage = "Tidak ada perubahan yang terdeteksi.";
            this.$dispatch("open-modal", "error-modal");
            return false;
        }

        return true;
    },

    async editData() {
        if (!this.checkEditFormValidity()) {
            return;
        }

        try {
            // Prepare the payload, conditionally including andil
            const payload = {
                nilai_inflasi: parseFloat(this.edit_nilai_inflasi),
            };

            // Only include andil if kd_wilayah is "0" and edit_andil is not null or empty
            if (
                this.kd_wilayah === "0" &&
                this.edit_andil !== null &&
                this.edit_andil !== ""
            ) {
                payload.andil = parseFloat(this.edit_andil);
            }

            const result = await this.fetchWrapper(
                `/api/data/inflasi/${this.inflasi_id}`,
                {
                    method: "PUT",
                    body: JSON.stringify(payload),
                },
                "Data inflasi berhasil diperbarui",
                true
            );

            this.$dispatch("close-modal", "edit-modal");
            this.inflasi_id = null;
            this.edit_nilai_inflasi = "";
            this.edit_andil = "";
            await this.fetchData();
        } catch (error) {
            console.error("Failed to update data:", error);
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

    async fetchData() {
        if (!this.checkFormValidity()) {
            return;
        }
        try {
            const params = new URLSearchParams({
                bulan: this.bulan,
                tahun: this.tahun,
                kd_level: this.selectedKdLevel,
                kd_wilayah: this.kd_wilayah,
                kd_komoditas: this.selectedKomoditas,
                sort: this.sort,
                direction: this.direction,
            });

            const result = await this.fetchWrapper(
                `/api/data/edit?${params.toString()}`,
                {},
                "Data berhasil dimuat",
                false
            );

            this.message = result.message;
            this.data = {
                inflasi: result.data.inflasi || [],
                title: result.data.title,
                kd_level: this.selectedKdLevel,
                kd_wilayah: this.kd_wilayah,
            };
        } catch (error) {
            console.error("Failed to fetch data:", error);
            this.message = this.modalMessage || "Gagal memuat data."; // EDIT HERE: Use modalMessage
            this.data = {
                inflasi: [],
                title: null,
                kd_level: null,
                kd_wilayah: null,
            };
        }
    },

    openDeleteModal(id, komoditas) {
        this.modalData = { id, komoditas };
        this.deleteRekonsiliasi = false;
        this.$dispatch("open-modal", "confirm-delete");
    },

    async confirmDelete() {
        if (!this.deleteRekonsiliasi) {
            return;
        }

        try {
            const result = await this.fetchWrapper(
                `/data/delete/${this.modalData.id}`,
                {
                    method: "DELETE",
                    body: JSON.stringify({ delete_rekonsiliasi: true }),
                },
                "Data berhasil dihapus!",
                true
            );
            await this.fetchData();

            this.$dispatch("close-modal", "confirm-delete");
        } catch (error) {
            console.error("Delete error:", error);
        }
    },
}));

Alpine.start();
