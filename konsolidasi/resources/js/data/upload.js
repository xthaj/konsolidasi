import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,

    bulan: "",
    tahun: "",
    activeBulan: "", // Store the active bulan
    activeTahun: "", // Store the active tahun
    tahunOptions: [],

    confirmMessage: "",
    confirmDetails: "",
    formEvent: null,

    get isActivePeriod() {
        return (
            this.bulan === this.activeBulan && this.tahun === this.activeTahun
        );
    },

    async init() {
        this.loading = true;
        try {
            // Fetch Bulan and Tahun
            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();

            const aktifData = bulanTahunData.data.bt_aktif;
            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            this.tahunOptions =
                bulanTahunData.data.tahun ||
                (aktifData ? [aktifData.tahun] : []);
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false; // Turn off loading after initialization
        }
    },

    toggleDropdown(menu) {
        this.dropdowns[menu] = !this.dropdowns[menu];
    },

    closeDropdown(menu) {
        this.dropdowns[menu] = false;
    },
}));

Alpine.start();
