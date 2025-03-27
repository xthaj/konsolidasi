// document.addEventListener('alpine:init', () => {

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

    get isActivePeriod() {
        return (
            this.bulan === this.activeBulan && this.tahun === this.activeTahun
        );
    },

    async init() {
        this.loading = true; // Ensure loading is true at start
        try {
            // Fetch Komoditas
            const komoditasResponse = await fetch("/api/komoditas");
            const komoditasData = await komoditasResponse.json();
            this.komoditasData = komoditasData || [];

            // Fetch Wilayah
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.wilayahData = (wilayahData.provinces || []).concat(
                wilayahData.kabkots || []
            );

            // Fetch Bulan and Tahun
            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();

            const aktifData = bulanTahunData.bt_aktif; // First active record
            this.bulan = aktifData
                ? String(aktifData.bulan).padStart(2, "0")
                : "";
            this.tahun = aktifData ? aktifData.tahun : "";

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            // Populate tahunOptions, fallback if tahun is missing
            this.tahunOptions =
                bulanTahunData.tahun || (aktifData ? [aktifData.tahun] : []);
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false; // Turn off loading after initialization
        }
    },

    //Bulan Tahun methods

    async updateBulanTahun() {
        if (this.isActivePeriod) {
            this.failMessage = "Bulan dan tahun terpilih sudah aktif";
            this.failDetails = null;
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
            return;
        }

        console.log(this.bulan, this.tahun);

        const requestConfig = {
            method: "POST",
            url: "/update-bulan-tahun",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
            },
            body: JSON.stringify({ bulan: this.bulan, tahun: this.tahun }),
        };

        console.log(requestConfig.body);

        try {
            const response = await fetch(requestConfig.url, {
                method: requestConfig.method,
                headers: requestConfig.headers,
                body: requestConfig.body,
            });

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message;
                this.failDetails = data.details || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;
            this.successMessage = data.message;
            this.$dispatch("open-modal", "success-update-bulan-tahun");
        } catch (error) {
            this.failMessage = "An unexpected error occurred";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    // Komoditas Methods
    openAddKomoditasModal() {
        this.newKomoditas = { nama_komoditas: "" };
        this.$dispatch("open-modal", "add-komoditas");
    },

    async addKomoditas() {
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

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message || "Failed to add komoditas";
                this.failDetails = data.details || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun"); // Reuse fail modal or create a new one
                return;
            }

            this.komoditasData.push(data);
            this.successMessage = "Berhasil menambah komoditas!";
            this.$dispatch("open-modal", "success-update-bulan-tahun"); // Reuse success modal or create a new one
            this.$dispatch("close");
        } catch (error) {
            this.failMessage = "Terjadi kegagalan.";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    async updateKomoditas() {
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
                    }), // Only send nama_komoditas
                }
            );

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message || "Failed to update komoditas";
                this.failDetails = data.details || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            const index = this.komoditasData.findIndex(
                (k) => k.kd_komoditas === data.kd_komoditas
            );
            if (index !== -1) {
                this.komoditasData[index] = data;
            }
            this.successMessage = "Berhasil memperbarui komoditas!";
            this.$dispatch("open-modal", "success-update-bulan-tahun");
            this.$dispatch("close");
        } catch (error) {
            this.failMessage = "An unexpected error occurred";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    openEditKomoditasModal(komoditas) {
        this.editKomoditas = { ...komoditas };
        this.$dispatch("open-modal", "edit-komoditas");
    },

    deleteKomoditas(kd_komoditas) {
        console.log("clicked");
        const komoditas = this.komoditasData.find(
            (k) => k.kd_komoditas === kd_komoditas
        );
        if (!komoditas) return;

        // Set up the confirmation modal
        this.confirmMessage = `Apakah Anda yakin ingin menghapus komoditas "${komoditas.nama_komoditas}"?`;
        this.confirmDetails =
            "Seluruh inflasi & rekonsiliasi terkait juga akan terhapus dan tidak bisa dikembalikan.";
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

                const data = await response.json();

                if (!response.ok) {
                    this.failMessage =
                        data.message || "Failed to delete komoditas";
                    this.failDetails = data.details || null;
                    this.$dispatch("open-modal", "fail-update-bulan-tahun");
                    return;
                }

                this.komoditasData = this.komoditasData.filter(
                    (k) => k.kd_komoditas !== kd_komoditas
                );
                this.successMessage =
                    data.message || "Komoditas berhasil dihapus";
                this.$dispatch("open-modal", "success-update-bulan-tahun");
            } catch (error) {
                this.failMessage = "An unexpected error occurred";
                this.failDetails = { error: error.message };
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
            }
        };

        this.$dispatch("open-modal", "confirm-action");
    },

    executeConfirmAction() {
        if (this.confirmAction) {
            this.confirmAction(); // Execute the stored callback
            this.confirmAction = null; // Clear after execution
            this.confirmMessage = "";
            this.confirmDetails = null;
        }
        this.$dispatch("close"); // Close the modal
    },
}));
// });
