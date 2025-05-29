import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,
    bulan: "",
    tahun: "",
    activeBulan: "",
    activeTahun: "",
    tahunOptions: [],
    bulanOptions: [
        ["Januari", 1],
        ["Februari", 2],
        ["Maret", 3],
        ["April", 4],
        ["Mei", 5],
        ["Juni", 6],
        ["Juli", 7],
        ["Agustus", 8],
        ["September", 9],
        ["Oktober", 10],
        ["November", 11],
        ["Desember", 12],
    ],
    modalMessage: "",
    pageTitle: "Bulan dan Tahun Aktif",

    get isActivePeriod() {
        return (
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun
        );
    },

    async init() {
        this.loading = true;
        try {
            const res = await fetch("/bulan-tahun");
            if (!res.ok)
                throw new Error(`BulanTahun API error! status: ${res.status}`);

            const data = await res.json();
            const aktifData = data.data.bt_aktif;

            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;
            this.activeBulan = aktifData.bulan;
            this.activeTahun = aktifData.tahun;
            this.tahunOptions = data.data.tahun || [aktifData.tahun];
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    async updateBulanTahun() {
        if (this.isActivePeriod) {
            this.modalMessage = "Bulan dan tahun terpilih sudah aktif.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        try {
            const response = await fetch("/bulan-tahun", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify({ bulan: this.bulan, tahun: this.tahun }),
            });

            const result = await response.json();

            if (!response.ok) {
                this.modalMessage =
                    result.message || "Gagal memperbarui periode aktif.";
                this.$dispatch("open-modal", "error-modal");
            } else {
                this.modalMessage = result.message;
                this.$dispatch("close");
                this.$dispatch("open-modal", "success-modal");

                // Reload the latest data
                await this.init();
            }
        } catch (error) {
            console.error("Update failed:", error);
            this.modalMessage = "Terjadi kesalahan saat memperbarui periode.";
            this.$dispatch("open-modal", "error-modal");
        }
    },
}));

Alpine.start();
