// document.addEventListener('alpine:init', () => {

Alpine.data("webData", () => ({
    loading: false,
    bulan: "",
    tahun: "",
    activeBulan: "", // Store the active bulan
    activeTahun: "", // Store the active tahun
    tahunOptions: [],

    provinces: [],
    kabkots: [],
    komoditas: [],
    selectedProvince: "",
    selectedKabkot: "",
    selectedKomoditas: "",
    selectedKdLevel: "",

    kd_wilayah: "",

    modalData: { id: "", komoditas: "" },

    get isActivePeriod() {
        return (
            this.bulan === this.activeBulan && this.tahun === this.activeTahun
        );
    },

    async init() {
        this.loading = false; // Ensure loading is true at start
        try {
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];

            const komoditasResponse = await fetch("/api/komoditas");
            const komoditasData = await komoditasResponse.json();
            this.komoditas = komoditasData || [];

            // Fetch Bulan and Tahun
            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();

            const aktifData = bulanTahunData.bt_aktif; // First active record
            this.bulan = aktifData
                ? String(aktifData.bulan).padStart(2, "0")
                : "";
            this.tahun = aktifData ? aktifData.tahun : "";

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            // Populate tahunOptions, fallback if tahun is missing
            this.tahunOptions =
                bulanTahunData.tahun || (aktifData ? [aktifData.tahun] : []);
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false; // Turn off loading after initialization
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    //Bulan Tahun methods

    async updateBulanTahun() {
        if (this.isActivePeriod) {
            this.failMessage = "Bulan dan tahun terpilih sudah aktif";
            this.failDetails = null;
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
            return;
        }

        const requestConfig = {
            method: "POST",
            url: "/update-bulan-tahun",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify({ bulan: this.bulan, tahun: this.tahun }),
        };

        try {
            const response = await fetch(requestConfig.url, {
                method: requestConfig.method,
                headers: requestConfig.headers,
                body: requestConfig.body,
            });

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message;
                this.failDetails = data.details || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;
            this.successMessage = data.message;
            this.$dispatch("open-modal", "success-update-bulan-tahun");
        } catch (error) {
            this.failMessage = "An unexpected error occurred";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    alasanList: [
        "Kondisi Alam",
        "Masa Panen",
        "Gagal Panen",
        "Promo dan Diskon",
        "Harga Stok Melimpah",
        "Stok Menipis/Langka",
        "Harga Kembali Normal",
        "Turun Harga dari Distributor",
        "Kenaikan Harga dari Distributor",
        "Perbedaan Kualitas",
        "Supplier Menaikkan Harga",
        "Supplier Menurunkan Harga",
        "Persaingan Harga",
        "Permintaan Meningkat",
        "Permintaan Menurun",
        "Operasi Pasar",
        "Kebijakan Pemerintah Pusat",
        "Kebijakan Pemerintah Daerah",
        "Kesalahan Petugas Mencacah",
        "Penurunan Produksi",
        "Kenaikan Produksi",
        "Salah Entri Data",
        "Penggantian Responden",
        "Lainnya",
    ],
    selectedAlasan: [],

    // Dropdown handlers
    toggleDropdown(menu) {
        this.dropdowns[menu] = !this.dropdowns[menu];
    },
    closeDropdown(menu) {
        this.dropdowns[menu] = false;
    },

    modalOpen: false,

    openModal() {
        this.modalOpen = true;
    },

    closeModal() {
        this.modalOpen = false;
        this.item = {
            id: null,
            komoditas: "",
            harga: "",
            wilayah: "",
            levelHarga: "",
            periode: "",
        };
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
}));
// });
