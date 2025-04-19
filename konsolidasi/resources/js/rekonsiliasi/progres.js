import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true, // Start with loading true
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
            // Fetch Wilayah data
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];

            // Fetch active BulanTahun
            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();
            const aktifData = bulanTahunData.bt_aktif || {};

            console.log("bt responsde: ", bulanTahunData);

            // Set defaults
            this.bulan = aktifData.bulan
                ? String(aktifData.bulan).padStart(2, "0")
                : "";
            this.tahun = String(aktifData.tahun || new Date().getFullYear());

            // Override with URL params
            const urlParams = new URLSearchParams(window.location.search);
            this.bulan = urlParams.get("bulan")
                ? String(urlParams.get("bulan")).padStart(2, "0")
                : this.bulan;
            this.tahun = urlParams.get("tahun") || this.tahun;
            this.selectedKdLevel = urlParams.get("kd_level") || "01";
            this.selectedKabkot = urlParams.get("kd_wilayah") || "";
            this.status = urlParams.get("status") || "all";

            // Set active values *after* URL params
            this.activeBulan = aktifData.bulan
                ? String(aktifData.bulan).padStart(2, "0")
                : this.bulan;
            this.activeTahun = String(
                aktifData.tahun || new Date().getFullYear()
            );

            // Populate tahunOptions
            this.tahunOptions =
                bulanTahunData.tahun && Object.keys(bulanTahunData.tahun).length
                    ? Object.values(bulanTahunData.tahun).map(String)
                    : [this.tahun];

            console.log("tahunOptions: ", this.tahunOptions);
            // Debugging
            console.log("tahun:", this.tahun);
            console.log("bulan:", this.bulan);
            console.log("activeTahun:", this.activeTahun);
            console.log("activeBulan:", this.activeBulan);
            console.log("urlParams.get('tahun'):", urlParams.get("tahun"));
            console.log("urlParams.get('bulan'):", urlParams.get("bulan"));

            this.updateKdWilayah();

            // Check editability
            if (
                urlParams.get("bulan") === this.activeBulan &&
                urlParams.get("tahun") === this.activeTahun
            ) {
                this.isEditable = true;
                console.log("editable");
            } else {
                console.log("not editable");
            }
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
        this.$refs.filterForm.submit(); // Manually trigger form submission if needed
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

    // Dropdown handlers
    toggleDropdown(menu) {
        this.dropdowns[menu] = !this.dropdowns[menu];
    },
    closeDropdown(menu) {
        this.dropdowns[menu] = false;
    },

    modalOpen: false,

    openModal() {
        this.modalOpen = true;
    },

    openEditRekonModal(id, komoditas, kd_level, alasan, detail, media) {
        // Populate modalData for display fields
        this.modalData = {
            id,
            komoditas,
            kd_level,
            alasan,
            detail,
            media,
        };

        // Populate form fields
        this.selectedAlasan = alasan ? alasan.split(", ") : [];
        this.detail = detail || ""; // Set detail for textarea
        this.linkTerkait = media || ""; // Set linkTerkait for input

        console.log("Opening edit modal with:", this.modalData);

        // Open the modal
        this.$dispatch("open-modal", "edit-rekonsiliasi");
    },

    openDeleteModal(id, komoditas, nama_wilayah, kd_level, bulan_tahun_id) {
        this.modalData = {
            id,
            komoditas,
            nama_wilayah,
            kd_level,
            bulan_tahun_id,
        };
        this.$dispatch("open-modal", "delete-rekonsiliasi");
        console.log("Opening delete modal with:", this.modalData);
    },

    async submitEditRekon() {
        // Validate checkbox selection
        if (
            !Array.isArray(this.selectedAlasan) ||
            this.selectedAlasan.length === 0
        ) {
            alert("Pilih setidaknya satu alasan");
            return;
        }

        const sanitizeInput = (input) => {
            if (typeof input !== "string") return input; // Only process strings
            return input
                .trim() // Remove leading/trailing whitespace
                .replace(/[\r\n]+/g, " ") // Replace all line breaks with a space
                .replace(/`+/g, "'") // Replace backticks with single quotes
                .replace(/\s+/g, " "); // Replace multiple spaces with a single space
        };

        const data = {
            alasan: this.selectedAlasan.join(", "),
            detail: sanitizeInput(this.detail),
            media: this.linkTerkait,
        };

        try {
            const response = await fetch(
                `/rekonsiliasi/update/${this.modalData.id}`,
                {
                    method: "PUT",
                    headers: {
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify(data),
                }
            );

            if (response.ok) {
                alert("Berhasil menyimpan perubahan");
                location.reload(); // Refresh page on success
            } else {
                alert("Gagal menyimpan perubahan");
            }
        } catch (error) {
            console.error("Edit error:", error);
            alert("Error saat menyimpan");
        } finally {
            this.$dispatch("close-modal", "edit-rekonsiliasi");
        }
    },

    submitForm() {
        this.$refs.filterForm.submit();
    },

    async confirmDelete(id) {
        try {
            console.log("Deleting rekonsiliasi:", id);

            const response = await fetch(`/rekonsiliasi/${id}`, {
                method: "DELETE",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                    Accept: "application/json",
                },
            });

            if (response.ok) {
                alert("Berhasil menghapus data");
                // console.log("Delete successful, reloading page");
                location.reload();
            } else {
                alert("Gagal menghapus");
                console.error("Delete failed:", response.status);
            }
        } catch (error) {
            console.error("Delete error:", error);
            alert("Error saat menghapus");
        } finally {
            this.$dispatch("close-modal", "delete-rekonsiliasi"); // Fix modal name to match template
        }
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
}));
Alpine.start();
