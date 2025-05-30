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
                    fetch("/segmented-wilayah").then((res) =>
                        this.handleApiResponse(res, "Wilayah")
                    ),
                    fetch("/all-komoditas").then((res) =>
                        this.handleApiResponse(res, "Komoditas")
                    ),
                    fetch("/bulan-tahun").then((res) =>
                        this.handleApiResponse(res, "BulanTahun")
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
            ? this.filteredKomoditas.map((k) => k.kd_komoditas)
            : [];
        this.selectAllKomoditasChecked = checked;
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
            this.selectedKomoditas.includes(k.kd_komoditas)
        );
        const levelHargaMapping = {
            "01": "Harga Konsumen Kota",
            "02": "Harga Konsumen Desa",
            "03": "Harga Perdagangan Besar",
            "04": "Harga Produsen Desa",
            "05": "Harga Produsen",
        };
        const levelHargaDisplay = levelHargaMapping[this.selectedKdLevel];
        const combinations = [];
        selectedWilayah.forEach((wilayah) => {
            komoditasToAdd.forEach((komoditas) => {
                combinations.push({
                    kd_wilayah: wilayah.kd_wilayah,
                    level_harga: levelHargaDisplay,
                    kd_komoditas: komoditas.kd_komoditas,
                    nama_komoditas: komoditas.nama_komoditas,
                    bulan: this.bulan,
                    tahun: this.tahun,
                });
            });
        });

        if (combinations.length > 100) {
            this.$dispatch("open-modal", "limit-error");
            return;
        }

        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");
            const response = await fetch("/api/inflasi-id", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": csrfToken,
                },
                body: JSON.stringify(
                    combinations.map((combo) => ({
                        bulan: combo.bulan,
                        tahun: combo.tahun,
                        kd_level: this.selectedKdLevel,
                        kd_wilayah: combo.kd_wilayah,
                        kd_komoditas: combo.kd_komoditas,
                        level_harga: combo.level_harga,
                        nama_komoditas: combo.nama_komoditas,
                    }))
                ),
            });

            if (!response.ok)
                throw new Error(`HTTP error! status: ${response.status}`);
            const results = await response.json();

            const itemsWithInflasiId = [];
            const missingItems = [];
            results.forEach((result) => {
                const combo = combinations.find(
                    (c) =>
                        c.kd_wilayah === result.kd_wilayah &&
                        c.kd_komoditas === result.kd_komoditas
                );
                if (result.inflasi_id) {
                    itemsWithInflasiId.push({
                        ...combo,
                        inflasi_id: result.inflasi_id,
                        bulan_tahun_id: result.bulan_tahun_id,
                        inflasi: result.inflasi || "0.00",
                        nama_wilayah: this.getWilayahName(result.kd_wilayah),
                    });
                } else {
                    missingItems.push(
                        `${result.nama_wilayah} - ${result.nama_komoditas} tidak ditemukan`
                    );
                }
            });

            this.modalContent = {
                success: missingItems.length === 0,
                items: itemsWithInflasiId,
                missingItems: missingItems,
            };
            this.$dispatch("open-modal", "confirm-add");
        } catch (error) {
            // console.error("Error fetching inflasi_ids:", error);
            this.modalMessage = "Gagal memvalidasi data. Silakan coba lagi.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    confirmAddToTable() {
        this.tableData = [...this.tableData, ...this.modalContent.items];
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
            formData.append(
                "bulan_tahun_ids[]",
                parseInt(item.bulan_tahun_id, 10)
            );
        });

        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");
            const response = await fetch("/rekonsiliasi/confirm", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: formData,
            });

            const result = await response.json();
            if (result.success) {
                this.modalMessage = result.message;
                this.$dispatch("open-modal", "success-modal");
                this.tableData = [];
            } else {
                this.modalMessage =
                    result.message || "Gagal mengkonfirmasi rekonsiliasi.";
                this.$dispatch("open-modal", "error-modal");
            }
        } catch (error) {
            // console.error("Rekonsiliasi error:", error);
            this.modalMessage =
                "Terjadi kesalahan saat mengkonfirmasi rekonsiliasi.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    // Utility
    getWilayahName(kd_wilayah) {
        const wilayah = [...this.provinces, ...this.kabkots].find(
            (w) => w.kd_wilayah === kd_wilayah
        );
        return wilayah ? wilayah.nama_wilayah : "Unknown";
    },
}));

Alpine.start();
