import "flowbite";
import Alpine from "alpinejs";
import * as echarts from "echarts";

// Make Alpine globally available
window.Alpine = Alpine;

// Non-reactive container for eCharts instances to avoid proxying performance issues
const charts = new Map();

Alpine.data("webData", () => ({
    loading: false,
    modalMessage: "",
    errorMessage: "",
    errors: [],
    data: null,
    colors: {
        "Harga Konsumen Kota": "#5470C6",
        "Harga Konsumen Desa": "#73C0DE",
        "Harga Perdagangan Besar": "#8A9A5B",
        "Harga Produsen Desa": "#9A60B4",
        "Harga Produsen": "#FC8452",
    },
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
    provinces: [],
    kabkots: [],
    komoditas: [],
    selectedProvince: "",
    selectedKabkot: "",
    selectedKomoditas: "",
    selectedKdLevel: "",
    pendingKdLevel: "",
    selectedLevel: "HK",
    isPusat: false,
    kd_wilayah: "",
    wilayahLevel: "1",
    pendingWilayahLevel: "1",
    provinsiGeoJson: null,
    kabkotGeoJson: null,
    showAndil: false, // Toggle to switch between inflasi and andil
    colorPalette: {
        HK: "#5470C6",
        HK_Desa: "#73C0DE",
        HPB: "#8A9A5B",
        HPD: "#9A60B4",
        HP: "#FC8452",
        Deflation: "#EE6666",
        Inflation: "#65B581",
        VisualMap: ["#FD665F", "#FFCE34", "#65B581"],
    },

    // Computed property to check if the selected period is active
    get isActivePeriod() {
        return (
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun
        );
    },

    // Initialize the component
    async init() {
        this.loading = true;
        this.initializeCharts();
        this.resizeCharts();
        try {
            const [
                wilayahResponse,
                komoditasResponse,
                bulanTahunResponse,
                provGeo,
                kabkotGeo,
            ] = await Promise.all([
                fetch("/api/wilayah").then((res) => {
                    if (!res.ok)
                        throw new Error(`Wilayah API error: ${res.status}`);
                    return res.json();
                }),
                fetch("/api/komoditas").then((res) => {
                    if (!res.ok)
                        throw new Error(`Komoditas API error: ${res.status}`);
                    return res.json();
                }),
                fetch("/api/bulan_tahun").then((res) => {
                    if (!res.ok)
                        throw new Error(`BulanTahun API error: ${res.status}`);
                    return res.json();
                }),
                fetch("/geojson/Provinsi.json")
                    .then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `Provinsi GeoJSON error: ${res.status}`
                            );
                        return res.json();
                    })
                    .catch((err) => {
                        console.error("Failed to load Provinsi GeoJSON:", err);
                        return null;
                    }),
                fetch("/geojson/kab_indo_dummy4.json")
                    .then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `Kabkot GeoJSON error: ${res.status}`
                            );
                        return res.json();
                    })
                    .catch((err) => {
                        console.error("Failed to load Kabkot GeoJSON:", err);
                        return null;
                    }),
            ]);

            this.provinces = wilayahResponse.data?.provinces || [];
            this.kabkots = wilayahResponse.data?.kabkots || [];
            this.komoditas = komoditasResponse.data || [];
            const aktifData = bulanTahunResponse.data?.bt_aktif;
            this.bulan = aktifData?.bulan || "";
            this.tahun = aktifData?.tahun || "";
            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;
            this.tahunOptions =
                bulanTahunResponse.data?.tahun ||
                (aktifData ? [aktifData.tahun] : []);
            this.selectedKomoditas = "001";
            this.isPusat = true;
            this.kd_wilayah = "0";
            this.wilayahLevel = "1";
            this.pendingWilayahLevel = "1";
            this.provinsiGeoJson = provGeo;
            this.kabkotGeoJson = kabkotGeo;

            if (this.provinsiGeoJson) {
                echarts.registerMap("Provinsi_Indonesia", this.provinsiGeoJson);
            } else {
                console.warn(
                    "Provinsi GeoJSON not loaded; choropleth map may not work"
                );
            }
            if (this.kabkotGeoJson) {
                echarts.registerMap("Kabkot_Indonesia", this.kabkotGeoJson);
            } else {
                console.warn(
                    "Kabkot GeoJSON not loaded; choropleth map may not work"
                );
            }

            await this.fetchData();

            window.addEventListener("resize", () => {
                clearTimeout(window.resizeTimeout);
                window.resizeTimeout = setTimeout(
                    () => this.resizeCharts(),
                    200
                );
            });

            document.addEventListener("alpine:init", () => {
                Alpine.effect(() => {
                    setTimeout(() => this.resizeCharts(), 200);
                });
            });
        } catch (error) {
            console.error("Initialization failed:", error);
            this.errorMessage = "Gagal menginisialisasi aplikasi";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },

    // Initialize all charts
    initializeCharts() {
        const chartConfigs = [
            { id: "lineChart", type: "line", height: 384 },
            { id: "horizontalBarChart", type: "bar", height: 384 },
            { id: "heatmapChart", type: "heatmap", height: 550 },
            { id: "stackedBarChart", type: "bar", height: 384 },
            { id: "provHorizontalBarChart", type: "bar", height: 550 },
            { id: "kabkotHorizontalBarChart", type: "bar", height: 550 },
            { id: "provinsiChoropleth", type: "map", height: 1000 },
            { id: "kabkotChoropleth", type: "map", height: 1000 },
        ];

        const resizeObserver = new ResizeObserver((entries) => {
            entries.forEach((entry) => {
                const chartId = entry.target.id;
                const chart = charts.get(chartId);
                if (chart) {
                    chart.resize();
                    console.log(`Resized ${chartId}`);
                } else {
                    console.warn(`Chart ${chartId} not found in charts Map`);
                }
            });
        });

        chartConfigs.forEach((config) => {
            const chartDiv = document.getElementById(config.id);
            if (chartDiv) {
                const chart = echarts.init(chartDiv);
                charts.set(config.id, chart);
                chart.showLoading({
                    text: "Loading data...",
                    color: "#5470C6",
                    textColor: "#000",
                    maskColor: "rgba(255, 255, 255, 0.8)",
                });
                resizeObserver.observe(chartDiv);
                console.log(`Initialized and observing ${config.id}`);
            } else {
                console.warn(`Chart element #${config.id} not found in DOM`);
            }
        });
    },

    // Resize all charts
    resizeCharts() {
        const paddingX = 32;
        charts.forEach((chart, chartId) => {
            const chartDiv = document.getElementById(chartId);
            if (chart && chartDiv && !chart.isDisposed()) {
                const container = chartDiv.parentElement;
                const width = container.clientWidth - paddingX;
                const height = chartDiv.clientHeight;
                if (width > 0 && height > 0) {
                    chart.resize({ width, height });
                    console.log(`Resized ${chartId}: ${width}x${height}`);
                }
            }
        });
    },

    // Check form validity
    checkFormValidity() {
        return (
            this.bulan &&
            this.tahun &&
            this.selectedKomoditas &&
            (this.wilayahLevel === "1" ||
                (this.wilayahLevel === "2" && this.kd_wilayah !== "0"))
        );
    },

    // Handle komoditas selection
    selectKomoditas(event) {
        this.selectedKomoditas = event.target.value;
        this.fetchData();
    },

    // Computed property for filtered kabkots
    get filteredKabkots() {
        if (!this.selectedProvince?.kd_wilayah) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd == this.selectedProvince.kd_wilayah
        );
    },

    // Update selected province and kd_wilayah
    selectProvince(province) {
        this.selectedProvince = province;
        this.updateKdWilayah();
        this.fetchData();
    },

    // Reset province selection when wilayah level changes
    updateWilayahOptions() {
        this.selectedProvince = "";
        this.updateKdWilayah();
    },

    // Update kd_wilayah based on wilayah level
    updateKdWilayah() {
        this.kd_wilayah =
            this.pendingWilayahLevel === "1"
                ? "2"
                : this.selectedProvince || "";
        console.log("kd_wilayah:", this.kd_wilayah);
    },

    // Fetch data from API
    async fetchData() {
        this.errorMessage = "";
        this.errors = [];
        charts.forEach((chart) => chart.showLoading());

        try {
            const params = new URLSearchParams({
                bulan: this.bulan,
                tahun: this.tahun,
                level_wilayah: this.pendingWilayahLevel,
                kd_wilayah: this.kd_wilayah,
                kd_komoditas: this.selectedKomoditas,
            });

            const response = await fetch(`/api/visualisasi?${params}`);
            if (!response.ok) throw new Error(`API error: ${response.status}`);
            const result = await response.json();

            if (result.status === "partial" || result.errors?.length > 0) {
                this.errorMessage =
                    result.message || "Beberapa data tidak tersedia.";
                this.errors = result.errors || result.data?.errors || [];
            }
            this.selectedKdLevel = this.pendingKdLevel;

            this.data = result.data;
            this.updateCharts(result.data);
        } catch (error) {
            console.error("Fetch data failed:", error);
            // this.$dispatch("open-modal", "error-modal");
        } finally {
            charts.forEach((chart) => chart.hideLoading());
        }
    },

    // Toggle between inflasi and andil display
    toggleAndil() {
        this.showAndil = !this.showAndil;
        const toggleAndilBtn = document.getElementById("toggleAndilBtn");
        if (toggleAndilBtn) {
            toggleAndilBtn.textContent = this.showAndil
                ? "Lihat Inflasi"
                : "Lihat Andil";
        }
        if (this.data) {
            this.updateCharts(this.data); // Re-render charts with toggled data
        }
    },

    // Handle level selection change
    selectLevel(event) {
        this.selectedLevel = event.target.value;
        const levelMap = { HK: "01", HD: "02", HPB: "03", HPD: "04", HP: "05" };
        this.selectedKdLevel = levelMap[this.selectedLevel] || "01";
        if (this.data) {
            this.updateCharts(this.data);
        }
        this.resizeCharts();
    },

    // Dismiss errors
    dismissErrors() {
        this.errorMessage = "";
        this.errors = [];
    },

    // Update all charts with new data
    // Inside the Alpine.data("webData", () => ({ ... })) block
    // Inside the Alpine.data("webData", () => ({ ... })) block
    updateCharts(data) {
        this.data = data;
        const isNational = this.kd_wilayah === "0";
        const chartKey = isNational ? "line" : "provinsiLine";

        // Define color mapping for stackedBarChart
        const stackedBarColors = {
            "Turun (<0)": "#EE6666", // red
            "Stabil (=0)": "#FFCE34", // yellow
            "Naik (>0)": "#91CC75", // green
            "Data tidak tersedia": "#DCDDE2", // gray
        };

        // Helper function to get chart title with fallback
        const getChartTitle = (key) => {
            const title = data.chart_status?.[key]?.title || `Chart ${key}`;
            console.log(`Chart ${key} Title: ${title}`);
            return title;
        };

        // Line Chart
        const lineChart = charts.get("lineChart");
        if (lineChart && data.chart_data[chartKey]) {
            const seriesData = data.chart_data[chartKey].series.map((s) => ({
                name: `${s.name} (${this.showAndil ? "Andil" : "Inflasi"})`,
                type: "line",
                data: this.showAndil ? s.andil : s.inflasi,
                itemStyle: {
                    color: this.colors[s.name] || this.colorPalette.HK,
                },
            }));
            lineChart.setOption({
                title: {
                    text: getChartTitle(chartKey),
                    left: "left",
                    top: 10,
                    textStyle: {
                        fontSize: 16,
                        color: "#333",
                        fontWeight: "bold",
                    },
                    padding: [0, 0, 50, 0],
                },
                tooltip: { trigger: "axis" },
                legend: { bottom: 0, data: seriesData.map((s) => s.name) },
                grid: {
                    left: "3%",
                    right: "4%",
                    bottom: "20%",
                    containLabel: true,
                },
                toolbox: {
                    feature: {
                        saveAsImage: { title: "Save as PNG" },
                        restore: {},
                    },
                },
                xAxis: {
                    type: "category",
                    data: data.chart_data[chartKey].xAxis,
                },
                yAxis: {
                    type: "value",
                    name: this.showAndil ? "Andil (%)" : "Inflasi (%)",
                },
                series: seriesData,
            });
            lineChart.hideLoading();
        } else if (lineChart) {
            lineChart.showLoading({
                text: "No data available",
                color: "#FD665F",
            });
        }

        // Horizontal Bar Chart
        const horizontalBarChart = charts.get("horizontalBarChart");
        if (horizontalBarChart && data.chart_data.horizontalBar) {
            horizontalBarChart.setOption({
                title: {
                    text: getChartTitle("horizontalBar"),
                    left: "left",
                    top: 10,
                    textStyle: { fontSize: 16, fontWeight: "bold" },
                },
                tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
                toolbox: {
                    feature: {
                        saveAsImage: { title: "Save as PNG" },
                        restore: {},
                    },
                },
                legend: { bottom: 0, data: ["Inflasi", "Andil"] },
                grid: { containLabel: true, left: "5%", right: "15%" },
                xAxis: { type: "value", name: "Nilai (%)" },
                yAxis: {
                    type: "category",
                    data: data.chart_data.horizontalBar.datasets.map(
                        (d) => d.label
                    ),
                },
                series: [
                    {
                        name: "Inflasi",
                        type: "bar",
                        data: data.chart_data.horizontalBar.datasets.map(
                            (d) => d.inflasi[d.inflasi.length - 1]
                        ),
                        itemStyle: { color: this.colorPalette.HK },
                    },
                    {
                        name: "Andil",
                        type: "bar",
                        data: data.chart_data.horizontalBar.datasets.map(
                            (d) => d.andil[d.andil.length - 1]
                        ),
                        itemStyle: { color: this.colorPalette.HK_Desa },
                    },
                ],
            });
            horizontalBarChart.hideLoading();
        }

        // Heatmap Chart
        const heatmapChart = charts.get("heatmapChart");
        if (isNational && heatmapChart && data.chart_data.heatmap) {
            heatmapChart.setOption({
                title: {
                    text: getChartTitle("heatmap"),
                    left: "left",
                    top: 10,
                    textStyle: {
                        fontSize: 16,
                        color: "#333",
                        fontWeight: "bold",
                    },
                    padding: [0, 0, 50, 0],
                },
                tooltip: { position: "top" },
                toolbox: {
                    feature: {
                        saveAsImage: { title: "Save as PNG" },
                        restore: {},
                    },
                },
                grid: { left: "5%", right: "15%", containLabel: true },
                xAxis: {
                    type: "category",
                    data: data.chart_data.heatmap.xAxis,
                    splitArea: { show: true },
                },
                yAxis: {
                    type: "category",
                    data: data.chart_data.heatmap.yAxis,
                    splitArea: { show: true },
                },
                visualMap: {
                    min: data.chart_data.heatmap.min ?? -5,
                    max: data.chart_data.heatmap.max ?? 5,
                    calculable: true,
                    orient: "horizontal",
                    left: "left",
                    bottom: 0,
                    inRange: { color: this.colorPalette.VisualMap },
                },
                dataZoom: [
                    {
                        type: "slider",
                        orient: "vertical",
                        handleIcon: "roundRect",
                    },
                ],
                series: [
                    {
                        name: "Inflasi",
                        type: "heatmap",
                        data: data.chart_data.heatmap.values,
                        label: { show: true },
                        emphasis: {
                            itemStyle: {
                                shadowBlur: 10,
                                shadowColor: "rgba(0, 0, 0, 0.5)",
                            },
                        },
                    },
                ],
            });
            heatmapChart.hideLoading();
        }

        // Stacked Bar Chart
        const stackedBarChart = charts.get("stackedBarChart");
        if (isNational && stackedBarChart && data.chart_data.stackedBar) {
            console.log("Stacked Bar Chart Data:", data.chart_data.stackedBar);
            const series = data.chart_data.stackedBar.datasets.map((d) => {
                const color = stackedBarColors[d.label] || this.colorPalette.HK;
                console.log(`Label: ${d.label}, Assigned Color: ${color}`);
                if (!/^#[0-9A-F]{6}$/i.test(color)) {
                    console.warn(
                        `Invalid color for ${d.label}: ${color}, falling back to ${this.colorPalette.HK}`
                    );
                }
                return {
                    name: d.label,
                    type: "bar",
                    stack: "total",
                    data: d.data,
                    itemStyle: {
                        color: /^#[0-9A-F]{6}$/i.test(color)
                            ? color
                            : this.colorPalette.HK,
                    },
                };
            });
            stackedBarChart.setOption({
                title: {
                    text: getChartTitle("stackedBar"),
                    left: "left",
                    top: 10,
                    textStyle: {
                        fontSize: 16,
                        color: "#333",
                        fontWeight: "bold",
                    },
                    padding: [0, 0, 50, 0],
                },
                tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
                toolbox: {
                    feature: {
                        saveAsImage: { title: "Save as PNG" },
                        restore: {},
                    },
                },
                legend: { bottom: 0 },
                grid: { left: "10%", right: "10%", bottom: "15%", top: "10%" },
                xAxis: {
                    type: "category",
                    data: data.chart_data.stackedBar.labels,
                },
                yAxis: { type: "value", name: "Jumlah Provinsi" },
                series: series,
            });
            stackedBarChart.hideLoading();
        } else if (stackedBarChart) {
            console.log("No stacked bar data available or not national level");
            stackedBarChart.showLoading({
                text: "No stacked bar data available",
                color: "#FD665F",
            });
        }

        // Province Horizontal Bar Chart
        const provHorizontalBarChart = charts.get("provHorizontalBarChart");
        if (provHorizontalBarChart && data.chart_data?.provHorizontalBar) {
            console.log(
                "provHorizontalBarChart Data:",
                data.chart_data.provHorizontalBar
            );
            const provData = data.chart_data.provHorizontalBar.find(
                (d) => d.kd_level === this.selectedKdLevel
            );
            if (provData) {
                console.log("provHorizontalBarChart Selected Data:", provData);
                console.log(
                    "provHorizontalBarChart Color:",
                    this.colorPalette.HK
                );
                if (!/^#[0-9A-F]{6}$/i.test(this.colorPalette.HK)) {
                    console.warn(
                        `Invalid color for provHorizontalBarChart: ${this.colorPalette.HK}, using fallback #5470C6`
                    );
                }
                try {
                    provHorizontalBarChart.setOption({
                        title: {
                            text: getChartTitle("provHorizontalBar"),
                            left: "left",
                            top: 10,
                            textStyle: {
                                fontSize: 16,
                                color: "#333",
                                fontWeight: "bold",
                            },
                            padding: [0, 0, 50, 0],
                        },
                        tooltip: {
                            trigger: "axis",
                            axisPointer: { type: "shadow" },
                        },
                        toolbox: {
                            feature: {
                                saveAsImage: { title: "Save as PNG" },
                                restore: {},
                            },
                        },
                        grid: {
                            left: "5%",
                            right: "20%",
                            bottom: "10%",
                            top: "10%",
                            containLabel: true,
                        },
                        dataZoom: [{ type: "slider", orient: "vertical" }],
                        xAxis: { type: "value", name: "Inflasi (%)" },
                        yAxis: { type: "category", data: provData.names || [] },
                        series: [
                            {
                                name: "Inflasi",
                                type: "bar",
                                data: provData.inflasi || [],
                                itemStyle: {
                                    color: /^#[0-9A-F]{6}$/i.test(
                                        this.colorPalette.HK
                                    )
                                        ? this.colorPalette.HK
                                        : "#5470C6",
                                },
                            },
                        ],
                    });
                    console.log("provHorizontalBarChart updated successfully");
                    provHorizontalBarChart.hideLoading();
                } catch (error) {
                    console.error(
                        "Error updating provHorizontalBarChart:",
                        error
                    );
                    provHorizontalBarChart.showLoading({
                        text: "Error rendering chart",
                        color: "#FD665F",
                    });
                }
            } else {
                console.warn(
                    "No provHorizontalBar data for selectedKdLevel:",
                    this.selectedKdLevel
                );
                provHorizontalBarChart.showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }
        } else if (provHorizontalBarChart) {
            console.log("No provHorizontalBar data available");
            provHorizontalBarChart.showLoading({
                text: "No data available",
                color: "#FD665F",
            });
        }

        // Kabkot Horizontal Bar Chart
        const kabkotHorizontalBarChart = charts.get("kabkotHorizontalBarChart");
        if (
            this.selectedLevel === "HK" &&
            kabkotHorizontalBarChart &&
            data.chart_data.kabkotHorizontalBar
        ) {
            const kabkotData = data.chart_data.kabkotHorizontalBar.find(
                (d) => d.kd_level === "01"
            );
            if (kabkotData) {
                kabkotHorizontalBarChart.setOption({
                    title: {
                        text: getChartTitle("kabkotHorizontalBar"),
                        left: "left",
                        top: 10,
                        textStyle: {
                            fontSize: 16,
                            color: "#333",
                            fontWeight: "bold",
                        },
                        padding: [0, 0, 50, 0],
                    },
                    tooltip: {
                        trigger: "axis",
                        axisPointer: { type: "shadow" },
                    },
                    grid: {
                        left: "5%",
                        right: "20%",
                        bottom: "10%",
                        top: "10%",
                        containLabel: true,
                    },
                    dataZoom: [{ type: "slider", orient: "vertical" }],
                    xAxis: { type: "value", name: "Inflasi (%)" },
                    yAxis: { type: "category", data: kabkotData.names },
                    series: [
                        {
                            name: "Inflasi",
                            type: "bar",
                            data: kabkotData.inflasi,
                            itemStyle: { color: this.colorPalette.HK },
                        },
                    ],
                });
                kabkotHorizontalBarChart.hideLoading();
            }
        }

        // Province Choropleth
        const provinsiChoropleth = charts.get("provinsiChoropleth");
        if (
            provinsiChoropleth &&
            data.chart_data.provinsiChoropleth &&
            this.provinsiGeoJson
        ) {
            const provData = data.chart_data.provinsiChoropleth.find(
                (d) => d.kd_level === this.selectedKdLevel
            );
            if (provData) {
                const provChoroData = this.provinsiGeoJson.features.map(
                    (feature) => {
                        const regionCode = feature.properties.KODE_PROV;
                        const index = provData.regions.findIndex(
                            (code) => String(code) === regionCode
                        );
                        return {
                            name:
                                index !== -1
                                    ? provData.names[index]
                                    : regionCode,
                            value:
                                index !== -1 ? provData.inflasi[index] : null,
                        };
                    }
                );
                provinsiChoropleth.setOption({
                    title: {
                        text: getChartTitle("provinsiChoropleth"),
                        left: "left",
                        top: 10,
                        textStyle: {
                            fontSize: 16,
                            color: "#333",
                            fontWeight: "bold",
                        },
                        padding: [0, 0, 50, 0],
                    },
                    tooltip: { trigger: "item", formatter: "{b}: {c}%" },
                    visualMap: {
                        min: provData.min ?? -5,
                        max: provData.max ?? 5,
                        calculable: true,
                        inRange: { color: this.colorPalette.VisualMap },
                        left: "right",
                        bottom: 10,
                    },
                    series: [
                        {
                            name: "Inflasi",
                            type: "map",
                            map: "Provinsi_Indonesia",
                            data: provChoroData,
                            nameProperty: "KODE_PROV",
                        },
                    ],
                });
                provinsiChoropleth.hideLoading();
            }
        }

        // Kabkot Choropleth
        const kabkotChoropleth = charts.get("kabkotChoropleth");
        if (
            this.selectedLevel === "HK" &&
            kabkotChoropleth &&
            this.kabkotGeoJson
        ) {
            const kabkotData = isNational
                ? data.chart_data.kabkotChoropleth?.find(
                      (d) => d.kd_level === "01"
                  )
                : data.chart_data.provinsiKabkotChoropleth?.find(
                      (d) => d.kd_level === "01"
                  );
            if (kabkotData) {
                const kabkotChoroData = this.kabkotGeoJson.features.map(
                    (feature) => {
                        const regionCode = feature.properties.idkab;
                        const index = kabkotData.regions.findIndex(
                            (code) => String(code) === regionCode
                        );
                        return {
                            name:
                                index !== -1
                                    ? kabkotData.names[index]
                                    : regionCode,
                            value:
                                index !== -1 ? kabkotData.inflasi[index] : null,
                        };
                    }
                );
                kabkotChoropleth.setOption({
                    title: {
                        text: getChartTitle(
                            isNational
                                ? "kabkotChoropleth"
                                : "provinsiKabkotChoropleth"
                        ),
                        left: "left",
                        top: 10,
                        textStyle: {
                            fontSize: 16,
                            color: "#333",
                            fontWeight: "bold",
                        },
                        padding: [0, 0, 50, 0],
                    },
                    tooltip: { trigger: "item", formatter: "{b}: {c}%" },
                    visualMap: {
                        min: kabkotData.min ?? -5,
                        max: kabkotData.max ?? 5,
                        calculable: true,
                        inRange: { color: this.colorPalette.VisualMap },
                        left: "right",
                        bottom: 10,
                    },
                    series: [
                        {
                            name: "Inflasi",
                            type: "map",
                            map: "Kabkot_Indonesia",
                            data: kabkotChoroData,
                            nameProperty: "idkab",
                        },
                    ],
                });
                kabkotChoropleth.hideLoading();
            }
        }
    },
}));

Alpine.start();
