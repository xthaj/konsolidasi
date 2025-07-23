import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    wilayahData: [],
    editWilayah: { kd_wilayah: "", nama_wilayah: "" },

    modalMessage: "",
    confirmMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async fetchWilayah() {
        const wilayahResponse = await fetch("/all-wilayah");
        const result = await wilayahResponse.json();
        this.wilayahData = result.data || [];
    },

    async init() {
        this.loading = true;
        try {
            await this.fetchWilayah();
        } catch (error) {
            console.error("Failed to load wilayah data:", error);
            this.modalMessage = "Gagal memuat data wilayah";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },

    async updateWilayah() {
        if (!this.editWilayah.nama_wilayah.trim()) {
            this.modalMessage = "Nama wilayah tidak boleh kosong.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        try {
            const response = await fetch(
                `/wilayah/${this.editWilayah.kd_wilayah}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                    body: JSON.stringify({
                        nama_wilayah: this.editWilayah.nama_wilayah,
                    }),
                }
            );

            const result = await response.json();

            if (!response.ok) {
                this.modalMessage =
                    result.message || "Gagal memperbarui wilayah.";
                this.$dispatch("open-modal", "error-modal");
                return;
            } else {
                this.modalMessage =
                    result.message || "Wilayah berhasil diperbarui!";

                await this.fetchWilayah();
                this.$dispatch("close", "edit-wilayah");
                this.$dispatch("open-modal", "success-modal");
            }
        } catch (error) {
            this.modalMessage = "Terjadi kesalahan saat memperbarui wilayah.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    openEditWilayahModal(wilayah) {
        this.editWilayah = { ...wilayah };
        this.$dispatch("open-modal", "edit-wilayah");
    },
}));

Alpine.start();
