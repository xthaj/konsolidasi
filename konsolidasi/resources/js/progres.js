// document.addEventListener('alpine:init', () => {
Alpine.data("webData", () => ({
    loading: true, // Start with loading true
    bulan: "",
    tahun: "",
    activeBulan: "",
    activeTahun: "",
    tahunOptions: [],
    provinces: [],
    kabkots: [],
    selectedProvince: "",
    selectedKabkot: "",
    selectedKdLevel: "",
    kd_wilayah: "",
    status: "all",

    get isActivePeriod() {
        return (
            this.bulan === this.activeBulan && this.tahun === this.activeTahun
        );
    },

    async init() {
        this.loading = true;
        try {
            // Fetch Wilayah data
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];

            // Fetch active BulanTahun
            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();
            const aktifData = bulanTahunData.bt_aktif || {};
            this.bulan = aktifData.bulan
                ? String(aktifData.bulan).padStart(2, "0")
                : "01";
            this.tahun = aktifData.tahun || String(new Date().getFullYear());
            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            // Populate tahunOptions
            const currentYear = new Date().getFullYear();
            this.tahunOptions = Array.from(
                { length: 11 },
                (_, i) => currentYear - 5 + i
            );

            // Set initial values from URL params if present
            const urlParams = new URLSearchParams(window.location.search);
            this.bulan = urlParams.get("bulan") || this.bulan;
            this.tahun = urlParams.get("tahun") || this.tahun;
            this.selectedKdLevel = urlParams.get("kd_level") || "01";
            this.selectedKabkot = urlParams.get("kd_wilayah") || "";
            this.status = urlParams.get("status") || "all";
            if (this.selectedKabkot && this.selectedKabkot.length > 2) {
                this.selectedProvince =
                    this.selectedKabkot.substring(0, 2) + "00";
            }
            this.updateKdWilayah();
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return this.kabkots;
        return this.kabkots.filter((k) =>
            k.kd_wilayah.startsWith(this.selectedProvince.substring(0, 2))
        );
    },

    updateKdWilayah() {
        this.kd_wilayah = this.selectedKabkot || this.selectedProvince || "";
    },

    submitForm() {
        this.$refs.filterForm.submit(); // Manually trigger form submission if needed
    },
}));
// });
