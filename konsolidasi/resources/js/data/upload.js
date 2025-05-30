import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    loading: true,

    bulan: "",
    tahun: "",
    activeBulan: "", // Store the active bulan
    activeTahun: "", // Store the active tahun
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

    confirmMessage: "",
    confirmDetails: "",
    formEvent: null,

    get isActivePeriod() {
        const result =
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun;
        return result;
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

    toggleDropdown(menu) {
        this.dropdowns[menu] = !this.dropdowns[menu];
    },

    closeDropdown(menu) {
        this.dropdowns[menu] = false;
    },
}));

Alpine.start();
