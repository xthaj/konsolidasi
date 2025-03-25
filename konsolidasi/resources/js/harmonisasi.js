// document.addEventListener('alpine:init', () => {
Alpine.data("webData", () => ({
    bulan: "",
    tahun: "",
    activeBulan: "",
    activeTahun: "",
    tahunOptions: [],

    provinces: [],
    kabkots: [],
    komoditas: [],

    selectedProvince: "",
    selectedKabkot: "",
    selectedKomoditas: "",
    selectedKdLevel: "",
    isPusat: false,
    kd_wilayah: "",

    // Store ECharts instances
    multiAxisChart: null,
    horizontalBarChart: null,
    stackedBarChart: null,
    rankBarChartProvinsi: null,
    rankBarChartProvinsi2: null,
    map: null,
    map2: null,
    priceLevelCharts: {},
    inflationHeatmap: null,

    // Static map data (same as before)
    // mapDemo: {
    //     97: 3.07,
    //     96: 5.61,
    //     95: 5.79,
    //     94: 6.79,
    //     92: 0.55,
    //     91: 4.71,
    //     82: 3.15,
    //     81: 3.64,
    //     76: 2.61,
    //     75: 3.79,
    //     74: 3.65,
    //     73: 4.96,
    //     72: 2.74,
    //     71: 2.66,
    //     65: 3.1,
    //     64: 6.16,
    //     63: 4.57,
    //     62: 0.09,
    //     61: 0.98,
    //     53: 2.69,
    //     52: 3.8,
    //     51: 4.16,
    //     36: 4.96,
    //     35: 1.1,
    //     34: 0.7,
    //     33: 0.12,
    //     32: 2.39,
    //     31: 2.98,
    //     21: 1.49,
    //     19: 0.96,
    //     18: 1.56,
    //     17: 4.63,
    //     16: 6.44,
    //     15: 5.46,
    //     14: 1.41,
    //     13: 5.59,
    //     12: 4.54,
    //     11: 2.37,
    // },
    // mapDemo2: {
    //     9702: 5.0,
    //     9604: 4.62,
    //     9601: 2.4,
    //     9501: 5.71,
    //     9471: 6.08,
    //     9271: 4.82,
    //     9203: 1.82,
    //     9202: 5.52,
    //     9105: 2.12,
    //     8271: 0.51,
    //     8202: 5.26,
    //     8172: 1.44,
    //     8171: 1.42,
    //     8103: 5.62,
    //     7604: 1.9,
    //     7472: 5.67,
    //     7571: 4.48,
    //     7502: 1.57,
    //     7471: 0.59,
    //     7404: 1.21,
    //     7403: 6.93,
    //     7373: 5.23,
    //     7372: 1.13,
    //     7371: 5.71,
    //     7325: 1.92,
    //     7314: 4.94,
    //     7313: 2.78,
    //     7311: 4.85,
    //     7302: 0.3,
    //     7271: 5.29,
    //     7206: 2.35,
    //     7203: 3.31,
    //     7202: 4.18,
    //     7174: 1.84,
    //     7171: 2.05,
    //     7106: 2.25,
    //     7105: 1.85,
    //     6571: 1.91,
    //     6504: 5.36,
    //     6502: 6.15,
    //     6472: 4.63,
    //     6471: 0.19,
    //     6409: 4.24,
    //     6405: 2.33,
    //     6371: 0.16,
    //     6309: 5.68,
    //     6307: 4.73,
    //     6302: 4.64,
    //     6301: 1.82,
    //     6271: 1.62,
    //     6206: 4.67,
    //     6203: 2.02,
    //     6202: 5.63,
    //     6172: 5.54,
    //     6171: 3.4,
    //     6111: 0.08,
    //     6107: 6.2,
    //     6106: 5.37,
    //     5371: 3.66,
    //     5312: 3.49,
    //     5310: 0.64,
    //     5304: 4.24,
    //     5302: 4.21,
    //     5272: 1.04,
    //     5271: 2.39,
    //     5204: 0.18,
    //     5171: 4.57,
    //     5108: 6.76,
    //     5103: 4.22,
    //     5102: 0.23,
    //     3673: 6.56,
    //     3672: 6.58,
    //     3671: 3.64,
    //     3602: 5.22,
    //     3601: 5.3,
    //     3578: 2.41,
    //     3577: 6.05,
    //     3574: 3.72,
    //     3573: 1.53,
    //     3571: 3.23,
    //     3529: 1.21,
    //     3525: 5.96,
    //     3522: 0.21,
    //     3510: 5.23,
    //     3509: 0.68,
    //     3504: 4.4,
    //     3471: 4.03,
    //     3403: 4.91,
    //     3376: 6.4,
    //     3374: 4.6,
    //     3372: 5.71,
    //     3319: 0.19,
    //     3317: 1.32,
    //     3312: 3.54,
    //     3307: 2.05,
    //     3302: 6.28,
    //     3301: 6.45,
    //     3278: 6.61,
    //     3276: 5.76,
    //     3275: 1.48,
    //     3274: 4.86,
    //     3273: 4.63,
    //     3272: 4.14,
    //     3271: 0.9,
    //     3213: 2.29,
    //     3210: 2.1,
    //     3204: 4.01,
    //     3100: 6.33,
    //     2172: 3.05,
    //     2171: 0.21,
    //     2101: 1.77,
    //     1971: 1.96,
    //     1906: 4.81,
    //     1903: 4.04,
    //     1902: 1.65,
    //     1872: 6.87,
    //     1871: 1.35,
    //     1811: 0.46,
    //     1804: 1.69,
    //     1771: 6.35,
    //     1706: 6.58,
    //     1674: 3.27,
    //     1671: 3.37,
    //     1603: 5.17,
    //     1602: 5.06,
    //     1571: 5.99,
    //     1509: 4.51,
    //     1501: 4.18,
    //     1473: 2.34,
    //     1471: 1.17,
    //     1406: 1.97,
    //     1403: 6.35,
    //     1375: 1.97,
    //     1371: 0.04,
    //     1312: 2.28,
    //     1311: 0.98,
    //     1278: 4.71,
    //     1277: 3.66,
    //     1275: 1.13,
    //     1273: 5.98,
    //     1271: 2.46,
    //     1212: 4.98,
    //     1211: 4.76,
    //     1207: 5.18,
    //     1174: 3.28,
    //     1171: 1.5,
    //     1114: 4.49,
    //     1107: 1.51,
    //     1106: 0.19,
    // },

    async dataCheck(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const csrfToken = document
            .querySelector('meta[name="csrf-token"]')
            .getAttribute("content");

        try {
            const response = await fetch("/visualisasi/cek-data", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": csrfToken,
                    Accept: "application/json",
                },
                body: formData,
            });

            const result = await response.json();
            if (result.success) {
                alert("Data tersedia! Memuat visualisasi...");
                window.location.reload(); // Or trigger visualization render
            } else {
                alert(
                    "Beberapa data tidak tersedia: " +
                        (result.message || "Unknown error")
                );
            }
        } catch (error) {
            console.error("Error:", error);
            alert("Terjadi kesalahan: " + error.message);
        }
    },

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

            const komoditasResponse = await fetch("/api/komoditas");
            const komoditasData = await komoditasResponse.json();
            this.komoditas = komoditasData || [];

            const bulanTahunResponse = await fetch("/api/bulan_tahun");
            const bulanTahunData = await bulanTahunResponse.json();

            const aktifData = bulanTahunData.bt_aktif;
            this.bulan = aktifData
                ? String(aktifData.bulan).padStart(2, "0")
                : "";
            this.tahun = aktifData ? aktifData.tahun : "";

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            this.tahunOptions =
                bulanTahunData.tahun || (aktifData ? [aktifData.tahun] : []);

            this.inflationData = {
                11: {
                    "Harga Produsen": 2.37,
                    "Harga Produsen Desa": 2.5,
                    "Harga Perdagangan Besar": 2.2,
                    "Harga Konsumen Desa": 2.45,
                    "Harga Konsumen Kota": 2.6,
                },
                12: {
                    "Harga Produsen": 4.54,
                    "Harga Produsen Desa": 4.7,
                    "Harga Perdagangan Besar": 4.3,
                    "Harga Konsumen Desa": 4.6,
                    "Harga Konsumen Kota": 4.8,
                },
                13: {
                    "Harga Produsen": 5.59,
                    "Harga Produsen Desa": 5.8,
                    "Harga Perdagangan Besar": 5.4,
                    "Harga Konsumen Desa": 5.7,
                    "Harga Konsumen Kota": 5.9,
                },
                14: {
                    "Harga Produsen": 1.41,
                    "Harga Produsen Desa": 1.6,
                    "Harga Perdagangan Besar": 1.3,
                    "Harga Konsumen Desa": 1.5,
                    "Harga Konsumen Kota": 1.7,
                },
                15: {
                    "Harga Produsen": 5.46,
                    "Harga Produsen Desa": 5.6,
                    "Harga Perdagangan Besar": 5.3,
                    "Harga Konsumen Desa": 5.5,
                    "Harga Konsumen Kota": 5.7,
                },
                16: {
                    "Harga Produsen": 6.44,
                    "Harga Produsen Desa": 6.6,
                    "Harga Perdagangan Besar": 6.2,
                    "Harga Konsumen Desa": 6.5,
                    "Harga Konsumen Kota": 6.7,
                },
                17: {
                    "Harga Produsen": 4.63,
                    "Harga Produsen Desa": 4.8,
                    "Harga Perdagangan Besar": 4.5,
                    "Harga Konsumen Desa": 4.7,
                    "Harga Konsumen Kota": 4.9,
                },
                18: {
                    "Harga Produsen": 1.56,
                    "Harga Produsen Desa": 1.7,
                    "Harga Perdagangan Besar": 1.4,
                    "Harga Konsumen Desa": 1.6,
                    "Harga Konsumen Kota": 1.8,
                },
                19: {
                    "Harga Produsen": 0.96,
                    "Harga Produsen Desa": 1.1,
                    "Harga Perdagangan Besar": 0.9,
                    "Harga Konsumen Desa": 1.0,
                    "Harga Konsumen Kota": 1.2,
                },
                21: {
                    "Harga Produsen": 1.49,
                    "Harga Produsen Desa": 1.6,
                    "Harga Perdagangan Besar": 1.3,
                    "Harga Konsumen Desa": 1.5,
                    "Harga Konsumen Kota": 1.7,
                },
                31: {
                    "Harga Produsen": 2.98,
                    "Harga Produsen Desa": 3.1,
                    "Harga Perdagangan Besar": 2.8,
                    "Harga Konsumen Desa": 3.0,
                    "Harga Konsumen Kota": 3.2,
                },
                32: {
                    "Harga Produsen": 2.39,
                    "Harga Produsen Desa": 2.5,
                    "Harga Perdagangan Besar": 2.2,
                    "Harga Konsumen Desa": 2.4,
                    "Harga Konsumen Kota": 2.6,
                },
                33: {
                    "Harga Produsen": 0.12,
                    "Harga Produsen Desa": 0.3,
                    "Harga Perdagangan Besar": 0.1,
                    "Harga Konsumen Desa": 0.2,
                    "Harga Konsumen Kota": 0.4,
                },
                34: {
                    "Harga Produsen": 0.7,
                    "Harga Produsen Desa": 0.9,
                    "Harga Perdagangan Besar": 0.6,
                    "Harga Konsumen Desa": 0.8,
                    "Harga Konsumen Kota": 1.0,
                },
                35: {
                    "Harga Produsen": 1.1,
                    "Harga Produsen Desa": 1.3,
                    "Harga Perdagangan Besar": 1.0,
                    "Harga Konsumen Desa": 1.2,
                    "Harga Konsumen Kota": 1.4,
                },
                36: {
                    "Harga Produsen": 4.96,
                    "Harga Produsen Desa": 5.1,
                    "Harga Perdagangan Besar": 4.8,
                    "Harga Konsumen Desa": 5.0,
                    "Harga Konsumen Kota": 5.2,
                },
                51: {
                    "Harga Produsen": 4.16,
                    "Harga Produsen Desa": 4.3,
                    "Harga Perdagangan Besar": 4.0,
                    "Harga Konsumen Desa": 4.2,
                    "Harga Konsumen Kota": 4.4,
                },
                52: {
                    "Harga Produsen": 3.8,
                    "Harga Produsen Desa": 4.0,
                    "Harga Perdagangan Besar": 3.6,
                    "Harga Konsumen Desa": 3.9,
                    "Harga Konsumen Kota": 4.1,
                },
                53: {
                    "Harga Produsen": 2.69,
                    "Harga Produsen Desa": 2.8,
                    "Harga Perdagangan Besar": 2.5,
                    "Harga Konsumen Desa": 2.7,
                    "Harga Konsumen Kota": 2.9,
                },
                61: {
                    "Harga Produsen": 0.98,
                    "Harga Produsen Desa": 1.1,
                    "Harga Perdagangan Besar": 0.9,
                    "Harga Konsumen Desa": 1.0,
                    "Harga Konsumen Kota": 1.2,
                },
                62: {
                    "Harga Produsen": 0.09,
                    "Harga Produsen Desa": 0.2,
                    "Harga Perdagangan Besar": 0.05,
                    "Harga Konsumen Desa": 0.15,
                    "Harga Konsumen Kota": 0.3,
                },
                63: {
                    "Harga Produsen": 4.57,
                    "Harga Produsen Desa": 4.7,
                    "Harga Perdagangan Besar": 4.4,
                    "Harga Konsumen Desa": 4.6,
                    "Harga Konsumen Kota": 4.8,
                },
                64: {
                    "Harga Produsen": 6.16,
                    "Harga Produsen Desa": 6.3,
                    "Harga Perdagangan Besar": 6.0,
                    "Harga Konsumen Desa": 6.2,
                    "Harga Konsumen Kota": 6.4,
                },
                65: {
                    "Harga Produsen": 3.1,
                    "Harga Produsen Desa": 3.3,
                    "Harga Perdagangan Besar": 3.0,
                    "Harga Konsumen Desa": 3.2,
                    "Harga Konsumen Kota": 3.4,
                },
                71: {
                    "Harga Produsen": 2.66,
                    "Harga Produsen Desa": 2.8,
                    "Harga Perdagangan Besar": 2.5,
                    "Harga Konsumen Desa": 2.7,
                    "Harga Konsumen Kota": 2.9,
                },
                72: {
                    "Harga Produsen": 2.74,
                    "Harga Produsen Desa": 2.9,
                    "Harga Perdagangan Besar": 2.6,
                    "Harga Konsumen Desa": 2.8,
                    "Harga Konsumen Kota": 3.0,
                },
                73: {
                    "Harga Produsen": 4.96,
                    "Harga Produsen Desa": 5.1,
                    "Harga Perdagangan Besar": 4.8,
                    "Harga Konsumen Desa": 5.0,
                    "Harga Konsumen Kota": 5.2,
                },
                74: {
                    "Harga Produsen": 3.65,
                    "Harga Produsen Desa": 3.8,
                    "Harga Perdagangan Besar": 3.5,
                    "Harga Konsumen Desa": 3.7,
                    "Harga Konsumen Kota": 3.9,
                },
                75: {
                    "Harga Produsen": 3.79,
                    "Harga Produsen Desa": 4.0,
                    "Harga Perdagangan Besar": 3.6,
                    "Harga Konsumen Desa": 3.9,
                    "Harga Konsumen Kota": 4.1,
                },
                76: {
                    "Harga Produsen": 2.61,
                    "Harga Produsen Desa": 2.8,
                    "Harga Perdagangan Besar": 2.5,
                    "Harga Konsumen Desa": 2.7,
                    "Harga Konsumen Kota": 2.9,
                },
                81: {
                    "Harga Produsen": 3.64,
                    "Harga Produsen Desa": 3.8,
                    "Harga Perdagangan Besar": 3.5,
                    "Harga Konsumen Desa": 3.7,
                    "Harga Konsumen Kota": 3.9,
                },
                82: {
                    "Harga Produsen": 3.15,
                    "Harga Produsen Desa": 3.3,
                    "Harga Perdagangan Besar": 3.0,
                    "Harga Konsumen Desa": 3.2,
                    "Harga Konsumen Kota": 3.4,
                },
                91: {
                    "Harga Produsen": 4.71,
                    "Harga Produsen Desa": 4.9,
                    "Harga Perdagangan Besar": 4.6,
                    "Harga Konsumen Desa": 4.8,
                    "Harga Konsumen Kota": 5.0,
                },
                92: {
                    "Harga Produsen": 0.55,
                    "Harga Produsen Desa": 0.7,
                    "Harga Perdagangan Besar": 0.5,
                    "Harga Konsumen Desa": 0.6,
                    "Harga Konsumen Kota": 0.8,
                },
                94: {
                    "Harga Produsen": 6.79,
                    "Harga Produsen Desa": 7.0,
                    "Harga Perdagangan Besar": 6.6,
                    "Harga Konsumen Desa": 6.9,
                    "Harga Konsumen Kota": 7.1,
                },
                95: {
                    "Harga Produsen": 5.79,
                    "Harga Produsen Desa": 6.0,
                    "Harga Perdagangan Besar": 5.6,
                    "Harga Konsumen Desa": 5.9,
                    "Harga Konsumen Kota": 6.1,
                },
                96: {
                    "Harga Produsen": 5.61,
                    "Harga Produsen Desa": 5.8,
                    "Harga Perdagangan Besar": 5.4,
                    "Harga Konsumen Desa": 5.7,
                    "Harga Konsumen Kota": 5.9,
                },
                97: {
                    "Harga Produsen": 3.07,
                    "Harga Produsen Desa": 3.2,
                    "Harga Perdagangan Besar": 2.9,
                    "Harga Konsumen Desa": 3.1,
                    "Harga Konsumen Kota": 3.3,
                },
            };
            // this.initCharts();
            // this.initMaps();

            // Add resize event listener
            // this.handleResize = () => {
            //     if (this.multiAxisChart) this.multiAxisChart.resize();
            //     if (this.horizontalBarChart) this.horizontalBarChart.resize();
            //     if (this.stackedBarChart) this.stackedBarChart.resize();
            //     if (this.rankBarChartProvinsi)
            //         this.rankBarChartProvinsi.resize();
            //     if (this.rankBarChartProvinsi2)
            //         this.rankBarChartProvinsi2.resize();
            //     if (this.inflationHeatmap) this.inflationHeatmap.resize();
            //     if (this.map) this.map.resize();
            //     if (this.map2) this.map2.resize();
            //     Object.values(this.priceLevelCharts).forEach((chart) => {
            //         if (chart) chart.resize();
            //     });
            // };

            // window.addEventListener("resize", this.handleResize);
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    // // Ensure cleanup when the component is destroyed
    // destroy() {
    //     window.removeEventListener("resize", this.handleResize);
    // },

    // labels: [
    //     "November 2024",
    //     "December 2024",
    //     "January 2025",
    //     "February 2025",
    //     "March 2025",
    // ],

    // datasets: [
    //     {
    //         label: "Harga Produsen",
    //         inflasi: [5.5, 3.0, 2.8, 3.2, -3.1],
    //         andil: [0.5, 0.6, 0.55, 0.65, 0.6],
    //         stacked: [20, 30, 50],
    //     },
    //     {
    //         label: "Harga Produsen Desa",
    //         inflasi: [3.8, 2.1, 2.0, 2.3, 2.2],
    //         andil: [0.4, 0.45, 0.43, 0.47, 0.44],
    //         stacked: [20, 30, 50],
    //     },
    //     {
    //         label: "Harga Perdagangan Besar",
    //         inflasi: [2.5, -3.0, 2.8, -3.2, -3.1],
    //         andil: [0.5, 0.8, 0.34, 0.15, 0.6],
    //         stacked: [20, 30, 50],
    //     },
    //     {
    //         label: "Harga Konsumen Desa",
    //         inflasi: [-1.8, 3.1, 4.0, 2.9, 7.2],
    //         andil: [0.4, 0.3, 0.68, 0.25, 0.43],
    //         stacked: [20, 30, 50],
    //     },
    //     {
    //         label: "Harga Konsumen Kota",
    //         inflasi: [2.5, 3.0, 7.0, 3.2, 3.1],
    //         andil: [0.22, 0.52, 0.32, 0.65, 0.6],
    //         stacked: [20, 30, 50],
    //     },
    // ],

    // priceLevels: [
    //     "Harga Produsen",
    //     "Harga Produsen Desa",
    //     "Harga Perdagangan Besar",
    //     "Harga Konsumen Desa",
    //     "Harga Konsumen Kota",
    // ],

    // provinsi: [
    //     "ACEH",
    //     "SUMATERA UTARA",
    //     "SUMATERA BARAT",
    //     "RIAU",
    //     "JAMBI",
    //     "SUMATERA SELATAN",
    //     "BENGKULU",
    //     "LAMPUNG",
    //     "KEPULAUAN BANGKA BELITUNG",
    //     "KEPULAUAN RIAU",
    //     "DKI JAKARTA",
    //     "JAWA BARAT",
    //     "JAWA TENGAH",
    //     "DI YOGYAKARTA",
    //     "JAWA TIMUR",
    //     "BANTEN",
    //     "BALI",
    //     "NUSA TENGGARA BARAT",
    //     "NUSA TENGGARA TIMUR",
    //     "KALIMANTAN BARAT",
    //     "KALIMANTAN TENGAH",
    //     "KALIMANTAN SELATAN",
    //     "KALIMANTAN TIMUR",
    //     "KALIMANTAN UTARA",
    //     "SULAWESI UTARA",
    //     "SULAWESI TENGAH",
    //     "SULAWESI SELATAN",
    //     "SULAWESI TENGGARA",
    //     "GORONTALO",
    //     "SULAWESI BARAT",
    //     "MALUKU",
    //     "MALUKU UTARA",
    //     "PAPUA BARAT",
    //     "PAPUA BARAT DAYA",
    //     "PAPUA",
    //     "PAPUA SELATAN",
    //     "PAPUA TENGAH",
    //     "PAPUA PEGUNUNGAN",
    // ],

    // kota: [
    //     "KAB JAYAWIJAYA",
    //     "KAB NABIRE",
    //     "TIMIKA",
    //     "MERAUKE",
    //     "KOTA JAYAPURA",
    //     "KOTA SORONG",
    //     "KAB SORONG SELATAN",
    //     "KAB SORONG",
    //     "MANOKWARI",
    //     "KOTA TERNATE",
    //     "KAB HALMAHERA TENGAH",
    //     "KOTA TUAL",
    //     "KOTA AMBON",
    //     "KAB MALUKU TENGAH",
    //     "MAMUJU",
    //     "KAB MAJENE",
    //     "KOTA GORONTALO",
    //     "KAB GORONTALO",
    //     "KOTA BAU BAU",
    //     "KOTA KENDARI",
    //     "KAB KOLAKA",
    //     "KAB KONAWE",
    //     "KOTA PALOPO",
    //     "KOTA PARE PARE",
    //     "KOTA MAKASSAR",
    //     "KAB LUWU TIMUR",
    //     "KAB SIDENRENG RAPPANG",
    //     "KAB WAJO",
    //     "WATAMPONE",
    //     "BULUKUMBA",
    //     "KOTA PALU",
    //     "KAB TOLI TOLI",
    //     "KAB MOROWALI",
    //     "LUWUK",
    //     "KOTA KOTAMOBAGU",
    //     "KOTA MANADO",
    //     "KAB MINAHASA UTARA",
    //     "KAB MINAHASA SELATAN",
    //     "KOTA TARAKAN",
    //     "KAB NUNUKAN",
    //     "TANJUNG SELOR",
    //     "KOTA SAMARINDA",
    //     "KOTA BALIKPAPAN",
    //     "KAB PENAJAM PASER UTARA",
    //     "KAB BERAU",
    //     "KOTA BANJARMASIN",
    //     "TANJUNG",
    //     "KAB HULU SUNGAI TENGAH",
    //     "KOTABARU",
    //     "KAB TANAH LAUT",
    //     "KOTA PALANGKARAYA",
    //     "KAB SUKAMARA",
    //     "KAB KAPUAS",
    //     "SAMPIT",
    //     "KOTA SINGKAWANG",
    //     "KOTA PONTIANAK",
    //     "KAB KAYONG UTARA",
    //     "SINTANG",
    //     "KAB KETAPANG",
    //     "KOTA KUPANG",
    //     "KAB NGADA",
    //     "MAUMERE",
    //     "KAB TIMOR TENGAH SELATAN",
    //     "WAINGAPU",
    //     "KOTA BIMA",
    //     "KOTA MATARAM",
    //     "KAB SUMBAWA",
    //     "KOTA DENPASAR",
    //     "SINGARAJA",
    //     "KAB BADUNG",
    //     "KAB TABANAN",
    //     "KOTA SERANG",
    //     "KOTA CILEGON",
    //     "KOTA TANGERANG",
    //     "KAB LEBAK",
    //     "KAB PANDEGLANG",
    //     "KOTA SURABAYA",
    //     "KOTA MADIUN",
    //     "KOTA PROBOLINGGO",
    //     "KOTA MALANG",
    //     "KOTA KEDIRI",
    //     "SUMENEP",
    //     "KAB GRESIK",
    //     "KAB BOJONEGORO",
    //     "BANYUWANGI",
    //     "JEMBER",
    //     "KAB TULUNGAGUNG",
    //     "KOTA YOGYAKARTA",
    //     "KAB GUNUNGKIDUL",
    //     "KOTA TEGAL",
    //     "KOTA SEMARANG",
    //     "KOTA SURAKARTA",
    //     "KUDUS",
    //     "KAB REMBANG",
    //     "KAB WONOGIRI",
    //     "KAB WONOSOBO",
    //     "PURWOKERTO",
    //     "CILACAP",
    //     "KOTA TASIKMALAYA",
    //     "KOTA DEPOK",
    //     "KOTA BEKASI",
    //     "KOTA CIREBON",
    //     "KOTA BANDUNG",
    //     "KOTA SUKABUMI",
    //     "KOTA BOGOR",
    //     "KAB SUBANG",
    //     "KAB MAJALENGKA",
    //     "KAB BANDUNG",
    //     "DKI JAKARTA",
    //     "KOTA TANJUNG PINANG",
    //     "KOTA BATAM",
    //     "KAB KARIMUN",
    //     "KOTA PANGKAL PINANG",
    //     "KAB BELITUNG TIMUR",
    //     "KAB BANGKA BARAT",
    //     "TANJUNG PANDAN",
    //     "KOTA METRO",
    //     "KOTA BANDAR LAMPUNG",
    //     "KAB MESUJI",
    //     "KAB LAMPUNG TIMUR",
    //     "KOTA BENGKULU",
    //     "KAB MUKO MUKO",
    //     "KOTA LUBUK LINGGAU",
    //     "KOTA PALEMBANG",
    //     "KAB MUARA ENIM",
    //     "KAB OGAN KOMERING ILIR",
    //     "KOTA JAMBI",
    //     "MUARA BUNGO",
    //     "KAB KERINCI",
    //     "KOTA DUMAI",
    //     "KOTA PEKANBARU",
    //     "KAB KAMPAR",
    //     "TEMBILAHAN",
    //     "KOTA BUKITTINGGI",
    //     "KOTA PADANG",
    //     "KAB PASAMAN BARAT",
    //     "KAB DHARMASRAYA",
    //     "KOTA GUNUNGSITOLI",
    //     "KOTA PADANGSIDIMPUAN",
    //     "KOTA MEDAN",
    //     "KOTA PEMATANG SIANTAR",
    //     "KOTA SIBOLGA",
    //     "KAB DELI SERDANG",
    //     "KAB KARO",
    //     "KAB LABUHANBATU",
    //     "KOTA LHOKSEUMAWE",
    //     "KOTA BANDA ACEH",
    //     "KAB ACEH TAMIANG",
    //     "MEULABOH",
    //     "KAB ACEH TENGAH",
    // ],

    // inflasi_provinsi: [
    //     2.5, 3.1, 2.8, 3.3, 2.9, 3.5, 2.7, -3.0, 3.2, 2.6, 2.9, 3.4, 3.1, 2.7,
    //     3.6, 2.8, 3.3, 3.0, 2.5, 3.2, 3.1, 2.9, 3.5, -2.8, 3.0, 2.7, 3.3, 3.1,
    //     2.6, 3.4, 2.9, 3.0, 2.5, 2.8, 3.2, 3.1, -2.7, 3.5,
    // ],

    // chartData: {
    //     labels: [
    //         "Harga Produsen",
    //         "Harga Produsen Desa",
    //         "Harga Perdagangan Besar",
    //         "Harga Konsumen Desa",
    //         "Harga Konsumen Kota",
    //     ],
    //     datasets: [
    //         {
    //             label: "Naik (↑)",
    //             data: [20, 47, 35, 20, 30],
    //             backgroundColor: "#36a2eb",
    //             stack: "stack1",
    //         },
    //         {
    //             label: "Stabil (-)",
    //             data: [35, 30, 40, 22, 30],
    //             backgroundColor: "#4bc0c0",
    //             stack: "stack1",
    //         },
    //         {
    //             label: "Menurun (↓)",
    //             data: [45, 4, 25, 50, 40],
    //             backgroundColor: "red",
    //             stack: "stack1",
    //         },
    //     ],
    // },

    // initCharts() {
    //     // Toolbox configuration (without dataZoom for now to avoid the previous error)
    //     const toolboxConfig = {
    //         show: true,
    //         feature: {
    //             saveAsImage: {
    //                 title: "Download",
    //                 name: "chart",
    //                 type: "png",
    //                 pixelRatio: 2,
    //                 backgroundColor: "#fff",
    //             },
    //             restore: {
    //                 title: "Reset",
    //             },
    //         },
    //         right: 10,
    //         top: 0,
    //     };

    //     // Multi-Axis Chart (Line Chart)
    //     const multiAxisChartElement = document.getElementById("multiAxisChart");
    //     if (!multiAxisChartElement) {
    //         console.error("MultiAxisChart element not found");
    //         return;
    //     }
    //     if (typeof echarts === "undefined") {
    //         console.error("ECharts not loaded");
    //         return;
    //     }

    //     this.multiAxisChart = echarts.init(multiAxisChartElement);
    //     const multiAxisOption = {
    //         title: { text: "Stacked Line" },
    //         tooltip: { trigger: "axis" },
    //         legend: {
    //             data: [
    //                 "Email",
    //                 "Union Ads",
    //                 "Video Ads",
    //                 "Direct",
    //                 "Search Engine",
    //             ],
    //         },
    //         grid: { left: "3%", right: "4%", bottom: "3%", containLabel: true },
    //         toolbox: { feature: { saveAsImage: {}, restore: {} } },
    //         xAxis: {
    //             type: "category",
    //             boundaryGap: false,
    //             data: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    //         },
    //         yAxis: { type: "value" },
    //         series: [
    //             {
    //                 name: "Email",
    //                 type: "line",
    //                 stack: "Total",
    //                 data: [120, 132, 101, 134, 90, 230, 210],
    //             },
    //             {
    //                 name: "Union Ads",
    //                 type: "line",
    //                 stack: "Total",
    //                 data: [220, 182, 191, 234, 290, 330, 310],
    //             },
    //             {
    //                 name: "Video Ads",
    //                 type: "line",
    //                 stack: "Total",
    //                 data: [150, 232, 201, 154, 190, 330, 410],
    //             },
    //             {
    //                 name: "Direct",
    //                 type: "line",
    //                 stack: "Total",
    //                 data: [320, 332, 301, 334, 390, 330, 320],
    //             },
    //             {
    //                 name: "Search Engine",
    //                 type: "line",
    //                 stack: "Total",
    //                 data: [820, 932, 901, 934, 1290, 1330, 1320],
    //             },
    //         ],
    //     };
    //     this.multiAxisChart.setOption(multiAxisOption);

    // Horizontal Bar Chart
    // try {
    //     this.horizontalBarChart = echarts.init(
    //         document.getElementById("horizontalBarChart")
    //     );
    //     const horizontalBarOption = {
    //         toolbox: toolboxConfig,
    //         tooltip: {
    //             trigger: "axis",
    //             axisPointer: { type: "shadow" },
    //             formatter: (params) => {
    //                 let result = `${params[0].name}<br>`;
    //                 params.forEach((param) => {
    //                     result += `${param.marker} ${param.seriesName}: ${param.value}%<br>`;
    //                 });
    //                 return result;
    //             },
    //         },
    //         legend: { bottom: 0 },
    //         grid: { left: "15%", right: "10%", bottom: "15%", top: "10%" },
    //         xAxis: { type: "value", name: "Value (%)" },
    //         yAxis: {
    //             type: "category",
    //             data: this.datasets.map((dataset) => dataset.label),
    //             name: "Price Level",
    //         },
    //         series: [
    //             {
    //                 name: "Inflasi",
    //                 type: "bar",
    //                 data: this.datasets.map(
    //                     (dataset) => dataset.inflasi[this.labels.length - 1]
    //                 ),
    //             },
    //             {
    //                 name: "Andil",
    //                 type: "bar",
    //                 data: this.datasets.map(
    //                     (dataset) => dataset.andil[this.labels.length - 1]
    //                 ),
    //             },
    //         ],
    //     };
    //     this.horizontalBarChart.setOption(horizontalBarOption);
    // } catch (err) {
    //     console.error("Error initializing horizontalBarChart:", err);
    // }

    // // Stacked Bar Chart
    // try {
    //     this.stackedBarChart = echarts.init(
    //         document.getElementById("stackedBarChart")
    //     );
    //     const stackedBarOption = {
    //         toolbox: toolboxConfig,
    //         tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
    //         legend: { bottom: 0 },
    //         grid: { left: "10%", right: "10%", bottom: "15%", top: "10%" },
    //         xAxis: { type: "category", data: this.chartData.labels },
    //         yAxis: { type: "value", max: 100 },
    //         series: this.chartData.datasets.map((dataset) => ({
    //             name: dataset.label,
    //             type: "bar",
    //             stack: dataset.stack,
    //             data: dataset.data,
    //             itemStyle: { color: dataset.backgroundColor },
    //         })),
    //     };
    //     this.stackedBarChart.setOption(stackedBarOption);
    // } catch (err) {
    //     console.error("Error initializing stackedBarChart:", err);
    // }

    // // Rank Bar Charts (Provinsi and Kabkot)
    // const provinsiInflasi = this.provinces.map(() =>
    //     (Math.random() * 10).toFixed(2)
    // );
    // const sortedData = this.provinces
    //     .map((prov, index) => ({
    //         name: prov.nama_wilayah || `Provinsi ${index + 1}`,
    //         value: parseFloat(provinsiInflasi[index]),
    //     }))
    //     .sort((a, b) => b.value - a.value);
    // const sortedProvinsi = sortedData.map((item) => item.name);
    // const sortedInflasi = sortedData.map((item) => item.value);

    // try {
    //     this.rankBarChartProvinsi = echarts.init(
    //         document.getElementById("rankBarChartProvinsi")
    //     );
    //     const rankBarOption1 = {
    //         toolbox: toolboxConfig,
    //         tooltip: { formatter: (params) => `Inflasi: ${params.value}%` },
    //         grid: { left: "15%", right: "10%", bottom: "10%", top: "10%" },
    //         xAxis: { type: "value", name: "Inflasi (%)" },
    //         yAxis: {
    //             type: "category",
    //             data: sortedProvinsi,
    //             axisLabel: { fontSize: 10 },
    //         },
    //         series: [
    //             {
    //                 name: "Inflasi",
    //                 type: "bar",
    //                 data: sortedInflasi,
    //                 itemStyle: { color: "#36a2eb" },
    //             },
    //         ],
    //     };
    //     this.rankBarChartProvinsi.setOption(rankBarOption1);
    // } catch (err) {
    //     console.error("Error initializing rankBarChartProvinsi:", err);
    // }

    // try {
    //     this.rankBarChartProvinsi2 = echarts.init(
    //         document.getElementById("rankBarChartProvinsi2")
    //     );
    //     const rankBarOption2 = {
    //         toolbox: toolboxConfig,
    //         tooltip: { formatter: (params) => `Inflasi: ${params.value}%` },
    //         grid: { left: "15%", right: "10%", bottom: "10%", top: "10%" },
    //         xAxis: { type: "value", name: "Inflasi (%)" },
    //         yAxis: {
    //             type: "category",
    //             data: sortedProvinsi,
    //             axisLabel: { fontSize: 10 },
    //         },
    //         series: [
    //             {
    //                 name: "Inflasi",
    //                 type: "bar",
    //                 data: sortedInflasi,
    //                 itemStyle: { color: "#36a2eb" },
    //             },
    //         ],
    //     };
    //     this.rankBarChartProvinsi2.setOption(rankBarOption2);
    // } catch (err) {
    //     console.error("Error initializing rankBarChartProvinsi2:", err);
    // }

    // // Small Multiples: 5 Horizontal Bar Charts for Price Levels
    // const maxInflation = Math.max(
    //     ...Object.values(this.inflationData).flatMap((prov) =>
    //         Object.values(prov).map((val) => parseFloat(val))
    //     )
    // );

    // this.priceLevels.forEach((level, index) => {
    //     const chartId = `priceLevelChart${index + 1}`;
    //     const chartElement = document.getElementById(chartId);
    //     if (!chartElement) {
    //         console.error(`Chart element for ${level} not found`);
    //         return;
    //     }

    //     try {
    //         this.priceLevelCharts[level] = echarts.init(chartElement);
    //         const option = {
    //             toolbox: toolboxConfig,
    //             tooltip: {
    //                 formatter: (params) => `Inflasi: ${params.value}%`,
    //             },
    //             grid: {
    //                 left: index === 0 ? "15%" : "5%",
    //                 right: "5%",
    //                 bottom: "10%",
    //                 top: "15%",
    //             },
    //             xAxis: {
    //                 type: "value",
    //                 name: index === 0 ? "Inflation (%)" : "",
    //                 max: Math.ceil(maxInflation),
    //                 axisLabel: { show: true },
    //             },
    //             yAxis: {
    //                 type: "category",
    //                 data: this.provinces.map(
    //                     (p) => p.nama_wilayah || `Provinsi ${p.kd_wilayah}`
    //                 ),
    //                 name: index === 0 ? "Province" : "",
    //                 axisLabel: { show: index === 0, fontSize: 10 },
    //             },
    //             series: [
    //                 {
    //                     name: level,
    //                     type: "bar",
    //                     data: this.provinces.map(
    //                         (p) =>
    //                             this.inflationData[p.kd_wilayah]?.[level] ||
    //                             0
    //                     ),
    //                     itemStyle: { color: "#36a2eb" },
    //                     barWidth: 12,
    //                 },
    //             ],
    //         };
    //         this.priceLevelCharts[level].setOption(option);
    //     } catch (err) {
    //         console.error(
    //             `Error initializing priceLevelChart for ${level}:`,
    //             err
    //         );
    //     }
    // });

    // // Heatmap
    // try {
    //     this.inflationHeatmap = echarts.init(
    //         document.getElementById("inflationHeatmap")
    //     );
    //     const provinces = this.provinces.map(
    //         (p) => p.nama_wilayah || `Provinsi ${p.kd_wilayah}`
    //     );
    //     const priceLevels = this.priceLevels;
    //     const zValues = provinces.map((prov) => {
    //         const provData =
    //             this.inflationData[
    //                 this.provinces.find((p) => p.nama_wilayah === prov)
    //                     ?.kd_wilayah
    //             ] || {};
    //         return priceLevels.map(
    //             (level) => parseFloat(provData[level]) || 0
    //         );
    //     });
    //     const heatmapData = provinces.flatMap((prov, i) =>
    //         priceLevels.map((level, j) => [j, i, zValues[i][j]])
    //     );
    //     const heatmapOption = {
    //         toolbox: toolboxConfig,
    //         tooltip: {
    //             formatter: (params) =>
    //                 `${provinces[params.value[1]]} - ${
    //                     priceLevels[params.value[0]]
    //                 }<br>Inflation: ${params.value[2]}%`,
    //         },
    //         grid: { height: "70%", width: "70%", left: "15%", top: "10%" },
    //         xAxis: {
    //             type: "category",
    //             data: priceLevels,
    //             axisLabel: { rotate: 45 },
    //         },
    //         yAxis: {
    //             type: "category",
    //             data: provinces,
    //             axisLabel: { fontSize: 10 },
    //         },
    //         visualMap: {
    //             min: 0,
    //             max: 10,
    //             calculable: true,
    //             orient: "vertical",
    //             right: 10,
    //             top: "center",
    //             inRange: { color: ["#E6F0FA", "#003087"] },
    //         },
    //         series: [
    //             {
    //                 type: "heatmap",
    //                 data: heatmapData,
    //                 label: {
    //                     show: true,
    //                     formatter: (params) =>
    //                         `${params.value[2].toFixed(2)}%`,
    //                     fontSize: 10,
    //                 },
    //                 emphasis: {
    //                     itemStyle: { borderColor: "#333", borderWidth: 1 },
    //                 },
    //             },
    //         ],
    //     };
    //     this.inflationHeatmap.setOption(heatmapOption);
    // } catch (err) {
    //     console.error("Error initializing inflationHeatmap:", err);
    // }
    // },

    // async initMaps() {
    // Define a common toolbox configuration for maps
    // const toolboxConfig = {
    //     show: true,
    //     feature: {
    //         saveAsImage: {
    //             title: "Download",
    //             name: "map", // Base name for the downloaded file
    //             type: "png",
    //             pixelRatio: 2,
    //             backgroundColor: "#fff",
    //         },
    //         restore: {
    //             title: "Reset",
    //         },
    //     },
    //     right: 10,
    //     top: 0,
    // };
    // // Load Provinsi GeoJSON
    // try {
    //     const provinsiResponse = await fetch("/geojson/Provinsi.json");
    //     const provinsiData = await provinsiResponse.json();
    //     provinsiData.features.forEach((feature) => {
    //         const regionCode = feature.properties.KODE_PROV;
    //         const inflationRate = this.mapDemo[regionCode];
    //         feature.properties.inflation_rate =
    //             inflationRate !== undefined ? inflationRate : null;
    //     });
    //     echarts.registerMap("Provinsi", provinsiData);
    //     this.map = echarts.init(document.getElementById("map"));
    //     const provinsiMapOption = {
    //         toolbox: toolboxConfig, // Add toolbox
    //         tooltip: {
    //             formatter: (params) => {
    //                 const value = params.value || "N/A";
    //                 return `${params.name}<br>Inflation Rate: ${value}%`;
    //             },
    //         },
    //         visualMap: {
    //             min: 0,
    //             max: 7,
    //             text: ["High", "Low"],
    //             calculable: true,
    //             inRange: {
    //                 color: ["#FEB24C", "#800026"],
    //             },
    //         },
    //         series: [
    //             {
    //                 type: "map",
    //                 map: "Provinsi",
    //                 label: { show: true, fontSize: 10 },
    //                 data: provinsiData.features.map((feature) => ({
    //                     name: feature.properties.PROVINSI,
    //                     value: feature.properties.inflation_rate,
    //                 })),
    //                 emphasis: {
    //                     label: { show: true, fontWeight: "bold" },
    //                     itemStyle: { areaColor: "#FF9900" },
    //                 },
    //             },
    //         ],
    //     };
    //     this.map.setOption(provinsiMapOption);
    // } catch (err) {
    //     console.error("Error loading Provinsi GeoJSON:", err);
    // }
    // // Load Kabkot GeoJSON
    // try {
    //     const kabkotResponse = await fetch("/geojson/kab_indo_dummy4.json");
    //     const kabkotData = await kabkotResponse.json();
    //     kabkotData.features.forEach((feature) => {
    //         const regionCode = feature.properties.idkab;
    //         const inflationRate = this.mapDemo2[regionCode];
    //         feature.properties.inflation_rate =
    //             inflationRate !== undefined ? inflationRate : null;
    //     });
    //     echarts.registerMap("Kabkot", kabkotData);
    //     this.map2 = echarts.init(document.getElementById("map2"));
    //     const kabkotMapOption = {
    //         toolbox: toolboxConfig, // Add toolbox
    //         tooltip: {
    //             formatter: (params) => {
    //                 const value = params.value || "N/A";
    //                 return `${params.name}<br>Inflation Rate: ${value}%`;
    //             },
    //         },
    //         visualMap: {
    //             min: 0,
    //             max: 7,
    //             text: ["High", "Low"],
    //             calculable: true,
    //             inRange: {
    //                 color: ["#FEB24C", "#800026"],
    //             },
    //         },
    //         series: [
    //             {
    //                 type: "map",
    //                 map: "Kabkot",
    //                 label: { show: true, fontSize: 8 },
    //                 data: kabkotData.features.map((feature) => ({
    //                     name: feature.properties.nmkab,
    //                     value: feature.properties.inflation_rate,
    //                 })),
    //                 emphasis: {
    //                     label: { show: true, fontWeight: "bold" },
    //                     itemStyle: { areaColor: "#FF9900" },
    //                 },
    //             },
    //         ],
    //     };
    //     this.map2.setOption(kabkotMapOption);
    // } catch (err) {
    //     console.error("Error loading Kabkot GeoJSON:", err);
    // }
    // },

    // Chart control methods
    showInflasiLine() {
        const option = this.multiAxisChart.getOption();
        option.series = this.datasets.map((dataset) => ({
            name: dataset.label,
            type: "line",
            data: dataset.inflasi,
        }));
        this.multiAxisChart.setOption(option);
    },

    showAndilLine() {
        const option = this.multiAxisChart.getOption();
        option.series = this.datasets.map((dataset) => ({
            name: dataset.label,
            type: "line",
            data: dataset.andil,
        }));
        this.multiAxisChart.setOption(option);
    },

    toggleFullscreen(chartId) {
        const chartElement = document.getElementById(chartId);
        const fullscreenIcon = document.getElementById(
            `fullscreenIcon${
                chartId.charAt(0).toUpperCase() + chartId.slice(1)
            }`
        );
        const container = chartElement.parentElement;

        if (!document.fullscreenElement) {
            container.requestFullscreen().catch((err) => {
                console.log(
                    `Error trying to enable fullscreen: ${err.message}`
                );
            });
            chartElement.style.height = "100vh";
            fullscreenIcon.textContent = "close_fullscreen";
        } else {
            document.exitFullscreen();
            chartElement.style.height =
                chartId.includes("map") || chartId.includes("priceLevelChart")
                    ? "1000px"
                    : "384px"; // 384px = 96 * 4 (equivalent to max-h-96)
            fullscreenIcon.textContent = "fullscreen";
        }

        // Resize the chart to fit the new dimensions
        setTimeout(() => {
            if (chartId === "multiAxisChart") this.multiAxisChart.resize();
            else if (chartId === "horizontalBarChart")
                this.horizontalBarChart.resize();
            else if (chartId === "stackedBarChart")
                this.stackedBarChart.resize();
            else if (chartId === "rankBarChartProvinsi")
                this.rankBarChartProvinsi.resize();
            else if (chartId === "rankBarChartProvinsi2")
                this.rankBarChartProvinsi2.resize();
            else if (chartId === "inflationHeatmap")
                this.inflationHeatmap.resize();
            else if (chartId === "map") this.map.resize();
            else if (chartId === "map2") this.map2.resize();
            else if (chartId.includes("priceLevelChart")) {
                const level =
                    this.priceLevels[
                        parseInt(chartId.replace("priceLevelChart", "")) - 1
                    ];
                this.priceLevelCharts[level].resize();
            }
        }, 100);
    },

    // Existing methods (unchanged)
    selectKomoditas(event) {
        this.selectedKomoditas = event.target.value;
    },

    get filteredKabkots() {
        if (!this.selectedProvince.kd_wilayah) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd == this.selectedProvince.kd_wilayah
        );
    },

    selectProvince(province) {
        this.selectedProvince = province;
        this.selectedKabkot = "";
        this.updateKdWilayah();
    },

    updateKdWilayah() {
        if (this.isPusat) {
            this.kd_wilayah = "0";
        } else if (this.selectedKabkot) {
            this.kd_wilayah = this.selectedKabkot;
        } else if (this.selectedProvince.kd_wilayah) {
            this.kd_wilayah = this.selectedProvince.kd_wilayah;
        } else {
            this.kd_wilayah = "";
        }
    },

    togglePusat() {
        this.updateKdWilayah();
    },

    modalOpen: false,
    item: { id: null, komoditas: "Example Komoditas", harga: "1000" },

    openModal(id, komoditas, harga, wilayah, levelHarga, periode) {
        this.item = { id, komoditas, harga, wilayah, levelHarga, periode };
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
}));

Alpine.start();

//         kabkotChoropleth: async () => {
//             charts.choropleth.showLoading();
//             try {
//                 // Fetch the GeoJSON data
//                 const kabkotResponse = await fetch(
//                     "/geojson/kab_indo_dummy4.json"
//                 );
//                 if (!kabkotResponse.ok) {
//                     throw new Error(
//                         `Failed to fetch GeoJSON: ${kabkotResponse.status}`
//                     );
//                 }
//                 const kabkotData = await kabkotResponse.json();
//                 console.log("GeoJSON Loaded:", kabkotData);

//                 // Check GeoJSON structure
//                 if (
//                     !kabkotData.features ||
//                     !Array.isArray(kabkotData.features)
//                 ) {
//                     throw new Error(
//                         "GeoJSON does not contain 'features' array"
//                     );
//                 }
//                 console.log("Number of Features:", kabkotData.features.length);

//                 // Log sample feature properties
//                 console.log(
//                     "Sample Feature Properties:",
//                     kabkotData.features[0].properties
//                 );

//                 // Assign random values and map data using 'nmkab'
//                 const mapData = kabkotData.features.map((feature) => {
//                     const name = feature.properties.nmkab; // Use 'nmkab' from your GeoJSON
//                     if (!name) {
//                         console.warn(`Feature missing 'nmkab':`, feature);
//                     }
//                     const value = Math.floor(Math.random() * 15) - 7;
//                     return { name, value };
//                 });
//                 console.log(
//                     "Mapped Data (first 5 entries):",
//                     mapData.slice(0, 5)
//                 );

//                 // Register the map
//                 echarts.registerMap("Kabkot_Indonesia", kabkotData);
//                 console.log("Map Registered: Kabkot_Indonesia");

//                 // Define chart options
//                 const option = {
//                     title: {
//                         text: "Indonesia Kabupaten Data (Random Values)",
//                         subtext: "Generated Random Data",
//                         left: "right",
//                     },
//                     tooltip: {
//                         trigger: "item",
//                         showDelay: 0,
//                         transitionDuration: 0.2,
//                         formatter: (params) =>
//                             `${params.name}: ${
//                                 params.value !== undefined
//                                     ? params.value
//                                     : "No Data"
//                             }`,
//                     },
//                     visualMap: {
//                         left: "right",
//                         min: -7,
//                         max: 7,
//                         inRange: {
//                             color: [
//                                 "#313695",
//                                 "#4575b4",
//                                 "#74add1",
//                                 "#abd9e9",
//                                 "#e0f3f8",
//                                 "#ffffbf",
//                                 "#fee090",
//                                 "#fdae61",
//                                 "#f46d43",
//                                 "#d73027",
//                                 "#a50026",
//                             ],
//                         },
//                         text: ["High", "Low"],
//                         calculable: true,
//                     },
//                     toolbox: {
//                         show: true,
//                         left: "left",
//                         top: "top",
//                         feature: {
//                             dataView: { readOnly: false },
//                             restore: {},
//                             saveAsImage: {},
//                         },
//                     },
//                     series: [
//                         {
//                             name: "Kabupaten Data",
//                             type: "map",
//                             map: "Kabkot_Indonesia",
//                             emphasis: { label: { show: true } },
//                             data: mapData,
//                             nameProperty: "nmkab", // Tell ECharts to use 'nmkab' as the name field in GeoJSON
//                         },
//                     ],
//                 };

//                 // Apply options and hide loading
//                 console.log(
//                     "Applying Chart Options with Data Length:",
//                     mapData.length
//                 );
//                 charts.choropleth.setOption(option, true); // Force re-render
//                 charts.choropleth.hideLoading();
//             } catch (error) {
//                 console.error("Error in Kabkot Choropleth:", error);
//                 charts.choropleth.hideLoading();
//             }
//         },
//     };

//     // Apply chart options
//     charts.stackedLine.setOption(chartOptions.stackedLine);
//     charts.heatMap.setOption(chartOptions.heatMap);
//     charts.verticalBar.setOption(chartOptions.verticalBar);
//     charts.stackedBar.setOption(chartOptions.stackedBar);
//     chartOptions.kabkotChoropleth();
//     // Handle window resize for all charts
//     window.addEventListener("resize", () => {
//         Object.values(charts).forEach((chart) => chart.resize());
//     });
// });

document.addEventListener("DOMContentLoaded", async () => {
    // DOM Elements
    const stackedLineChartElement = document.getElementById("stackedLineChart");
    const horizontalBarChartElement =
        document.getElementById("horizontalBarChart");
    const heatmapChartElement = document.getElementById("heatmapChart");
    const barChartsContainer = document.getElementById("barChartsContainer");
    const stackedBarChartElement = document.getElementById("stackedBarChart");
    const provHorizontalBarChartElement = document.getElementById(
        "provHorizontalBarChart"
    );
    const kabkotHorizontalBarChartElement = document.getElementById(
        "kabkotHorizontalBarChart"
    );
    const provinsiChoroplethElement =
        document.getElementById("provinsiChoropleth");
    const kabkotChoroplethElement = document.getElementById("kabkotChoropleth");
    const levelSelect = document.getElementById("levelHargaSelect");
    // Validate DOM elements (unchanged)
    if (
        !stackedLineChartElement ||
        !horizontalBarChartElement ||
        !heatmapChartElement ||
        !barChartsContainer ||
        !stackedBarChartElement ||
        !provHorizontalBarChartElement ||
        !kabkotHorizontalBarChartElement ||
        !provinsiChoroplethElement ||
        !kabkotChoroplethElement
    ) {
        console.error("One or more required DOM elements not found");
        return;
    }

    // Initialize ECharts instances
    let stackedLineChart,
        horizontalBarChart,
        heatmapChart,
        barChartInstance,
        stackedBarChart,
        provHorizontalBarChart,
        kabkotHorizontalBarChart,
        provinsiChoropleth,
        kabkotChoropleth,
        provinsiGeoJson,
        kabkotGeoJson;

    try {
        stackedLineChart = echarts.init(stackedLineChartElement);
        horizontalBarChart = echarts.init(horizontalBarChartElement);
        heatmapChart = echarts.init(heatmapChartElement);
        barChartInstance = echarts.init(barChartsContainer); // Assign directly to outer scope
        stackedBarChart = echarts.init(stackedBarChartElement);
        provHorizontalBarChart = echarts.init(provHorizontalBarChartElement);
        kabkotHorizontalBarChart = echarts.init(
            kabkotHorizontalBarChartElement
        );
        provinsiChoropleth = echarts.init(provinsiChoroplethElement);
        kabkotChoropleth = echarts.init(kabkotChoroplethElement);
    } catch (error) {
        console.error("Failed to initialize ECharts instances:", error);
        return;
    }

    // Array of bar chart instances
    // const chartsBar = [barChart1, barChart2, barChart3, barChart4, barChart5];

    // Default data (fallback if no backend data)
    const defaultStackedLineData = {
        series: [
            { name: "Email", data: [120, 132, 101, 134, 90, 230, 210] },
            { name: "Union Ads", data: [220, 182, 191, 234, 290, 330, 310] },
            { name: "Video Ads", data: [150, 232, 201, 154, 190, 330, 410] },
        ],
        xAxis: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
    };

    // Default data
    const defaultProvHorizontalBarData = [];
    const defaultKabkotHorizontalBarData = [];

    const defaultHorizontalBarData = {
        labels: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"],
        datasets: [
            {
                label: "Email",
                inflasi: [120, 132, 101, 134, 90, 230, 210],
                andil: [10, 12, 11, 13, 9, 23, 21],
            },
            {
                label: "Union Ads",
                inflasi: [220, 182, 191, 234, 290, 330, 310],
                andil: [22, 18, 19, 23, 29, 33, 31],
            },
            {
                label: "Video Ads",
                inflasi: [150, 232, 201, 154, 190, 330, 410],
                andil: [15, 23, 20, 15, 19, 33, 41],
            },
        ],
    };

    const defaultHeatmapData = {
        xAxis: ["01", "02", "03", "04", "05"],
        yAxis: ["Province A", "Province B", "Province C"],
        values: [
            [0, 0, 10],
            [1, 0, 15],
            [2, 0, 20],
            [3, 0, 25],
            [4, 0, 30],
            [0, 1, 5],
            [1, 1, 10],
            [2, 1, 15],
            [3, 1, 20],
            [4, 1, 25],
            [0, 2, 8],
            [1, 2, 12],
            [2, 2, 18],
            [3, 2, 22],
            [4, 2, 28],
        ],
    };

    const defaultBarChartData = [
        {
            name: "Level 01",
            provinces: ["Province A", "Province B"],
            values: [1.2, 1.5],
        },
        {
            name: "Level 02",
            provinces: ["Province A", "Province C"],
            values: [1.8, 2.0],
        },
        {
            name: "Level 03",
            provinces: ["Province B", "Province C"],
            values: [2.2, 2.5],
        },
        { name: "Level 04", provinces: ["Province A"], values: [1.0] },
        {
            name: "Level 05",
            provinces: ["Province B", "Province C"],
            values: [1.7, 1.9],
        },
    ];

    // Use backend data if available, otherwise fallback to default
    const stackedLineData = window.stackedLineData || defaultStackedLineData;
    const horizontalBarData =
        window.horizontalBarData || defaultHorizontalBarData;
    const heatmapData = window.heatmapData || defaultHeatmapData;
    const barChartData = window.barChartsData || defaultBarChartData;
    const stackedBarData = window.stackedBarData || defaultStackedBarData;
    const provHorizontalBarData =
        window.provHorizontalBarData || defaultProvHorizontalBarData;
    const kabkotHorizontalBarData =
        window.kabkotHorizontalBarData || defaultKabkotHorizontalBarData;

    // Load GeoJSON data
    try {
        const provResponse = await fetch("/geojson/Provinsi.json");
        const kabkotResponse = await fetch("/geojson/kab_indo_dummy4.json");
        console.log("Provinsi Response Status:", provResponse.status);
        console.log("Kabkot Response Status:", kabkotResponse.status);
        if (!provResponse.ok)
            throw new Error(
                `Failed to fetch Provinsi GeoJSON: ${provResponse.status}`
            );
        if (!kabkotResponse.ok)
            throw new Error(
                `Failed to fetch Kabkot GeoJSON: ${kabkotResponse.status}`
            );
        provinsiGeoJson = await provResponse.json();
        kabkotGeoJson = await kabkotResponse.json();
        console.log("Provinsi GeoJSON:", provinsiGeoJson);
        console.log("Kabkot GeoJSON:", kabkotGeoJson);
        echarts.registerMap("Provinsi_Indonesia", provinsiGeoJson);
        echarts.registerMap("Kabkot_Indonesia", kabkotGeoJson);
        console.log("GeoJSON Loaded and Maps Registered");
    } catch (error) {
        console.error("Error loading GeoJSON:", error);
        return;
    }

    // Stacked Line Chart Configuration (Inflasi)
    const stackedLineOptions = {
        // title: { text: window.chartTitle || "Inflasi" },
        tooltip: { trigger: "axis" },
        legend: { data: stackedLineData.series.map((s) => s.name) },
        grid: { left: "3%", right: "4%", bottom: "3%", containLabel: true },
        toolbox: { feature: { saveAsImage: { title: "Save as PNG" } } },
        xAxis: {
            type: "category",
            boundaryGap: false,
            data: stackedLineData.xAxis,
        },
        yAxis: { type: "value", name: "Inflasi (%)" },
        series: stackedLineData.series.map((series) => ({
            ...series,
            type: "line",
            stack: "Total",
            areaStyle: {},
        })),
    };

    // Horizontal Bar Chart Configuration (Inflasi and Andil)
    const horizontalBarOptions = {
        title: { text: "Inflasi dan Andil (Latest Period)" },
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" },
            formatter: (params) => {
                let result = `${params[0].name}<br>`;
                params.forEach((param) => {
                    result += `${param.marker} ${param.seriesName}: ${param.value}%<br>`;
                });
                return result;
            },
        },
        legend: { bottom: 0, data: ["Inflasi", "Andil"] },
        grid: { left: "15%", right: "10%", bottom: "15%", top: "10%" },
        xAxis: { type: "value", name: "Value (%)" },
        yAxis: {
            type: "category",
            data: horizontalBarData.datasets.map((dataset) => dataset.label),
            name: "Price Level",
        },
        series: [
            {
                name: "Inflasi",
                type: "bar",
                data: horizontalBarData.datasets.map(
                    (dataset) => dataset.inflasi[dataset.inflasi.length - 1]
                ),
                itemStyle: { color: "#5470C6" },
            },
            {
                name: "Andil",
                type: "bar",
                data: horizontalBarData.datasets.map(
                    (dataset) => dataset.andil[dataset.andil.length - 1]
                ),
                itemStyle: { color: "#91CC75" },
            },
        ],
    };

    // Heatmap Chart Configuration (Inflasi by Province)
    const heatmapOptions = {
        tooltip: { position: "top" },
        grid: { height: "80%", top: "10%" },
        xAxis: {
            type: "category",
            data: heatmapData.xAxis,
            splitArea: { show: true },
        },
        yAxis: {
            type: "category",
            data: heatmapData.yAxis,
            splitArea: { show: true },
        },
        visualMap: {
            min: -2,
            max: 3,
            calculable: true,
            orient: "horizontal",
            left: "center",
            bottom: "15%",
        },
        series: [
            {
                name: "Inflasi",
                type: "heatmap",
                data: heatmapData.values,
                label: { show: true },
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowColor: "rgba(0, 0, 0, 0.5)",
                    },
                },
            },
        ],
    };

    // Bar Chart Options
    // Grid setup for 1 row, 5 columns
    const grids = [];
    const xAxes = [];
    const yAxes = [];
    const series = [];
    const titles = [];
    const columnCount = 5;

    barChartData.forEach((data, idx) => {
        grids.push({
            show: true,
            borderWidth: 0,
            left: `${(idx / columnCount) * 100 + 2}%`, // 2% padding
            top: "10%",
            width: `${(1 / columnCount) * 100 - 4}%`, // 4% total padding
            height: "70%",
        });
        xAxes.push({
            type: "value",
            name: "Inflation (%)",
            gridIndex: idx,
            min: 0,
            max: Math.max(...data.values) * 1.2, // Dynamic max
        });
        yAxes.push({
            type: "category",
            data: data.provinces,
            gridIndex: idx,
            axisLabel: {
                show: idx === 0, // Show labels only on first and last
                interval: 0,
                rotate: 45,
            },
        });
        series.push({
            name: data.name,
            type: "bar",
            xAxisIndex: idx,
            yAxisIndex: idx,
            data: data.values,
            itemStyle: { color: "#73C0DE" },
        });
        titles.push({
            text: data.name,
            textAlign: "center",
            left: `${(idx / columnCount) * 100 + (1 / columnCount) * 50}%`,
            top: "2%",
            textStyle: { fontSize: 12, fontWeight: "normal" },
        });
    });

    const barChartOptions = {
        title: titles,
        grid: grids,
        xAxis: xAxes,
        yAxis: yAxes,
        series: series,
        tooltip: { trigger: "axis" },
    };

    // Stacked Bar Chart Configuration
    const stackedBarOptions = {
        title: { text: "Inflation Categories by Level (Latest Month)" },
        tooltip: {
            trigger: "axis",
            axisPointer: { type: "shadow" },
            formatter: (params) => {
                let total = 0;
                let result = `${params[0].axisValueLabel}<br>`;
                params.forEach((param) => {
                    total += param.value;
                    result += `${param.marker} ${param.seriesName}: ${param.value}<br>`;
                });
                result += `<strong>Total: ${total}</strong>`;
                return result;
            },
        },
        legend: { bottom: 0 },
        grid: { left: "10%", right: "10%", bottom: "15%", top: "10%" },
        xAxis: { type: "category", data: stackedBarData.labels },
        yAxis: { type: "value", name: "Province Count" },
        series: stackedBarData.datasets.map((dataset) => ({
            name: dataset.label,
            type: "bar",
            stack: dataset.stack,
            data: dataset.data,
            itemStyle: { color: dataset.backgroundColor },
        })),
    };

    // Horizontal Bar Chart Options
    const horizontalBarOptionsWilayah = (data, title) => ({
        title: { text: title },
        tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
        grid: { left: "15%", right: "10%", bottom: "10%", top: "10%" },
        xAxis: { type: "value", name: "Inflasi (%)" },
        yAxis: { type: "category", data: data.names },
        series: [
            {
                name: "Inflasi",
                type: "bar",
                data: data.inflasi,
                itemStyle: { color: "#5470C6" },
            },
        ],
    });

    // Choropleth Options
    const choroplethOptions = (mapName, data, title) => ({
        title: { text: title, left: "center" },
        tooltip: {
            trigger: "item",
            formatter: (params) =>
                `${params.name}: ${
                    params.value !== undefined ? params.value + "%" : "No Data"
                }`,
        },
        visualMap: {
            left: "right",
            min: -7,
            max: 7,
            inRange: {
                color: [
                    "#313695",
                    "#4575b4",
                    "#74add1",
                    "#abd9e9",
                    "#e0f3f8",
                    "#ffffbf",
                    "#fee090",
                    "#fdae61",
                    "#f46d43",
                    "#d73027",
                    "#a50026",
                ],
            },
            text: ["High", "Low"],
            calculable: true,
        },
        series: [
            {
                name: "Inflasi",
                type: "map",
                map: mapName,
                emphasis: { label: { show: true } },
                data: data,
                nameProperty:
                    mapName === "Provinsi_Indonesia" ? "KODE_PROV" : "idkab",
            },
        ],
    });

    // Prepare choropleth data with mapName parameter
    function prepareChoroplethData(geoJson, data, mapName) {
        return geoJson.features.map((feature) => {
            const regionCode =
                mapName === "Provinsi_Indonesia"
                    ? feature.properties.KODE_PROV
                    : feature.properties.idkab;
            const index = data.regions.findIndex((code) => code === regionCode);
            return {
                name: regionCode,
                value: index !== -1 ? data.inflasi[index] : null,
            };
        });
    }

    // Function to update main charts
    function updateCharts(
        newStackedLineData,
        newHorizontalBarData,
        newHeatmapData
    ) {
        const updatedStackedLine = newStackedLineData || stackedLineData;
        const updatedHorizontalBar = newHorizontalBarData || horizontalBarData;
        const updatedHeatmap = newHeatmapData || heatmapData;

        if (updatedStackedLine.series && updatedStackedLine.xAxis) {
            stackedLineOptions.legend.data = updatedStackedLine.series.map(
                (s) => s.name
            );
            stackedLineOptions.xAxis.data = updatedStackedLine.xAxis;
            stackedLineOptions.series = updatedStackedLine.series.map(
                (series) => ({
                    ...series,
                    type: "line",
                    stack: "Total",
                    areaStyle: {},
                })
            );
            stackedLineChart.setOption(stackedLineOptions, true);
        }

        if (updatedHorizontalBar.datasets && updatedHorizontalBar.labels) {
            horizontalBarOptions.yAxis.data = updatedHorizontalBar.datasets.map(
                (dataset) => dataset.label
            );
            horizontalBarOptions.series[0].data =
                updatedHorizontalBar.datasets.map(
                    (dataset) => dataset.inflasi[dataset.inflasi.length - 1]
                );
            horizontalBarOptions.series[1].data =
                updatedHorizontalBar.datasets.map(
                    (dataset) => dataset.andil[dataset.andil.length - 1]
                );
            horizontalBarChart.setOption(horizontalBarOptions, true);
        }

        if (updatedHeatmap.xAxis && updatedHeatmap.values) {
            heatmapOptions.xAxis.data = updatedHeatmap.xAxis;
            heatmapOptions.yAxis.data = updatedHeatmap.yAxis;
            heatmapOptions.series[0].data = updatedHeatmap.values.map(
                (item) => [item[0], item[1], item[2] || "-"]
            );
            heatmapChart.setOption(heatmapOptions, true);
        }
    }

    // Function to update bar charts
    // Function to update bar charts
    window.updateBarCharts = function (newBarChartData) {
        const updatedData = newBarChartData || barChartData;
        updatedData.forEach((data, idx) => {
            xAxes[idx].max = Math.max(...data.values) * 1.2;
            yAxes[idx].data = data.provinces;
            series[idx].data = data.values;
            titles[idx].text = data.name;
        });
        barChartInstance.setOption({
            title: titles,
            xAxis: xAxes,
            yAxis: yAxes,
            series: series,
        });
    };

    // Function to update stacked bar chart
    function updateStackedBarChart(newStackedBarData) {
        const updatedStackedBar = newStackedBarData || stackedBarData;
        if (updatedStackedBar.labels && updatedStackedBar.datasets) {
            stackedBarOptions.xAxis.data = updatedStackedBar.labels;
            stackedBarOptions.series = updatedStackedBar.datasets.map(
                (dataset) => ({
                    name: dataset.label,
                    type: "bar",
                    stack: dataset.stack,
                    data: dataset.data,
                    itemStyle: { color: dataset.backgroundColor },
                })
            );
            stackedBarChart.setOption(stackedBarOptions, true);
        } else {
            console.warn("Invalid stacked bar data provided.");
        }
    }

    function updateSelectCharts(levelIndex) {
        const provData = window.provHorizontalBarData?.[levelIndex] ?? {
            regions: [],
            names: [],
            inflasi: [],
        };
        const kabkotData = window.kabkotHorizontalBarData?.[levelIndex] ?? {
            regions: [],
            names: [],
            inflasi: [],
        };

        // Update bar charts with names
        provHorizontalBarChart.setOption(
            horizontalBarOptionsWilayah(provData, "Inflasi by Province"),
            true
        );
        kabkotHorizontalBarChart.setOption(
            horizontalBarOptionsWilayah(
                kabkotData,
                "Inflasi by Kabupaten/Kota"
            ),
            true
        );

        // Update choropleth maps with codes
        const provChoroData = prepareChoroplethData(
            provinsiGeoJson,
            provData,
            "Provinsi_Indonesia"
        ); // Fixed mapName
        const kabkotChoroData = prepareChoroplethData(
            kabkotGeoJson,
            kabkotData,
            "Kabkot_Indonesia"
        ); // Fixed mapName

        provinsiChoropleth.setOption(
            choroplethOptions(
                "Provinsi_Indonesia",
                provChoroData,
                "Inflasi by Provinsi"
            ),
            true
        );
        kabkotChoropleth.setOption(
            choroplethOptions(
                "Kabkot_Indonesia",
                kabkotChoroData,
                "Inflasi by Kabupaten/Kota"
            ),
            true
        );
    }

    // Initial render (default to first level)
    updateSelectCharts(0);

    // Handle select change
    levelSelect.addEventListener("change", () => {
        console.log("select change");
        const levelMap = {
            "Harga Konsumen Kota": 0, // 01
            "Harga Konsumen Desa": 1, // 02
            "Harga Perdagangan Besar": 2, // 03
            "Harga Produsen Desa": 3, // 04
            "Harga Produsen": 4, // 05
        };
        const selectedLevel = levelMap[levelSelect.value] || 0;
        updateSelectCharts(selectedLevel); // Fixed function name
    });

    // Initial chart rendering
    try {
        if (stackedLineData.series && stackedLineData.xAxis) {
            stackedLineChart.setOption(stackedLineOptions);
        } else {
            console.warn("No valid stacked line data provided.");
        }

        if (horizontalBarData.datasets && horizontalBarData.labels) {
            horizontalBarChart.setOption(horizontalBarOptions);
        } else {
            console.warn("No valid horizontal bar data provided.");
        }

        if (heatmapData.values) {
            heatmapChart.setOption(heatmapOptions);
        } else {
            console.warn("No valid heatmap data provided.");
        }

        barChartInstance.setOption(barChartOptions);

        if (stackedBarData.labels && stackedBarData.datasets) {
            stackedBarChart.setOption(stackedBarOptions);
        } else {
            console.warn("No valid stacked bar data provided.");
        }
    } catch (error) {
        console.error("Error rendering charts:", error);
    }

    // Handle window resize
    window.addEventListener("resize", () => {
        stackedLineChart.resize();
        horizontalBarChart.resize();
        heatmapChart.resize();
        // chartsBar.forEach((chart) => chart.resize());
        stackedBarChart.resize();
        provHorizontalBarChart.resize();
        kabkotHorizontalBarChart.resize();
        provHorizontalBarChart.resize();
        kabkotHorizontalBarChart.resize();
        provinsiChoropleth.resize();
        kabkotChoropleth.resize();

        barChartInstance.resize();
    });

    // Expose update functions globally
    window.updateCharts = updateCharts;
    window.updateBarCharts = updateBarCharts;
    window.updateSelectCharts = updateSelectCharts;

    // Log initial data
    console.log("Initial Stacked Line Data:", stackedLineData);
    console.log("Initial Horizontal Bar Data:", horizontalBarData);
    console.log("Initial Heatmap Data:", heatmapData);
    console.log("Initial Bar Chart Data:", provHorizontalBarData);
});
