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

    get isActivePeriod() {
        const result =
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun;
        return result;
    },

    async init() {
        this.loading = true;
        try {
            // Fetch Bulan and Tahun
            const bulanTahunResponse = await fetch("/bulan-tahun");
            const bulanTahunData = await bulanTahunResponse.json();

            const aktifData = bulanTahunData.data.bt_aktif;
            this.bulan = aktifData.bulan;
            this.tahun = aktifData.tahun;

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            this.tahunOptions =
                bulanTahunData.data.tahun ||
                (aktifData ? [aktifData.tahun] : []);
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.start();
