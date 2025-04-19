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

    komoditasData: [],
    wilayahData: [],
    newKomoditas: { kd_komoditas: "", nama_komoditas: "" },
    editKomoditas: { kd_komoditas: "", nama_komoditas: "" },
    newWilayah: { kd_wilayah: "", nama_wilayah: "" },
    editWilayah: { kd_wilayah: "", nama_wilayah: "" },

    successMessage: "", // New: Store success message
    failMessage: "", // New: Store fail message
    failDetails: null, // New: Store fail details

    confirmMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async init() {
        this.loading = true; // Ensure loading is true at start
        try {
            // Fetch Wilayah
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.wilayahData = (wilayahData.provinces || []).concat(
                wilayahData.kabkots || []
            );
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false; // Turn off loading after initialization
        }
    },
}));
Alpine.start();
