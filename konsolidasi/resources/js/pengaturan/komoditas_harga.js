import "flowbite";
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    komoditasData: [],
    modalMessage: "",

    async fetchWrapper(url, options = {}) {
        const response = await fetch(url, {
            ...options,
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content,
                ...options.headers,
            },
        });
        const result = await response.json();
        if (!response.ok) {
            throw new Error(result.message || "Terjadi kesalahan");
        }
        return result;
    },

    async init() {
        this.loading = true;
        try {
            await this.loadKomoditas();
        } catch (e) {
            console.error("Failed to load data:", e);
        } finally {
            this.loading = false;
        }
    },

    async loadKomoditas() {
        try {
            const res = await this.fetchWrapper("/all-komoditas-with-flag");
            this.komoditasData = (res.data || []).map((k) => ({
                ...k,
                is_harga: k.is_harga || false,
            }));
        } catch (e) {
            console.error("Failed to load komoditas:", e);
        }
    },

    async toggleHarga(kd_komoditas, checked) {
        try {
            const res = await this.fetchWrapper(`/komoditas/${kd_komoditas}/harga`, {
                method: "PATCH",
                body: JSON.stringify({ is_harga: checked }),
            });
            this.modalMessage = res.message || "Status berhasil diubah.";
            this.$dispatch("open-modal", "success-modal");
            await this.loadKomoditas();
        } catch (e) {
            this.modalMessage = e.message || "Gagal mengubah status.";
            this.$dispatch("open-modal", "error-modal");
        }
    },
}));

Alpine.start();
