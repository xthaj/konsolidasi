import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    alasanData: [],
    newAlasan: { nama: "" },
    modalMessage: "",
    confirmMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async fetchAlasan() {
        const alasanResponse = await fetch("/all-alasan");
        const result = await alasanResponse.json();
        this.alasanData = result.data || [];
    },

    async init() {
        this.loading = true;
        try {
            await this.fetchAlasan();
        } catch (error) {
            console.error("Failed to load data:", error);
            this.modalMessage = "Gagal memuat data alasan.";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },

    openAddAlasanModal() {
        this.newAlasan = { nama: "" };
        this.$dispatch("open-modal", "add-alasan");
    },

    async addAlasan() {
        try {
            const response = await fetch("/alasan", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify(this.newAlasan),
            });

            const result = await response.json();

            if (!response.ok) {
                this.modalMessage = result.message || "Gagal menambah alasan.";
                this.$dispatch("open-modal", "error-modal");
                return;
            } else {
                this.modalMessage =
                    result.message || "Alasan berhasil ditambahkan!";
                this.$dispatch("open-modal", "success-modal");
                this.$dispatch("close");
                await this.fetchAlasan();
            }
        } catch (error) {
            this.modalMessage = "Terjadi kesalahan saat menambah alasan.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    deleteAlasan(alasan_id) {
        const alasan = this.alasanData.find((a) => a.alasan_id === alasan_id);
        if (!alasan) return;

        this.confirmMessage = `Apakah Anda yakin ingin menghapus alasan "${alasan.keterangan}"?`;
        this.confirmDetails = "Data yang dihapus tidak dapat dikembalikan.";
        this.confirmAction = async () => {
            try {
                const response = await fetch(`/alasan/${alasan_id}`, {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                });

                const result = await response.json();

                if (!response.ok) {
                    this.modalMessage =
                        result.message || "Gagal menghapus alasan.";
                    this.$dispatch("open-modal", "error-modal");
                    return;
                } else {
                    this.modalMessage =
                        result.message || "Alasan berhasil dihapus!";
                    this.$dispatch("open-modal", "success-modal");
                    await this.fetchAlasan();
                }
            } catch (error) {
                this.modalMessage = "Terjadi kesalahan saat menghapus alasan.";
                this.$dispatch("open-modal", "error-modal");
            }
        };

        this.$dispatch("open-modal", "confirm-action");
    },

    executeConfirmAction() {
        if (this.confirmAction) {
            this.confirmAction();
            this.confirmAction = null;
            this.confirmMessage = "";
            this.confirmDetails = null;
        }
        this.$dispatch("close");
    },
}));

Alpine.start();
