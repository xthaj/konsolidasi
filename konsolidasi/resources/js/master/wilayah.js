import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    wilayahData: [],

    async init() {
        this.loading = true;
        try {
            const response = await fetch("/all-wilayah");
            const result = await response.json();
            this.wilayahData = result.data || [];
        } catch (error) {
            console.error("Failed to load wilayah data:", error);
            this.modalMessage = "Gagal memuat data wilayah";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();
