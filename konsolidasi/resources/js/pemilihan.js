import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    bulan: "",
    tahun: "",
    activeBulan: "",
    activeTahun: "",
    tahunOptions: [],
    errorMessage: "",
    provinces: [],
    kabkots: [],
    komoditas: [],
    selectedProvinces: [],
    selectedKabkots: [],
    selectedKomoditas: [],
    dropdowns: { komoditas: false },
    kd_wilayah: "",
    selectedKdLevel: "",
    selectAllProvincesChecked: false,
    selectAllKabkotsChecked: false,
    selectAllKomoditasChecked: false,
    tableData: [],
    filteredProvinces: [],
    filteredKabkots: [],
    filteredKomoditas: [],
    modalContent: {
        success: false,
        items: [],
        missingItems: [],
    },
    get isActivePeriod() {
        const result =
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun;
        return result;
    },
    hasUnconfirmedChanges: false,
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
            this.filteredProvinces = [...this.provinces];
            this.updateFilteredKabkots(); // Initialize filteredKabkots

            // Process komoditas data
            this.komoditas = komoditasResponse.data || [];
            this.filteredKomoditas = [...this.komoditas];

            // Process bulan and tahun data
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

            // Watch selectedProvinces to update filteredKabkots
            this.$watch("selectedProvinces", () => {
                this.updateFilteredKabkots();
                this.updateSelectAllKabkots();
                this.updateKdWilayah();
            });

            // Watch selectedKdLevel to clear kabkots when not '01'
            this.$watch("selectedKdLevel", () => {
                if (this.selectedKdLevel !== "01") {
                    this.selectedKabkots = [];
                    this.selectAllKabkotsChecked = false;
                }
                this.updateFilteredKabkots();
                this.updateKdWilayah();
            });

            // Watch tableData for changes
            this.$watch("tableData", () => {
                this.hasUnconfirmedChanges = this.tableData.length > 0;
            });

            window.addEventListener("beforeunload", (event) => {
                if (this.hasUnconfirmedChanges) {
                    event.preventDefault();
                }
            });

            console.log("Initialized:", this.tahunOptions);
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

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

        // Remove selected kabkots that are no longer in filteredKabkots
        this.selectedKabkots = this.selectedKabkots.filter((kabkot) =>
            this.filteredKabkots.some((f) => f.kd_wilayah === kabkot.kd_wilayah)
        );
        this.updateSelectAllKabkots();
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

    selectAllKabkots(checked) {
        if (checked) {
            this.selectedKabkots = [...this.filteredKabkots];
        } else {
            this.selectedKabkots = [];
        }
        this.selectAllKabkotsChecked = checked;
        this.updateKdWilayah();
    },

    updateSelectAllKabkots() {
        this.selectAllKabkotsChecked =
            this.filteredKabkots.length > 0 &&
            this.selectedKabkots.length === this.filteredKabkots.length;
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

    // Other methods remain unchanged
    checkRowValidity() {
        if (this.selectedKomoditas.length === 0) {
            return false;
        }
        if (this.selectedKdLevel === "all") {
            this.isPusat = true;
            this.updateKdWilayah();
            return true;
        }
        if (this.isPusat) {
            return true;
        }
        if (this.selectedKabkots.length > 0 && this.selectedKdLevel === "01") {
            return true;
        }
        return this.selectedProvinces.length > 0;
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
            selectedWilayah.length === 0 &&
            this.selectedKomoditas.length === 0
        ) {
            this.errorMessage =
                "Pilih minimal satu provinsi/kabupaten dan komoditas.";
            return;
        } else if (selectedWilayah.length === 0) {
            this.errorMessage = "Pilih minimal satu provinsi/kabupaten.";
            return;
        } else if (this.selectedKomoditas.length === 0) {
            this.errorMessage = "Pilih minimal satu komoditas.";
            return;
        }
        const komoditasToAdd =
            this.selectedKomoditas.length > 0
                ? this.komoditas.filter((k) =>
                      this.selectedKomoditas.includes(k.kd_komoditas)
                  )
                : this.komoditas;
        if (komoditasToAdd.length === 0) {
            console.error("Please select at least one komoditas");
            return;
        }
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
                    nama_wilayah: wilayah.nama_wilayah,
                    level_harga: levelHargaDisplay,
                    kd_komoditas: komoditas.kd_komoditas,
                    nama_komoditas: komoditas.nama_komoditas,
                    bulan: this.bulan,
                    tahun: this.tahun,
                });
            });
        });
        const MAX_COMBINATIONS = 100;
        if (combinations.length > MAX_COMBINATIONS) {
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
                        nama_wilayah: combo.nama_wilayah,
                        level_harga: combo.level_harga,
                        nama_komoditas: combo.nama_komoditas,
                    }))
                ),
            });
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const results = await response.json();
            console.log("API Results:", results);
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
            console.log("Modal Content:", this.modalContent);
            this.$dispatch("open-modal", "confirm-add");
        } catch (error) {
            console.error("Error fetching inflasi_ids:", error);
            this.modalContent = {
                success: false,
                items: [],
                missingItems: ["Error fetching inflasi_ids. Please try again."],
            };
            this.$dispatch("open-modal", "confirm-add");
        }
    },

    confirmAddToTable() {
        this.tableData = [...this.tableData, ...this.modalContent.items];
        this.$dispatch("close");
        // console.log("Table Data Updated:", this.tableData);
    },

    async confirmRekonsiliasi() {
        console.log("tableData before submission:", this.tableData);
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
        console.log("FormData to send (unique values only):");
        for (let pair of formData.entries()) {
            console.log(`${pair[0]}: ${pair[1]}`);
        }
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
            console.log("Server response:", result);
            if (result.success) {
                if (result.partial_success) {
                    alert(result.message);
                    console.log("Duplikat:", result.duplicates);
                    this.tableData = [];
                } else {
                    alert("Pemilihan komoditas rekonsiliasi berhasil!");
                    this.tableData = [];
                }
            } else {
                alert(
                    "Rekonsiliasi gagal: " + (result.message || "Unknown error")
                );
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Error: " + error.message);
        }
    },

    removeRow(index) {
        this.tableData.splice(index, 1);
    },

    updateKdWilayah() {
        if (this.selectedKdLevel !== "01") {
            this.selectedKabkots = [];
            this.selectAllKabkotsChecked = false;
        }
        if (this.selectedKabkots.length > 0) {
            this.kd_wilayah =
                this.selectedKabkots[
                    this.selectedKabkots.length - 1
                ].kd_wilayah;
        } else if (this.selectedProvinces.length > 0) {
            this.kd_wilayah =
                this.selectedProvinces[
                    this.selectedProvinces.length - 1
                ].kd_wilayah;
        } else {
            this.kd_wilayah = "";
        }
    },

    selectAllProvinces(checked) {
        if (checked) {
            this.selectedProvinces = [...this.filteredProvinces];
        } else {
            this.selectedProvinces = [];
        }
        this.selectAllProvincesChecked = checked;
        this.updateKdWilayah();
    },

    updateSelectAllProvinces() {
        this.selectAllProvincesChecked =
            this.selectedProvinces.length === this.filteredProvinces.length;
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

    selectAllKomoditas(checked) {
        if (checked) {
            this.selectedKomoditas = this.filteredKomoditas.map(
                (k) => k.kd_komoditas
            );
        } else {
            this.selectedKomoditas = [];
        }
        this.selectAllKomoditasChecked = checked;
    },

    updateSelectAllKomoditas() {
        this.selectAllKomoditasChecked =
            this.selectedKomoditas.length === this.filteredKomoditas.length;
    },

    searchProvince(query) {
        query = query.toLowerCase();
        this.filteredProvinces = this.provinces.filter((province) =>
            province.nama_wilayah.toLowerCase().includes(query)
        );
        this.updateSelectAllProvinces();
    },

    searchKomoditas(query) {
        query = query.toLowerCase();
        this.filteredKomoditas = this.komoditas.filter((komoditas) =>
            komoditas.nama_komoditas.toLowerCase().includes(query)
        );
        this.updateSelectAllKomoditas();
    },

    toggleDropdown(menu) {
        this.dropdowns[menu] = !this.dropdowns[menu];
    },

    closeDropdown(menu) {
        this.dropdowns[menu] = false;
    },
}));

Alpine.start();
