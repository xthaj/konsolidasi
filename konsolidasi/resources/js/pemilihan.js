import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    // State
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

    errorMessage: "",
    provinces: [],
    kabkots: [],
    komoditas: [],
    selectedProvinces: [],
    selectedKabkots: [],
    selectedKomoditas: [],
    selectedKdLevel: "",
    selectAllProvincesChecked: false,
    selectAllKabkotsChecked: false,
    selectAllKomoditasChecked: false,
    tableData: [],
    filteredProvinces: [],
    filteredKabkots: [],
    filteredKomoditas: [],
    modalContent: { success: false, items: [], missingItems: [] },
    modalMessage: "",
    hasUnconfirmedChanges: false,

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
                    Accept: "application/json",
                    ...(options.method &&
                    options.method !== "GET" &&
                    !options.body?.constructor?.name === "FormData"
                        ? {
                              "Content-Type": "application/json",
                              "X-CSRF-TOKEN": document.querySelector(
                                  'meta[name="csrf-token"]'
                              )?.content,
                          }
                        : options.method && options.method !== "GET"
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
                result?.message ||
                "Terjadi kesalahan saat memproses permintaan.";
            this.$dispatch("open-modal", "error-modal");
            throw error;
        }
    },

    // Computed Properties
    get isActivePeriod() {
        return (
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun
        );
    },

    // Initialization
    async init() {
        this.loading = true;
        try {
            const [wilayahResponse, komoditasResponse, bulanTahunResponse] =
                await Promise.all([
                    this.fetchWrapper(
                        "/segmented-wilayah",
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

            // Process Data
            this.provinces = wilayahResponse.data.provinces || [];
            this.kabkots = wilayahResponse.data.kabkots || [];
            this.filteredProvinces = [...this.provinces];
            this.komoditas = komoditasResponse.data || [];
            this.filteredKomoditas = [...this.komoditas];

            const aktifData = bulanTahunResponse.data.bt_aktif;
            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;
            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;
            this.tahunOptions =
                bulanTahunResponse.data.tahun ||
                (aktifData ? [aktifData.tahun] : []);
            this.selectedKdLevel = document.querySelector(
                'select[name="kd_level"]'
            ).value;

            this.updateFilteredKabkots();

            // Watchers
            this.$watch("selectedProvinces", () => {
                this.updateFilteredKabkots();
                this.updateSelectAllKabkots();
                this.updateKdWilayah();
            });

            this.$watch("selectedKdLevel", () => {
                if (this.selectedKdLevel !== "01") {
                    this.selectedKabkots = [];
                    this.selectAllKabkotsChecked = false;
                }
                this.updateFilteredKabkots();
                this.updateKdWilayah();
            });

            this.$watch("tableData", () => {
                this.hasUnconfirmedChanges = this.tableData.length > 0;
            });

            window.addEventListener("beforeunload", (event) => {
                if (this.hasUnconfirmedChanges) {
                    event.preventDefault();
                }
            });
        } catch (error) {
            // console.error("Initialization failed:", error);
            this.modalMessage = "Gagal memuat data. Silakan coba lagi.";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },

    // API Helper
    async handleApiResponse(response, context) {
        if (!response.ok)
            throw new Error(`${context} API error! status: ${response.status}`);
        return await response.json();
    },

    // Selection Methods
    selectAllProvinces(checked) {
        this.selectedProvinces = checked ? [...this.filteredProvinces] : [];
        this.selectAllProvincesChecked = checked;
        this.updateKdWilayah();
    },

    toggleProvince(provinsi) {
        const index = this.selectedProvinces.findIndex(
            (p) => p.kd_wilayah === provinsi.kd_wilayah
        );
        if (index === -1) {
            this.selectedProvinces.push(provinsi);
        } else {
            this.selectedProvinces.splice(index, 1);
        }
        this.updateSelectAllProvinces();
        this.updateKdWilayah();
    },

    selectAllKabkots(checked) {
        this.selectedKabkots = checked ? [...this.filteredKabkots] : [];
        this.selectAllKabkotsChecked = checked;
        this.updateKdWilayah();
    },

    toggleKabkot(kabkot) {
        const index = this.selectedKabkots.findIndex(
            (k) => k.kd_wilayah === kabkot.kd_wilayah
        );
        if (index === -1) {
            this.selectedKabkots.push(kabkot);
        } else {
            this.selectedKabkots.splice(index, 1);
        }
        this.updateSelectAllKabkots();
        this.updateKdWilayah();
    },

    selectAllKomoditas(checked) {
        this.selectedKomoditas = checked
            ? this.filteredKomoditas.map((k) => String(k.kd_komoditas)) // Convert to string
            : [];
        this.selectAllKomoditasChecked = checked;
        // console.log("Selected Komoditas:", this.selectedKomoditas);
        // console.log("Type of first kd_komoditas:", typeof this.selectedKomoditas[0]);
    },

    updateSelectAllProvinces() {
        this.selectAllProvincesChecked =
            this.selectedProvinces.length === this.filteredProvinces.length;
    },

    updateSelectAllKabkots() {
        this.selectAllKabkotsChecked =
            this.filteredKabkots.length > 0 &&
            this.selectedKabkots.length === this.filteredKabkots.length;
    },

    updateSelectAllKomoditas() {
        this.selectAllKomoditasChecked =
            this.selectedKomoditas.length === this.filteredKomoditas.length;
    },

    updateKdWilayah() {
        if (this.selectedKdLevel !== "01") {
            this.selectedKabkots = [];
            this.selectAllKabkotsChecked = false;
        }
        this.kd_wilayah =
            this.selectedKabkots.length > 0
                ? this.selectedKabkots[this.selectedKabkots.length - 1]
                      .kd_wilayah
                : this.selectedProvinces.length > 0
                ? this.selectedProvinces[this.selectedProvinces.length - 1]
                      .kd_wilayah
                : "";
    },

    // Search Methods
    searchProvince(query) {
        query = query.toLowerCase();
        this.filteredProvinces = this.provinces.filter((province) =>
            province.nama_wilayah.toLowerCase().includes(query)
        );
        this.updateSelectAllProvinces();
    },

    searchKabkot(query) {
        query = query.toLowerCase();
        const selectedProvinceKds = this.selectedProvinces.map(
            (p) => p.kd_wilayah
        );
        const baseKabkots =
            this.selectedKdLevel === "01" && selectedProvinceKds.length > 0
                ? this.kabkots.filter((kabkot) =>
                      selectedProvinceKds.includes(kabkot.parent_kd)
                  )
                : [];
        this.filteredKabkots = baseKabkots.filter((kabkot) =>
            kabkot.nama_wilayah.toLowerCase().includes(query)
        );
        this.updateSelectAllKabkots();
    },

    searchKomoditas(query) {
        query = query.toLowerCase();
        this.filteredKomoditas = this.komoditas.filter((komoditas) =>
            komoditas.nama_komoditas.toLowerCase().includes(query)
        );
        this.updateSelectAllKomoditas();
    },

    // Table Management
    updateFilteredKabkots() {
        if (
            this.selectedKdLevel !== "01" ||
            this.selectedProvinces.length === 0
        ) {
            this.filteredKabkots = [];
            this.selectedKabkots = [];
            this.selectAllKabkotsChecked = false;
            return;
        }
        const selectedProvinceKds = this.selectedProvinces.map(
            (p) => p.kd_wilayah
        );
        this.filteredKabkots = this.kabkots.filter((kabkot) =>
            selectedProvinceKds.includes(kabkot.parent_kd)
        );
        this.selectedKabkots = this.selectedKabkots.filter((kabkot) =>
            this.filteredKabkots.some((f) => f.kd_wilayah === kabkot.kd_wilayah)
        );
        this.updateSelectAllKabkots();
    },

    async addRow() {
        this.errorMessage = "";

        if (!this.bulan || !this.tahun || !this.selectedKdLevel) {
            this.errorMessage = "Pilih bulan, tahun, dan level harga.";
            return;
        }

        const selectedWilayah =
            this.selectedKdLevel === "01"
                ? [...this.selectedProvinces, ...this.selectedKabkots]
                : [...this.selectedProvinces];

        if (
            selectedWilayah.length === 0 ||
            this.selectedKomoditas.length === 0
        ) {
            this.errorMessage =
                "Pilih minimal satu provinsi/kabupaten dan komoditas.";
            return;
        }

        const komoditasToAdd = this.komoditas.filter((k) =>
            this.selectedKomoditas.includes(String(k.kd_komoditas))
        );

        if (komoditasToAdd.length === 0) {
            this.errorMessage = "Tidak ada komoditas yang dipilih.";
            return;
        }

        const levelHargaMap = {
            "01": "Harga Konsumen Kota",
            "02": "Harga Konsumen Desa",
            "03": "Harga Perdagangan Besar",
            "04": "Harga Produsen Desa",
            "05": "Harga Produsen",
        };

        const namaKdLevel =
            levelHargaMap[this.selectedKdLevel] || "Unknown Level Harga";

        const combinations = [];
        selectedWilayah.forEach((wilayah) => {
            komoditasToAdd.forEach((komoditas) => {
                combinations.push({
                    bulan: this.bulan,
                    tahun: this.tahun,
                    kd_wilayah: wilayah.kd_wilayah,
                    kd_komoditas: String(komoditas.kd_komoditas),
                    kd_level: this.selectedKdLevel,
                    nama_komoditas: komoditas.nama_komoditas,
                });
            });
        });

        if (combinations.length > 100) {
            this.$dispatch("open-modal", "limit-error");
            return;
        }

        try {
            const result = await this.fetchWrapper(
                "/api/inflasi-id",
                {
                    method: "POST",
                    body: JSON.stringify(combinations),
                },
                "Data berhasil divalidasi",
                false
            );

            const { message, data } = result;

            const itemsWithInflasiId = [];
            const missingItems = [];

            data.forEach((result) => {
                const combo = combinations.find(
                    (c) =>
                        c.kd_wilayah === result.kd_wilayah &&
                        c.kd_komoditas === result.kd_komoditas
                );

                if (result.inflasi_id) {
                    itemsWithInflasiId.push({
                        bulan: combo.bulan,
                        tahun: combo.tahun,
                        kd_wilayah: result.kd_wilayah,
                        kd_komoditas: result.kd_komoditas,
                        kd_level: combo.kd_level,
                        nama_kd_level: namaKdLevel, // Add for display purposes
                        nama_komoditas: result.nama_komoditas,
                        nama_wilayah: result.nama_wilayah,
                        inflasi_id: result.inflasi_id,
                        nilai_inflasi: result.nilai_inflasi || "0.00",
                        andil: result.andil || "-",
                        bulan_tahun_id: result.bulan_tahun_id,
                    });
                } else {
                    missingItems.push(
                        `${
                            result.nama_wilayah ||
                            this.getWilayahName(result.kd_wilayah)
                        } - ${
                            combo?.nama_komoditas || "Unknown"
                        } tidak ditemukan`
                    );
                }
            });

            this.modalContent = {
                success: missingItems.length === 0,
                items: itemsWithInflasiId,
                missingItems: missingItems,
            };
            this.modalMessage = message; // Display API message
            this.$dispatch("open-modal", "confirm-add");
        } catch (error) {
            this.modalContent = {
                success: false,
                items: [],
                missingItems: combinations.map(
                    (c) =>
                        `${this.getWilayahName(c.kd_wilayah)} - ${
                            c.nama_komoditas
                        } tidak ditemukan`
                ),
            };
        }
    },

    confirmAddToTable() {
        const newItems = this.modalContent.items.filter(
            (item) =>
                !this.tableData.some(
                    (existing) => existing.inflasi_id === item.inflasi_id
                )
        );
        this.tableData = [...this.tableData, ...newItems];
        this.$dispatch("close");
    },

    removeRow(index) {
        this.tableData.splice(index, 1);
    },

    async confirmRekonsiliasi() {
        const uniqueData = Array.from(
            new Map(
                this.tableData.map((item) => [item.inflasi_id, item])
            ).values()
        );
        const formData = new FormData();
        uniqueData.forEach((item) => {
            formData.append("inflasi_ids[]", item.inflasi_id);
            formData.append("bulan_tahun_ids[]", item.bulan_tahun_id);
        });

        // Log FormData contents
        // console.log("POST request to /rekonsiliasi/confirm - FormData contents:");
        // for (const [key, value] of formData.entries()) {
        //     console.log(`${key}: ${value}`);
        // }
        // console.log("Unique data items:", uniqueData);

        try {
            const result = await this.fetchWrapper(
                "/rekonsiliasi/confirm",
                {
                    method: "POST",
                    body: formData,
                },
                "Komoditas rekonsiliasi berhasil dikonfirmasi",
                true // Show success modal
            );

            this.tableData = [];
        } catch (error) {
            console.error("Confirm error:", error);
        }
    },

    // Utility
    getWilayahName(kd_wilayah) {
        const wilayah = [...this.provinces, ...this.kabkots].find(
            (w) => w.kd_wilayah === kd_wilayah
        );
        return wilayah ? wilayah.nama_wilayah : "Unknown";
    },

    formatInflasi(value) {
        if (value === "-" || value === null) return "-";
        const num = parseFloat(value);
        if (isNaN(num)) return "-";
        return num.toFixed(2);
    },
}));

Alpine.start();
