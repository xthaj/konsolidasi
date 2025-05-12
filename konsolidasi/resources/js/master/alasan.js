import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    alasanData: [],
    successMessage: "",
    failMessage: "",

    async init() {
        this.loading = true; // Ensure loading is true at start
        try {
            // Fetch alasan
            const alasanResponse = await fetch("/api/alasan");
            const alasanData = await alasanResponse.json();
            this.alasanData = alasanData.data || [];
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();
