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
    selectedProvince: "",
    selectedKabkot: "",
    selectedKdLevel: "",
    kd_wilayah: "",
    status: "all",
    isEditable: false,
    komoditas: [],
    selectedKomoditas: "",
    modalData: {
        id: "",
        komoditas: "",
        kd_level: "",
        alasan: "",
        detail: "",
        media: "",
    },
    detail: "",
    linkTerkait: "",

    get isActivePeriod() {
        return (
            this.bulan === this.activeBulan && this.tahun === this.activeTahun
        );
    },

    async init() {
        this.loading = true;
        try {
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];

            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();
            const aktifData = bulanTahunData.bt_aktif || {};

            const komoditasResponse = await fetch("/api/komoditas");
            const komoditasData = await komoditasResponse.json();
            this.komoditas = komoditasData || [];

            this.bulan = aktifData.bulan
                ? String(aktifData.bulan).padStart(2, "0")
                : "";
            this.tahun = String(aktifData.tahun || new Date().getFullYear());

            const urlParams = new URLSearchParams(window.location.search);
            this.bulan = urlParams.get("bulan")
                ? String(urlParams.get("bulan")).padStart(2, "0")
                : this.bulan;
            this.tahun = urlParams.get("tahun") || this.tahun;
            this.selectedKdLevel = urlParams.get("kd_level") || "01";
            this.selectedKabkot = urlParams.get("kd_wilayah") || "";
            this.status = urlParams.get("status") || "all";

            this.activeBulan = aktifData.bulan
                ? String(aktifData.bulan).padStart(2, "0")
                : this.bulan;
            this.activeTahun = String(
                aktifData.tahun || new Date().getFullYear()
            );

            this.tahunOptions =
                bulanTahunData.tahun && Object.keys(bulanTahunData.tahun).length
                    ? Object.values(bulanTahunData.tahun).map(String)
                    : [this.tahun];

            this.updateKdWilayah();

            if (
                urlParams.get("bulan") === this.activeBulan &&
                urlParams.get("tahun") === this.activeTahun
            ) {
                this.isEditable = true;
            }

            // Add event listener for toggle-pembahasan
            this.$el.addEventListener("toggle-pembahasan", async (event) => {
                console.log("Toggle-pembahasan event received:", event.detail);
                const { id, checked } = event.detail;
                const success = await this.togglePembahasan(id, checked);
                if (!success) {
                    // Revert checkbox state on failure
                    event.target.checked = !checked;
                }
            });
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return this.kabkots;
        return this.kabkots.filter((k) =>
            k.kd_wilayah.startsWith(this.selectedProvince.substring(0, 2))
        );
    },

    updateKdWilayah() {
        this.kd_wilayah =
            this.selectedKabkot && this.selectedKabkot !== ""
                ? this.selectedKabkot
                : this.selectedProvince || "";
    },

    submitForm() {
        this.$refs.filterForm.submit();
    },

    alasanList: [
        "Kondisi Alam",
        "Masa Panen",
        "Gagal Panen",
        "Promo dan Diskon",
        "Harga Stok Melimpah",
        "Stok Menipis/Langka",
        "Harga Kembali Normal",
        "Turun Harga dari Distributor",
        "Kenaikan Harga dari Distributor",
        "Perbedaan Kualitas",
        "Supplier Menaikkan Harga",
        "Supplier Menurunkan Harga",
        "Persaingan Harga",
        "Permintaan Meningkat",
        "Permintaan Menurun",
        "Operasi Pasar",
        "Kebijakan Pemerintah Pusat",
        "Kebijakan Pemerintah Daerah",
        "Kesalahan Petugas Mencacah",
        "Penurunan Produksi",
        "Kenaikan Produksi",
        "Salah Entri Data",
        "Penggantian Responden",
        "Lainnya",
    ],
    selectedAlasan: [],

    modalOpen: false,

    openModal() {
        this.modalOpen = true;
    },

    closeModal() {
        this.modalOpen = false;
        this.item = {
            id: null,
            komoditas: "",
            harga: "",
            wilayah: "",
            levelHarga: "",
            periode: "",
        };
    },

    async togglePembahasan(rekonsiliasiId, checked) {
        try {
            console.log(
                "Toggling pembahasan for ID:",
                rekonsiliasiId,
                "Checked:",
                checked
            );
            // this.loading = true;
            const response = await fetch(
                `/rekonsiliasi/${rekonsiliasiId}/pembahasan`,
                {
                    method: "PATCH",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        )?.content,
                    },
                    body: JSON.stringify({
                        pembahasan: checked ? 1 : 0,
                    }),
                }
            );

            if (!response.ok) {
                throw new Error(
                    `Failed to update pembahasan: ${response.statusText}`
                );
            }

            const result = await response.json();
            console.log("Pembahasan updated:", result);
            return true;
        } catch (error) {
            console.error("Error updating pembahasan:", error);
            alert("Gagal memperbarui status pembahasan. Silakan coba lagi.");
            return false;
        } finally {
            // this.loading = false;
        }
    },
}));

Alpine.start();
