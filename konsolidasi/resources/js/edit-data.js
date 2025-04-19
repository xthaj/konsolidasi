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

    provinces: [],
    kabkots: [],
    komoditas: [],
    wilayahLevel: "", // New state for region level
    selectedProvince: "",
    selectedKabkot: "",
    selectedKomoditas: "",
    selectedKdLevel: "",
    isPusat: false,
    kd_wilayah: "",
    item: { id: null, komoditas: "", harga: "", wilayah: "", levelHarga: "" },
    sortColumn: "kd_komoditas",
    sortDirection: "asc",
    deleteRekonsiliasi: false,
    modalData: { id: "", komoditas: "" },

    get isActivePeriod() {
        return (
            this.bulan === this.activeBulan && this.tahun === this.activeTahun
        );
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    async init() {
        this.loading = true;

        try {
            // Initialize from query parameters
            const urlParams = new URLSearchParams(window.location.search);
            this.bulan = urlParams.get("bulan") || "";
            this.tahun = urlParams.get("tahun") || "";
            this.selectedKdLevel = urlParams.get("kd_level") || "";
            this.selectedKomoditas = urlParams.get("kd_komoditas") || "";
            const kdWilayah = urlParams.get("kd_wilayah") || "";
            this.isPusat = kdWilayah === "0";
            this.selectedProvince = kdWilayah && !this.isPusat ? kdWilayah : "";
            this.selectedKabkot =
                kdWilayah && kdWilayah.length > 2 ? kdWilayah : "";
            this.wilayahLevel = this.isPusat
                ? "pusat"
                : this.selectedKabkot
                ? "kabkot"
                : this.selectedProvince
                ? "provinsi"
                : "pusat";

            // Fetch wilayah data
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];

            // Fetch komoditas data
            const komoditasResponse = await fetch("/api/komoditas");
            const komoditasData = await komoditasResponse.json();
            this.komoditas = komoditasData || [];

            // Fetch bulan and tahun
            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();
            const aktifData = bulanTahunData.bt_aktif;
            this.activeBulan = aktifData
                ? String(aktifData.bulan).padStart(2, "0")
                : "";
            this.activeTahun = aktifData ? aktifData.tahun : "";
            if (!this.bulan) this.bulan = this.activeBulan;
            if (!this.tahun) this.tahun = this.activeTahun;
            this.tahunOptions =
                bulanTahunData.tahun || (aktifData ? [aktifData.tahun] : []);

            // Update kd_wilayah
            this.updateKdWilayah();
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
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

    updateKdWilayah() {
        this.isPusat = this.wilayahLevel === "pusat";
        if (this.selectedKdLevel === "all") {
            this.wilayahLevel = "pusat";
            this.isPusat = true;
            this.kd_wilayah = "0";
        } else if (this.wilayahLevel === "pusat") {
            this.kd_wilayah = "0";
        } else if (
            this.wilayahLevel === "kabkot" &&
            this.selectedKabkot &&
            this.selectedKdLevel === "01"
        ) {
            this.kd_wilayah = this.selectedKabkot;
        } else if (this.wilayahLevel === "provinsi" && this.selectedProvince) {
            this.kd_wilayah = this.selectedProvince;
        } else {
            this.kd_wilayah = "";
        }
    },

    checkFormValidity() {
        if (!this.bulan || !this.tahun || !this.selectedKdLevel) {
            return false;
        }
        if (this.selectedKdLevel === "all") {
            this.wilayahLevel = "pusat";
            this.isPusat = true;
            return true;
        }
        if (this.wilayahLevel === "pusat") {
            return true;
        }
        if (this.wilayahLevel === "provinsi" && this.selectedProvince) {
            return true;
        }
        if (
            this.wilayahLevel === "kabkot" &&
            this.selectedProvince &&
            this.selectedKabkot &&
            this.selectedKdLevel === "01"
        ) {
            return true;
        }
        return false;
    },

    getValidationMessage() {
        if (!this.bulan) return "Bulan belum dipilih.";
        if (!this.tahun) return "Tahun belum dipilih.";
        if (!this.selectedKdLevel) return "Level harga belum dipilih.";
        if (this.selectedKdLevel === "all") return "";
        if (this.wilayahLevel === "pusat") return "";
        if (this.wilayahLevel === "provinsi" && !this.selectedProvince) {
            return "Provinsi belum dipilih.";
        }
        if (this.wilayahLevel === "kabkot") {
            if (!this.selectedProvince) return "Provinsi belum dipilih.";
            if (!this.selectedKabkot) return "Kabupaten/Kota belum dipilih.";
            if (this.selectedKdLevel !== "01") {
                return "Kabupaten/Kota hanya tersedia untuk Harga Konsumen Kota.";
            }
        }
        return "";
    },

    openDeleteModal(id, komoditas) {
        this.modalData = { id, komoditas };
        this.deleteRekonsiliasi = false;
        this.$dispatch("open-modal", "confirm-delete");
        console.log("Opening modal with:", this.modalData);
    },

    async confirmDelete() {
        if (!this.deleteRekonsiliasi) {
            console.log("Delete aborted: deleteRekonsiliasi is false");
            return;
        }

        try {
            const csrfToken = document
                .querySelector('meta[name="csrf-token"]')
                .getAttribute("content");
            const response = await fetch(`/data/delete/${this.modalData.id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ delete_rekonsiliasi: true }),
            });

            if (response.ok) {
                console.log("Delete successful, reloading page");
                location.reload();
            } else {
                console.error("Delete failed:", response.status);
                alert("Gagal menghapus");
            }
        } catch (error) {
            console.error("Delete error:", error);
            alert("Error saat menghapus");
        } finally {
            this.$dispatch("close-modal", "confirm-delete");
        }
    },

    setItem(id, komoditas, harga, wilayah, levelHarga) {
        console.log("Setting item:", {
            id,
            komoditas,
            harga,
            wilayah,
            levelHarga,
        });
        this.item = { id, komoditas, harga, wilayah, levelHarga };
        const form = document.querySelector("#edit-harga-form");
        if (form) {
            form.action = `/data/update/${id}`;
            console.log("Form action updated to:", form.action);
        } else {
            console.error("Edit form not found!");
        }
    },
}));

Alpine.start();
