import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    komoditasData: [],

    newKomoditas: { nama_komoditas: "" },
    editKomoditas: { kd_komoditas: "", nama_komoditas: "" },

    modalMessage: "",
    confirmMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async fetchKomoditas() {
        const komoditasResponse = await fetch("/all-komoditas");
        const result = await komoditasResponse.json();
        this.komoditasData = result.data || [];
    },

    async init() {
        this.loading = true;
        try {
            await this.fetchKomoditas();
        } catch (error) {
            console.error("Failed to load data:", error);
            this.modalMessage = "Failed to load komoditas data.";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },

    openAddKomoditasModal() {
        this.newKomoditas = { nama_komoditas: "" };
        this.$dispatch("open-modal", "add-komoditas");
    },

    async addKomoditas() {
        if (!this.newKomoditas.nama_komoditas.trim()) {
            this.modalMessage = "Nama komoditas tidak boleh kosong.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }
        
        try {
            const response = await fetch("/komoditas", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify(this.newKomoditas),
            });

            const result = await response.json();
            if (!response.ok) {
                this.modalMessage =
                    result.message || "Gagal menambahkan komoditas.";
                this.$dispatch("open-modal", "error-modal");
                return;
            } else {
                this.modalMessage =
                    result.message || "Komoditas berhasil ditambahkan!";
                this.$dispatch("open-modal", "success-modal");
                this.$dispatch("close","add-komoditas");
    
                await this.fetchKomoditas(); // Re-fetch data
            }
        } catch (error) {
            this.modalMessage = "Terjadi kesalahan saat menambah komoditas.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    async updateKomoditas() {
        if (!this.editKomoditas.nama_komoditas.trim()) {
            this.modalMessage = "Nama komoditas tidak boleh kosong.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        try {
            const response = await fetch(
                `/komoditas/${this.editKomoditas.kd_komoditas}`,
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
                        nama_komoditas: this.editKomoditas.nama_komoditas,
                    }),
                }
            );

            const result = await response.json();

            if (!response.ok) {
                this.modalMessage =
                    result.message || "Gagal memperbarui komoditas.";
                this.$dispatch("open-modal", "error-modal");
                return;
            } else {
                this.modalMessage =
                    result.message || "Komoditas berhasil diperbarui!";
    
                await this.fetchKomoditas();
                this.$dispatch("close","edit-komoditas");
                this.$dispatch("open-modal", "success-modal");
            }
        } catch (error) {
            this.modalMessage = "Terjadi kesalahan saat memperbarui komoditas.";
            this.$dispatch("open-modal", "error-modal");
        }
    },

    openEditKomoditasModal(komoditas) {
        this.editKomoditas = { ...komoditas };
        this.$dispatch("open-modal", "edit-komoditas");
    },

    deleteKomoditas(kd_komoditas) {
        const komoditas = this.komoditasData.find(
            (k) => k.kd_komoditas === kd_komoditas
        );
        if (!komoditas) return;

        this.confirmMessage = `Apakah Anda yakin ingin menghapus komoditas "${komoditas.nama_komoditas}"?`;
        this.confirmDetails =
            "Hanya bisa dilakukan apabila tidak ada inflasi terkait.";
        this.confirmAction = async () => {
            try {
                const response = await fetch(`/komoditas/${kd_komoditas}`, {
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
                        result.message || "Gagal menghapus komoditas.";
                    this.$dispatch("open-modal", "error-modal");
                    return;
                } else {
                    this.modalMessage =
                        result.message || "Komoditas berhasil dihapus!";
                    this.$dispatch("open-modal", "success-modal");
                    await this.fetchKomoditas(); // Re-fetch data
                }
            } catch (error) {
                this.modalMessage =
                    "Terjadi kesalahan saat menghapus komoditas.";
                this.$dispatch("open-modal", "error-modal");
            }
        };

        this.$dispatch("open-modal", "confirm-action");
    },
}));
Alpine.start();
