// document.addEventListener('alpine:init', () => {
Alpine.data("webData", () => ({
    bulan: "",
    tahun: "",
    activeBulan: "", // Store the active bulan
    activeTahun: "", // Store the active tahun
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

    multiAxisChart: null, // Store chart instances
    horizontalBarChart: null,
    stackedBarChart: null,
    rankBarChartProvinsi: null,
    rankBarChartProvinsi2: null,
    map: null, // Store Leaflet map instances
    map2: null,
    priceLevelCharts: {},

    // Static map data (could be fetched dynamically)
    mapDemo: {
        97: 3.07,
        96: 5.61,
        95: 5.79,
        94: 6.79,
        92: 0.55,
        91: 4.71,
        82: 3.15,
        81: 3.64,
        76: 2.61,
        75: 3.79,
        74: 3.65,
        73: 4.96,
        72: 2.74,
        71: 2.66,
        65: 3.1,
        64: 6.16,
        63: 4.57,
        62: 0.09,
        61: 0.98,
        53: 2.69,
        52: 3.8,
        51: 4.16,
        36: 4.96,
        35: 1.1,
        34: 0.7,
        33: 0.12,
        32: 2.39,
        31: 2.98,
        21: 1.49,
        19: 0.96,
        18: 1.56,
        17: 4.63,
        16: 6.44,
        15: 5.46,
        14: 1.41,
        13: 5.59,
        12: 4.54,
        11: 2.37,
    },
    mapDemo2: {
        9702: 5.0,
        9604: 4.62,
        9601: 2.4,
        9501: 5.71,
        9471: 6.08,
        9271: 4.82,
        9203: 1.82,
        9202: 5.52,
        9105: 2.12,
        8271: 0.51,
        8202: 5.26,
        8172: 1.44,
        8171: 1.42,
        8103: 5.62,
        7604: 1.9,
        7472: 5.67,
        7571: 4.48,
        7502: 1.57,
        7471: 0.59,
        7404: 1.21,
        7403: 6.93,
        7373: 5.23,
        7372: 1.13,
        7371: 5.71,
        7325: 1.92,
        7314: 4.94,
        7313: 2.78,
        7311: 4.85,
        7302: 0.3,
        7271: 5.29,
        7206: 2.35,
        7203: 3.31,
        7202: 4.18,
        7174: 1.84,
        7171: 2.05,
        7106: 2.25,
        7105: 1.85,
        6571: 1.91,
        6504: 5.36,
        6502: 6.15,
        6472: 4.63,
        6471: 0.19,
        6409: 4.24,
        6405: 2.33,
        6371: 0.16,
        6309: 5.68,
        6307: 4.73,
        6302: 4.64,
        6301: 1.82,
        6271: 1.62,
        6206: 4.67,
        6203: 2.02,
        6202: 5.63,
        6172: 5.54,
        6171: 3.4,
        6111: 0.08,
        6107: 6.2,
        6106: 5.37,
        5371: 3.66,
        5312: 3.49,
        5310: 0.64,
        5304: 4.24,
        5302: 4.21,
        5272: 1.04,
        5271: 2.39,
        5204: 0.18,
        5171: 4.57,
        5108: 6.76,
        5103: 4.22,
        5102: 0.23,
        3673: 6.56,
        3672: 6.58,
        3671: 3.64,
        3602: 5.22,
        3601: 5.3,
        3578: 2.41,
        3577: 6.05,
        3574: 3.72,
        3573: 1.53,
        3571: 3.23,
        3529: 1.21,
        3525: 5.96,
        3522: 0.21,
        3510: 5.23,
        3509: 0.68,
        3504: 4.4,
        3471: 4.03,
        3403: 4.91,
        3376: 6.4,
        3374: 4.6,
        3372: 5.71,
        3319: 0.19,
        3317: 1.32,
        3312: 3.54,
        3307: 2.05,
        3302: 6.28,
        3301: 6.45,
        3278: 6.61,
        3276: 5.76,
        3275: 1.48,
        3274: 4.86,
        3273: 4.63,
        3272: 4.14,
        3271: 0.9,
        3213: 2.29,
        3210: 2.1,
        3204: 4.01,
        3100: 6.33,
        2172: 3.05,
        2171: 0.21,
        2101: 1.77,
        1971: 1.96,
        1906: 4.81,
        1903: 4.04,
        1902: 1.65,
        1872: 6.87,
        1871: 1.35,
        1811: 0.46,
        1804: 1.69,
        1771: 6.35,
        1706: 6.58,
        1674: 3.27,
        1671: 3.37,
        1603: 5.17,
        1602: 5.06,
        1571: 5.99,
        1509: 4.51,
        1501: 4.18,
        1473: 2.34,
        1471: 1.17,
        1406: 1.97,
        1403: 6.35,
        1375: 1.97,
        1371: 0.04,
        1312: 2.28,
        1311: 0.98,
        1278: 4.71,
        1277: 3.66,
        1275: 1.13,
        1273: 5.98,
        1271: 2.46,
        1212: 4.98,
        1211: 4.76,
        1207: 5.18,
        1174: 3.28,
        1171: 1.5,
        1114: 4.49,
        1107: 1.51,
        1106: 0.19,
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
            this.initCharts();
            this.initMaps();
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false; // Turn off loading after initialization
        }
    },

    labels: [
        "November 2024",
        "December 2024",
        "January 2025",
        "February 2025",
        "March 2025",
    ],

    datasets: [
        {
            label: "Harga Produsen",
            inflasi: [5.5, 3.0, 2.8, 3.2, -3.1], // Inflasi values
            andil: [0.5, 0.6, 0.55, 0.65, 0.6], // Corresponding andil values
            stacked: [20, 30, 50],
        },
        {
            label: "Harga Produsen Desa",
            inflasi: [3.8, 2.1, 2.0, 2.3, 2.2],
            andil: [0.4, 0.45, 0.43, 0.47, 0.44],
            stacked: [20, 30, 50],
        },
        {
            label: "Harga Perdagangan Besar",
            inflasi: [2.5, -3.0, 2.8, -3.2, -3.1], // Inflasi values
            andil: [0.5, 0.8, 0.34, 0.15, 0.6], // Corresponding andil values
            stacked: [20, 30, 50],
        },
        {
            label: "Harga Konsumen Desa",
            inflasi: [-1.8, 3.1, 4.0, 2.9, 7.2],
            andil: [0.4, 0.3, 0.68, 0.25, 0.43],
            stacked: [20, 30, 50],
        },
        {
            label: "Harga Konsumen Kota",
            inflasi: [2.5, 3.0, 7.0, 3.2, 3.1], // Inflasi values
            andil: [0.22, 0.52, 0.32, 0.65, 0.6], // Corresponding andil values
            stacked: [20, 30, 50],
        },
        // Add more datasets for other price levels as needed
    ],

    // Small Multiples data
    priceLevels: [
        "Harga Produsen",
        "Harga Produsen Desa",
        "Harga Perdagangan Besar",
        "Harga Konsumen Desa",
        "Harga Konsumen Kota",
    ],
    // Dummy inflation data for Small Multiples (replace with API data)
    inflationData: {}, // Will be populated in init()

    provinsi: [
        "ACEH",
        "SUMATERA UTARA",
        "SUMATERA BARAT",
        "RIAU",
        "JAMBI",
        "SUMATERA SELATAN",
        "BENGKULU",
        "LAMPUNG",
        "KEPULAUAN BANGKA BELITUNG",
        "KEPULAUAN RIAU",
        "DKI JAKARTA",
        "JAWA BARAT",
        "JAWA TENGAH",
        "DI YOGYAKARTA",
        "JAWA TIMUR",
        "BANTEN",
        "BALI",
        "NUSA TENGGARA BARAT",
        "NUSA TENGGARA TIMUR",
        "KALIMANTAN BARAT",
        "KALIMANTAN TENGAH",
        "KALIMANTAN SELATAN",
        "KALIMANTAN TIMUR",
        "KALIMANTAN UTARA",
        "SULAWESI UTARA",
        "SULAWESI TENGAH",
        "SULAWESI SELATAN",
        "SULAWESI TENGGARA",
        "GORONTALO",
        "SULAWESI BARAT",
        "MALUKU",
        "MALUKU UTARA",
        "PAPUA BARAT",
        "PAPUA BARAT DAYA",
        "PAPUA",
        "PAPUA SELATAN",
        "PAPUA TENGAH",
        "PAPUA PEGUNUNGAN",
    ],

    kota: [
        "KAB JAYAWIJAYA",
        "KAB NABIRE",
        "TIMIKA",
        "MERAUKE",
        "KOTA JAYAPURA",
        "KOTA SORONG",
        "KAB SORONG SELATAN",
        "KAB SORONG",
        "MANOKWARI",
        "KOTA TERNATE",
        "KAB HALMAHERA TENGAH",
        "KOTA TUAL",
        "KOTA AMBON",
        "KAB MALUKU TENGAH",
        "MAMUJU",
        "KAB MAJENE",
        "KOTA GORONTALO",
        "KAB GORONTALO",
        "KOTA BAU BAU",
        "KOTA KENDARI",
        "KAB KOLAKA",
        "KAB KONAWE",
        "KOTA PALOPO",
        "KOTA PARE PARE",
        "KOTA MAKASSAR",
        "KAB LUWU TIMUR",
        "KAB SIDENRENG RAPPANG",
        "KAB WAJO",
        "WATAMPONE",
        "BULUKUMBA",
        "KOTA PALU",
        "KAB TOLI TOLI",
        "KAB MOROWALI",
        "LUWUK",
        "KOTA KOTAMOBAGU",
        "KOTA MANADO",
        "KAB MINAHASA UTARA",
        "KAB MINAHASA SELATAN",
        "KOTA TARAKAN",
        "KAB NUNUKAN",
        "TANJUNG SELOR",
        "KOTA SAMARINDA",
        "KOTA BALIKPAPAN",
        "KAB PENAJAM PASER UTARA",
        "KAB BERAU",
        "KOTA BANJARMASIN",
        "TANJUNG",
        "KAB HULU SUNGAI TENGAH",
        "KOTABARU",
        "KAB TANAH LAUT",
        "KOTA PALANGKARAYA",
        "KAB SUKAMARA",
        "KAB KAPUAS",
        "SAMPIT",
        "KOTA SINGKAWANG",
        "KOTA PONTIANAK",
        "KAB KAYONG UTARA",
        "SINTANG",
        "KAB KETAPANG",
        "KOTA KUPANG",
        "KAB NGADA",
        "MAUMERE",
        "KAB TIMOR TENGAH SELATAN",
        "WAINGAPU",
        "KOTA BIMA",
        "KOTA MATARAM",
        "KAB SUMBAWA",
        "KOTA DENPASAR",
        "SINGARAJA",
        "KAB BADUNG",
        "KAB TABANAN",
        "KOTA SERANG",
        "KOTA CILEGON",
        "KOTA TANGERANG",
        "KAB LEBAK",
        "KAB PANDEGLANG",
        "KOTA SURABAYA",
        "KOTA MADIUN",
        "KOTA PROBOLINGGO",
        "KOTA MALANG",
        "KOTA KEDIRI",
        "SUMENEP",
        "KAB GRESIK",
        "KAB BOJONEGORO",
        "BANYUWANGI",
        "JEMBER",
        "KAB TULUNGAGUNG",
        "KOTA YOGYAKARTA",
        "KAB GUNUNGKIDUL",
        "KOTA TEGAL",
        "KOTA SEMARANG",
        "KOTA SURAKARTA",
        "KUDUS",
        "KAB REMBANG",
        "KAB WONOGIRI",
        "KAB WONOSOBO",
        "PURWOKERTO",
        "CILACAP",
        "KOTA TASIKMALAYA",
        "KOTA DEPOK",
        "KOTA BEKASI",
        "KOTA CIREBON",
        "KOTA BANDUNG",
        "KOTA SUKABUMI",
        "KOTA BOGOR",
        "KAB SUBANG",
        "KAB MAJALENGKA",
        "KAB BANDUNG",
        "DKI JAKARTA",
        "KOTA TANJUNG PINANG",
        "KOTA BATAM",
        "KAB KARIMUN",
        "KOTA PANGKAL PINANG",
        "KAB BELITUNG TIMUR",
        "KAB BANGKA BARAT",
        "TANJUNG PANDAN",
        "KOTA METRO",
        "KOTA BANDAR LAMPUNG",
        "KAB MESUJI",
        "KAB LAMPUNG TIMUR",
        "KOTA BENGKULU",
        "KAB MUKO MUKO",
        "KOTA LUBUK LINGGAU",
        "KOTA PALEMBANG",
        "KAB MUARA ENIM",
        "KAB OGAN KOMERING ILIR",
        "KOTA JAMBI",
        "MUARA BUNGO",
        "KAB KERINCI",
        "KOTA DUMAI",
        "KOTA PEKANBARU",
        "KAB KAMPAR",
        "TEMBILAHAN",
        "KOTA BUKITTINGGI",
        "KOTA PADANG",
        "KAB PASAMAN BARAT",
        "KAB DHARMASRAYA",
        "KOTA GUNUNGSITOLI",
        "KOTA PADANGSIDIMPUAN",
        "KOTA MEDAN",
        "KOTA PEMATANG SIANTAR",
        "KOTA SIBOLGA",
        "KAB DELI SERDANG",
        "KAB KARO",
        "KAB LABUHANBATU",
        "KOTA LHOKSEUMAWE",
        "KOTA BANDA ACEH",
        "KAB ACEH TAMIANG",
        "MEULABOH",
        "KAB ACEH TENGAH",
    ],

    inflasi_provinsi: [
        2.5, 3.1, 2.8, 3.3, 2.9, 3.5, 2.7, -3.0, 3.2, 2.6, 2.9, 3.4, 3.1, 2.7,
        3.6, 2.8, 3.3, 3.0, 2.5, 3.2, 3.1, 2.9, 3.5, -2.8, 3.0, 2.7, 3.3, 3.1,
        2.6, 3.4, 2.9, 3.0, 2.5, 2.8, 3.2, 3.1, -2.7, 3.5,
    ],

    chartData: {
        labels: [
            "Harga Produsen",
            "Harga Produsen Desa",
            "Harga Perdagangan Besar",
            "Harga Konsumen Desa",
            "Harga Konsumen Kota",
        ],
        datasets: [
            {
                label: "Naik (↑)",
                data: [20, 47, 35, 20, 30], // Values for each harga level
                backgroundColor: "#36a2eb", // Blue
                stack: "stack1",
            },
            {
                label: "Stabil (-)",
                data: [35, 30, 40, 22, 30], // Values for each harga level
                backgroundColor: "#4bc0c0", // Green
                stack: "stack1",
            },
            {
                label: "Menurun (↓)",
                data: [45, 4, 25, 50, 40], // Values for each harga level
                backgroundColor: "red", // Red
                stack: "stack1",
            },
        ],
    },

    initCharts() {
        // Multi-Axis Chart
        this.multiAxisChart = new Chart(
            document.getElementById("multiAxisChart").getContext("2d"),
            {
                type: "line",
                data: {
                    labels: this.labels,
                    datasets: this.datasets.map((dataset) => ({
                        label: dataset.label,
                        data: dataset.inflasi,
                        inflasi: dataset.inflasi,
                        andil: dataset.andil,
                    })),
                },
                options: {
                    responsive: true,
                    interaction: { mode: "point", intersect: false },
                    plugins: {
                        legend: { position: "bottom" },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    const dataset = context.dataset;
                                    const inflasi =
                                        dataset.inflasi[context.dataIndex];
                                    const andil =
                                        dataset.andil[context.dataIndex];
                                    return [
                                        `${dataset.label}`,
                                        `Inflasi = ${inflasi}%`,
                                        `Andil = ${andil}%`,
                                    ];
                                },
                            },
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: { display: true, text: "Inflasi (%)" },
                        },
                        x: { title: { display: true, text: "Month" } },
                    },
                },
            }
        );

        // Horizontal Bar Chart
        this.horizontalBarChart = new Chart(
            document.getElementById("horizontalBarChart").getContext("2d"),
            {
                type: "bar",
                options: {
                    indexAxis: "y",
                    elements: { bar: { borderWidth: 2 } },
                },
                data: {
                    labels: this.datasets.map((dataset) => dataset.label),
                    datasets: [
                        {
                            label: "Inflasi",
                            data: this.datasets.map(
                                (dataset) =>
                                    dataset.inflasi[this.labels.length - 1]
                            ),
                        },
                        {
                            label: "Andil",
                            data: this.datasets.map(
                                (dataset) =>
                                    dataset.andil[this.labels.length - 1]
                            ),
                        },
                    ],
                },
            }
        );

        // Stacked Bar Chart
        this.stackedBarChart = new Chart(
            document.getElementById("stackedBarChart").getContext("2d"),
            {
                type: "bar",
                data: this.chartData,
                options: {
                    responsive: true,
                    plugins: { legend: { position: "bottom" } },
                    scales: {
                        x: { stacked: true },
                        y: { stacked: true, beginAtZero: true, max: 100 },
                    },
                },
            }
        );

        // Rank Bar Charts (Provinsi and Kabkot)
        const provinsiInflasi = this.provinces.map(() =>
            (Math.random() * 10).toFixed(2)
        );
        const sortedData = this.provinces
            .map((prov, index) => ({
                name: prov.nama_wilayah || `Provinsi ${index + 1}`,
                value: parseFloat(provinsiInflasi[index]),
            }))
            .sort((a, b) => b.value - a.value);
        const sortedProvinsi = sortedData.map((item) => item.name);
        const sortedInflasi = sortedData.map((item) => item.value);

        this.rankBarChartProvinsi = new Chart(
            document.getElementById("rankBarChartProvinsi").getContext("2d"),
            {
                type: "bar",
                options: {
                    indexAxis: "y",
                    elements: { bar: { borderWidth: 2 } },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) =>
                                    `Inflasi: ${context.raw.toFixed(2)}%`,
                            },
                        },
                    },
                },
                data: {
                    labels: sortedProvinsi,
                    datasets: [{ label: "Inflasi", data: sortedInflasi }],
                },
            }
        );

        this.rankBarChartProvinsi2 = new Chart(
            document.getElementById("rankBarChartProvinsi2").getContext("2d"),
            {
                type: "bar",
                options: {
                    indexAxis: "y",
                    elements: { bar: { borderWidth: 2 } },
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) =>
                                    `Inflasi: ${context.raw.toFixed(2)}%`,
                            },
                        },
                    },
                },
                data: {
                    labels: sortedProvinsi,
                    datasets: [{ label: "Inflasi", data: sortedInflasi }],
                },
            }
        );

        // Small Multiples: 5 Horizontal Bar Charts for Price Levels
        const maxInflation = Math.max(
            ...Object.values(this.inflationData).flatMap((prov) =>
                Object.values(prov).map((val) => parseFloat(val))
            )
        );

        this.priceLevels.forEach((level, index) => {
            const canvas = document.getElementById(
                `priceLevelChart${index + 1}`
            );
            if (!canvas) {
                console.error(`Canvas for ${level} not found`);
                return;
            }

            this.priceLevelCharts[level] = new Chart(canvas.getContext("2d"), {
                type: "bar",
                data: {
                    labels: this.provinces.map(
                        (p) => p.nama_wilayah || `Provinsi ${p.kd_wilayah}`
                    ),
                    datasets: [
                        {
                            label: level,
                            data: this.provinces.map(
                                (p) =>
                                    this.inflationData[p.kd_wilayah]?.[level] ||
                                    0
                            ),
                            backgroundColor: "#36a2eb",
                            borderWidth: 1,
                            barThickness: 12, // Consistent bar thickness
                        },
                    ],
                },
                options: {
                    indexAxis: "y",
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: (context) => `Inflasi: ${context.raw}%`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            title: { display: true, text: "Inflation (%)" },
                            min: 0,
                            max: Math.ceil(maxInflation),
                            ticks: {
                                maxTicksLimit: 8,
                            },
                            afterFit: (scale) => {
                                scale.width = 50;
                            },
                        },
                        y: {
                            title: {
                                display: true,
                                text: index === 0 ? "Province" : "",
                            },
                            ticks: {
                                display: index === 0,
                                maxTicksLimit: 34,
                                autoSkip: false,
                            },
                            min: 0,
                            max: this.provinces.length - 1,
                            offset: true,
                            grid: {
                                display: index === 0,
                            },
                            afterFit: (scale) => {
                                scale.width = index === 0 ? 150 : 10; // 150px for first chart, 10px for others
                            },
                        },
                    },
                },
            });
        });

        // Plotly Heatmap for Inflation Data
        const provinces = this.provinces.map(
            (p) => p.nama_wilayah || `Provinsi ${p.kd_wilayah}`
        );
        const priceLevels = this.priceLevels; // ["Harga Produsen", "Harga Produsen Desa", etc.]

        // Create the z-values (inflation data) for the heatmap
        const zValues = provinces.map((prov) => {
            const provData =
                this.inflationData[
                    this.provinces.find((p) => p.nama_wilayah === prov)
                        ?.kd_wilayah
                ] || {};
            return priceLevels.map((level) => parseFloat(provData[level]) || 0);
        });

        // Create annotations for the heatmap (to display the exact values)
        const annotations = [];
        for (let i = 0; i < provinces.length; i++) {
            for (let j = 0; j < priceLevels.length; j++) {
                annotations.push({
                    x: priceLevels[j],
                    y: provinces[i],
                    text: zValues[i][j].toFixed(2) + "%",
                    showarrow: false,
                    font: {
                        color: zValues[i][j] > 5 ? "white" : "black", // Adjust text color based on value for readability
                    },
                });
            }
        }

        const data = [
            {
                type: "heatmap",
                x: priceLevels,
                y: provinces,
                z: zValues,
                colorscale: "Viridis", // You can change this to "Blues", "Reds", etc.
                showscale: true,
            },
        ];

        const layout = {
            title: "",
            xaxis: {
                title: "Price Level",
                tickangle: 45,
            },
            yaxis: {
                title: "Province",
                automargin: true, // Automatically adjust margin for long province names
            },
            annotations: annotations,
            margin: { t: 50, b: 100, l: 150, r: 50 }, // Adjust margins for better fit
            height: 600, // Match the height of the container
        };

        Plotly.newPlot("inflationHeatmap", data, layout);
    },

    initMaps() {
        // Ensure Leaflet is available
        if (typeof L === "undefined") {
            console.error("Leaflet library is not loaded.");
            return;
        }

        // Initialize Map 1 (Provinsi)
        this.map = L.map("map").setView([-2.5489, 118.0149], 5); // Centered on Indonesia
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution:
                '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(this.map);

        // Initialize Map 2 (Kabkot)
        this.map2 = L.map("map2").setView([-2.5489, 118.0149], 5);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution:
                '© <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(this.map2);

        // Load Provinsi GeoJSON
        fetch("/geojson/Provinsi.json") // Adjust path as needed
            .then((response) => response.json())
            .then((data) => {
                data.features.forEach((feature) => {
                    const regionCode = feature.properties.KODE_PROV;
                    const inflationRate = this.mapDemo[regionCode];
                    feature.properties.inflation_rate =
                        inflationRate !== undefined ? inflationRate : null;
                });

                L.geoJSON(data, {
                    style: (feature) => ({
                        fillColor: this.getColor(
                            feature.properties.inflation_rate
                        ),
                        weight: 1,
                        opacity: 1,
                        color: "white",
                        dashArray: "3",
                        fillOpacity: 0.7,
                    }),
                    onEachFeature: (feature, layer) => {
                        layer.bindPopup(
                            `<strong>${feature.properties.PROVINSI}</strong><br>Inflation Rate: ${feature.properties.inflation_rate}`
                        );
                    },
                }).addTo(this.map);
            })
            .catch((err) =>
                console.error("Error loading Provinsi GeoJSON:", err)
            );

        // Load Kabkot GeoJSON
        fetch("/geojson/kab_indo_dummy4.json") // Adjust path as needed
            .then((response) => response.json())
            .then((data) => {
                data.features.forEach((feature) => {
                    const regionCode = feature.properties.idkab;
                    const inflationRate = this.mapDemo2[regionCode];
                    feature.properties.inflation_rate =
                        inflationRate !== undefined ? inflationRate : null;
                });

                L.geoJSON(data, {
                    style: (feature) => ({
                        fillColor: this.getColor2(
                            feature.properties.inflation_rate
                        ),
                        weight: 1,
                        opacity: 1,
                        color: "white",
                        dashArray: "3",
                        fillOpacity: 0.7,
                    }),
                    onEachFeature: (feature, layer) => {
                        layer.bindPopup(
                            `<strong>${feature.properties.nmkab}</strong><br>Inflation Rate: ${feature.properties.inflation_rate}`
                        );
                    },
                }).addTo(this.map2);
            })
            .catch((err) =>
                console.error("Error loading Kabkot GeoJSON:", err)
            );
    },

    // Map color functions
    getColor(value) {
        return value > 6
            ? "#800026"
            : value > 5
            ? "#BD0026"
            : value > 4
            ? "#E31A1C"
            : value > 3
            ? "#FC4E2A"
            : value > 2
            ? "#FD8D3C"
            : value > 0
            ? "#800026"
            : "#FEB24C";
    },

    getColor2(value) {
        return value > 0 ? "#800026" : "#FEB24C";
    },

    // Chart control methods
    showInflasiLine() {
        this.multiAxisChart.data.datasets = this.datasets.map((dataset) => ({
            label: dataset.label,
            data: dataset.inflasi,
            inflasi: dataset.inflasi,
            andil: dataset.andil,
        }));
        this.multiAxisChart.update();
    },

    showAndilLine() {
        this.multiAxisChart.data.datasets = this.datasets.map((dataset) => ({
            label: dataset.label,
            data: dataset.andil,
            inflasi: dataset.inflasi,
            andil: dataset.andil,
        }));
        this.multiAxisChart.update();
    },

    showBothLine() {
        this.multiAxisChart.data.datasets = this.datasets.flatMap((dataset) => [
            {
                label: `${dataset.label} - Inflasi`,
                data: dataset.inflasi,
                inflasi: dataset.inflasi,
                andil: dataset.andil,
                borderColor: "#4FCFCF",
            },
            {
                label: `${dataset.label} - Andil`,
                data: dataset.andil,
                inflasi: dataset.inflasi,
                andil: dataset.andil,
                borderColor: "gray",
            },
        ]);
        this.multiAxisChart.update();
    },

    toggleFullscreen(canvasId) {
        const canvas = document.getElementById(canvasId);
        const fullscreenIcon = document.getElementById(
            `fullscreenIcon${
                canvasId.charAt(0).toUpperCase() + canvasId.slice(1)
            }`
        );
        const container = canvas.parentElement; // Get the parent div

        if (!document.fullscreenElement) {
            container.requestFullscreen().catch((err) => {
                console.log(
                    `Error trying to enable fullscreen: ${err.message}`
                );
            });
            canvas.classList.remove("max-h-96"); // Remove height limit in fullscreen
            fullscreenIcon.textContent = "close_fullscreen"; // Change icon to close
        } else {
            document.exitFullscreen();
            canvas.classList.add("max-h-96"); // Restore height limit when exiting fullscreen
            fullscreenIcon.textContent = "fullscreen"; // Change icon back to fullscreen
        }
    },

    toggleFullscreenMap(mapId) {
        const mapElement = document.getElementById(mapId);
        const fullscreenIcon = document.getElementById(
            `fullscreenIcon${mapId.charAt(0).toUpperCase() + mapId.slice(1)}`
        );
        const container = mapElement.parentElement; // Get the parent div

        if (!document.fullscreenElement) {
            container.requestFullscreen().catch((err) => {
                console.log(
                    `Error trying to enable fullscreen: ${err.message}`
                );
            });
            mapElement.classList.remove("h-64"); // Remove height limit in fullscreen
            mapElement.style.width = "100%";
            mapElement.style.height = "100vh"; // Set height to full viewport height
            fullscreenIcon.textContent = "close_fullscreen"; // Change icon to close
        } else {
            document.exitFullscreen();
            mapElement.classList.add("h-64"); // Restore height limit when exiting fullscreen
            mapElement.style.width = "";
            mapElement.style.height = "";
            fullscreenIcon.textContent = "fullscreen"; // Change icon back to fullscreen
        }
    },

    // Existing methods
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
