// document.addEventListener('alpine:init', () => {
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
            this.bulan = aktifData.bulan
                ? String(aktifData.bulan).padStart(2, "0")
                : "";
            this.tahun = aktifData.tahun || String(new Date().getFullYear());
            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            // Populate tahunOptions
            this.tahunOptions =
                bulanTahunData.tahun || (aktifData ? [aktifData.tahun] : []);

            // Set initial values from URL params if present
            const urlParams = new URLSearchParams(window.location.search);
            this.bulan = urlParams.get("bulan")
                ? String(urlParams.get("bulan")).padStart(2, "0")
                : this.bulan;
            this.tahun = urlParams.get("tahun") || this.tahun;
            this.selectedKdLevel = urlParams.get("kd_level") || "01";
            this.selectedKabkot = urlParams.get("kd_wilayah") || "";
            this.status = urlParams.get("status") || "all";

            this.updateKdWilayah();

            if (
                urlParams.get("bulan") === this.activeBulan &&
                urlParams.get("tahun") === this.activeTahun
            ) {
                this.isEditable = true;
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

    submitEditRekon() {
        const data = {
            alasan: this.selectedAlasan.join(", "),
            detail: this.detail,
            media: this.linkTerkait,
        };

        fetch(`/rekonsiliasi/update/${this.modalData.id}`, {
            method: "PUT",
            headers: {
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
                "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        })
            .then((response) => {
                if (response.ok) {
                    location.reload(); // Refresh page on success
                } else {
                    alert("Gagal menyimpan perubahan");
                }
            })
            .catch((error) => {
                console.error("Edit error:", error);
                alert("Error saat menyimpan");
            })
            .finally(() => this.$dispatch("close-modal", "edit-rekonsiliasi"));
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
// });
