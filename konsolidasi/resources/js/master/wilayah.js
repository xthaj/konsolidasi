import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    wilayahData: [],
    newWilayah: { nama_wilayah: "" },
    editWilayah: { kd_wilayah: "", nama_wilayah: "" },
    modalMessage: "",
    confirmMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async init() {
        this.loading = true;
        try {
            const response = await fetch("/api/wilayah");
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

    openAddWilayahModal() {
        this.newWilayah = { nama_wilayah: "" };
        this.$dispatch("open-modal", "add-wilayah");
    },

    openEditWilayahModal(wilayah) {
        this.editWilayah = { ...wilayah };
        this.$dispatch("open-modal", "edit-wilayah");
    },

    async addWilayah() {
        if (!this.newWilayah.nama_wilayah) {
            this.modalMessage = "Nama wilayah harus diisi";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        try {
            const response = await fetch("/api/wilayah", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(this.newWilayah),
            });

            const result = await response.json();
            if (response.ok) {
                this.wilayahData.push(result.data);
                this.modalMessage = result.message;
                this.$dispatch("open-modal", "success-modal");
                this.$dispatch("close");
            } else {
                this.modalMessage =
                    result.message || "Gagal menambahkan wilayah";
                this.$dispatch("open-modal", "error-modal");
            }
        } catch (error) {
            console.error("Failed to add wilayah:", error);
            this.modalMessage = "Gagal menambahkan wilayah";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    async updateWilayah() {
        if (!this.editWilayah.nama_wilayah) {
            this.modalMessage = "Nama wilayah harus diisi";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        try {
            const response = await fetch(
                `/api/wilayah/${this.editWilayah.kd_wilayah}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                    },
                    body: JSON.stringify({
                        nama_wilayah: this.editWilayah.nama_wilayah,
                    }),
                }
            );

            const result = await response.json();
            if (response.ok) {
                const index = this.wilayahData.findIndex(
                    (w) => w.kd_wilayah === this.editWilayah.kd_wilayah
                );
                if (index !== -1) {
                    this.wilayahData[index] = result.data;
                }
                this.modalMessage = result.message;
                this.$dispatch("open-modal", "success-modal");
                this.$dispatch("close");
            } else {
                this.modalMessage =
                    result.message || "Gagal memperbarui wilayah";
                this.$dispatch("open-modal", "error-modal");
            }
        } catch (error) {
            console.error("Failed to update wilayah:", error);
            this.modalMessage = "Gagal memperbarui wilayah";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    deleteWilayah(kd_wilayah, nama_wilayah) {
        this.confirmMessage = "Apakah Anda yakin ingin menghapus wilayah ini?";
        this.confirmDetails = `Kode: ${kd_wilayah}, Nama: ${nama_wilayah}`;
        this.confirmAction = async () => {
            try {
                const response = await fetch(
                    `/api/master-wilayah/${kd_wilayah}`,
                    {
                        method: "DELETE",
                        headers: {
                            Accept: "application/json",
                        },
                    }
                );

                const result = await response.json();
                if (response.ok) {
                    this.wilayahData = this.wilayahData.filter(
                        (w) => w.kd_wilayah !== kd_wilayah
                    );
                    this.modalMessage = result.message;
                    this.$dispatch("open-modal", "success-modal");
                } else {
                    this.modalMessage =
                        result.message || "Gagal menghapus wilayah";
                    this.$dispatch("open-modal", "error-modal");
                }
            } catch (error) {
                console.error("Failed to delete wilayah:", error);
                this.modalMessage = "Gagal menghapus wilayah";
                this.$dispatch("open-modal", "error-modal");
            }
        };
        this.$dispatch("open-modal", "confirm-action");
    },
}));

Alpine.start();
