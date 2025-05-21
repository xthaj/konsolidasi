import "flowbite";
import Alpine from "alpinejs";
import * as echarts from "echarts";

// Make Alpine globally available
window.Alpine = Alpine;

// Non-reactive container for eCharts instances
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
    isPusat: false,
    kd_wilayah: "",
    wilayahLevel: "1",
    pendingWilayahLevel: "1",
    provinsiGeoJson: null,
    kabkotGeoJson: null,
    showAndil: false,
    colorPalette: {
        HK: "#5470C6",
        HK_Desa: "#73C0DE",
        HPB: "#8A9A5B",
        HPD: "#9A60B4",
        HP: "#FC8452",
        Deflation: "#EE6666",
        Inflation: "#65B581",
        VisualMap: [
            "#65B581", // green
            "#FFCE34", //yellow
            "#FD665F", // red
        ],
    },

    get priceLevels() {
        return [
            "Harga Konsumen Kota",
            "Harga Konsumen Desa",
            "Harga Perdagangan Besar",
            "Harga Produsen Desa",
            "Harga Produsen",
        ];
    },
    // Computed property for summary data
    get summaryData() {
        return this.data?.chart_data?.summary || {};
    },

    // Helper function to format percentages
    formatPercentage(value) {
        return value != null ? `${value.toFixed(2)}%` : "N/A";
    },

    // Computed property to check if the selected period is active
    get isActivePeriod() {
        return (
            +this.bulan === +this.activeBulan &&
            +this.tahun === +this.activeTahun
        );
    },

    // Computed property for filtered kabkots
    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter((k) => k.parent_kd == this.selectedProvince);
    },

    // Initialize the component
    async init() {
        this.loading = true;
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
                fetch("/geojson/provinsi.json")
                    .then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `Provinsi GeoJSON error: ${res.status}`
                            );
                        return res.json();
                    })
                    .catch((err) => {
                        // console.error("Failed to load Provinsi GeoJSON:", err);
                        return null;
                    }),
                fetch("/geojson/kabkot.json")
                    .then((res) => {
                        if (!res.ok)
                            throw new Error(
                                `Kabkot GeoJSON error: ${res.status}`
                            );
                        return res.json();
                    })
                    .catch((err) => {
                        // console.error("Failed to load Kabkot GeoJSON:", err);
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
            this.selectedKomoditas = "000";
            this.isPusat = true;
            this.kd_wilayah = "0";
            this.wilayahLevel = "1";
            this.pendingWilayahLevel = "1";
            this.provinsiGeoJson = provGeo;
            this.kabkotGeoJson = kabkotGeo;

            if (this.provinsiGeoJson) {
                echarts.registerMap("Provinsi_Indonesia", this.provinsiGeoJson);
            } else {
                // console.warn(
                //     "Provinsi GeoJSON not loaded; choropleth map may not work"
                // );
            }
            if (this.kabkotGeoJson) {
                echarts.registerMap("Kabkot_Indonesia", this.kabkotGeoJson);
            } else {
                // console.warn(
                //     "Kabkot GeoJSON not loaded; choropleth map may not work"
                // );
            }

            // this.initializeCharts();
            await this.fetchData();
            this.loading = false;

            window.addEventListener("resize", () => {
                clearTimeout(window.resizeTimeout);
                window.resizeTimeout = setTimeout(
                    () => this.resizeCharts(),
                    200
                );
            });
        } catch (error) {
            console.error("Initialization failed:", error);
            this.errorMessage = "Gagal menginisialisasi aplikasi";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },

    // Initialize charts for visible DOM elements
    initializeCharts() {
        const chartConfigs = [
            { id: "lineChart", type: "line", height: 384 },
            { id: "horizontalBarChart", type: "bar", height: 384 },
            { id: "heatmapChart", type: "heatmap", height: 550 },
            { id: "stackedBarChart", type: "bar", height: 384 },
            {
                id: "provHorizontalBarChart_01",
                type: "bar",
                height: 550,
                kd_level: "01",
            },
            {
                id: "provHorizontalBarChart_02",
                type: "bar",
                height: 550,
                kd_level: "02",
            },
            {
                id: "provHorizontalBarChart_03",
                type: "bar",
                height: 550,
                kd_level: "03",
            },
            {
                id: "provHorizontalBarChart_04",
                type: "bar",
                height: 550,
                kd_level: "04",
            },
            {
                id: "provHorizontalBarChart_05",
                type: "bar",
                height: 550,
                kd_level: "05",
            },
            {
                id: "kabkotHorizontalBarChart_01",
                type: "bar",
                height: 550,
                kd_level: "01",
            },
            {
                id: "provinsiChoropleth_01",
                type: "map",
                height: 550,
                kd_level: "01",
            },
            {
                id: "provinsiChoropleth_02",
                type: "map",
                height: 550,
                kd_level: "02",
            },
            {
                id: "provinsiChoropleth_03",
                type: "map",
                height: 550,
                kd_level: "03",
            },
            {
                id: "provinsiChoropleth_04",
                type: "map",
                height: 550,
                kd_level: "04",
            },
            {
                id: "provinsiChoropleth_05",
                type: "map",
                height: 550,
                kd_level: "05",
            },
            {
                id: "kabkotChoropleth_01",
                type: "map",
                height: 550,
                kd_level: "01",
            },
        ];

        const resizeObserver = new ResizeObserver((entries) => {
            entries.forEach((entry) => {
                const chartId = entry.target.id;
                const chart = charts.get(chartId);
                if (chart && !chart.isDisposed()) {
                    chart.resize();
                    // console.log(`Resized ${chartId}`);
                } else {
                    // console.warn(`Chart ${chartId} not found or disposed`);
                }
            });
        });

        charts.forEach((chart, chartId) => {
            chart.dispose();
            charts.delete(chartId);
            console.log(`Disposed chart ${chartId}`);
        });

        chartConfigs.forEach((config) => {
            const chartDiv = document.getElementById(config.id);
            if (chartDiv && chartDiv.offsetParent !== null) {
                const chart = echarts.init(chartDiv);
                charts.set(config.id, chart);
                chart.showLoading({
                    text: "Loading data...",
                    color: "#5470C6",
                    textColor: "#000",
                    maskColor: "rgba(255, 255, 255, 0.8)",
                });
                resizeObserver.observe(chartDiv);
                // console.log(`Initialized ${config.id}`);
            } else if (!chartDiv) {
                // console.log(`Chart element #${config.id} not found in DOM`);
            } else {
                // console.log(`Chart element #${config.id} is hidden`);
            }
        });
    },

    // Resize all charts
    resizeCharts() {
        const paddingX = 32;
        charts.forEach((chart, chartId) => {
            const chartDiv = document.getElementById(chartId);
            if (
                chart &&
                chartDiv &&
                !chart.isDisposed() &&
                chartDiv.offsetParent !== null
            ) {
                const container = chartDiv.parentElement;
                const width = container.clientWidth - paddingX;
                const height = chartDiv.clientHeight;
                if (width > 0 && height > 0) {
                    chart.resize({ width, height });
                    // console.log(`Resized ${chartId}: ${width}x${height}`);
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
            (this.pendingWilayahLevel === "1" ||
                (this.pendingWilayahLevel === "2" && this.kd_wilayah !== "0"))
        );
    },

    // Handle komoditas selection
    selectKomoditas(event) {
        this.selectedKomoditas = event.target.value;
        this.fetchData();
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
        this.selectedKabkot = "";
        this.updateKdWilayah();
    },

    // Update kd_wilayah based on wilayah level
    updateKdWilayah() {
        this.kd_wilayah =
            this.pendingWilayahLevel === "1"
                ? "0"
                : this.selectedProvince || "";
        // console.log("kd_wilayah:", this.kd_wilayah);
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
            this.wilayahLevel = this.pendingWilayahLevel;
            this.data = result.data;

            await new Promise((resolve) => setTimeout(resolve, 100));
            this.initializeCharts();
            this.updateCharts(result.data);
            this.resizeCharts();
        } catch (error) {
            console.error("Fetch data failed:", error);
            // this.errorMessage = "Gagal mengambil data dari server";
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
            this.updateCharts(this.data);
        }
    },

    // Dismiss errors
    dismissErrors() {
        this.errorMessage = "";
        this.errors = [];
    },

    // Update all charts with new data
    updateCharts(data) {
        this.data = data;
        const isNational = this.kd_wilayah === "0";
        const chartKey = "line";

        const levelColorMap = {
            "01": this.colorPalette.HK,
            "02": this.colorPalette.HK_Desa,
            "03": this.colorPalette.HPB,
            "04": this.colorPalette.HPD,
            "05": this.colorPalette.HP,
        };

        // Line Chart
        const lineChart = charts.get("lineChart");
        if (lineChart && data?.chart_data?.[chartKey]) {
            const seriesData = data.chart_data[chartKey].series.map((s) => ({
                name: `${s.name} (${this.showAndil ? "Andil" : "Inflasi"})`,
                type: "line",
                data: this.showAndil ? s.andil : s.inflasi,
                itemStyle: {
                    color: this.colors[s.name] || this.colorPalette.HK,
                },
            }));
            lineChart.setOption({
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
            // console.warn(`Line chart data missing: ${chartKey}`);
        }

        // Horizontal Bar Chart
        const horizontalBarChart = charts.get("horizontalBarChart");
        if (horizontalBarChart && data?.chart_data?.horizontalBar) {
            horizontalBarChart.setOption({
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
                        label: { show: true, position: "right" },
                    },
                    {
                        name: "Andil",
                        type: "bar",
                        data: data.chart_data.horizontalBar.datasets.map(
                            (d) => d.andil[d.andil.length - 1]
                        ),
                        itemStyle: { color: this.colorPalette.HK_Desa },
                        label: { show: true, position: "right" },
                    },
                ],
            });
            horizontalBarChart.hideLoading();
        } else if (horizontalBarChart) {
            horizontalBarChart.showLoading({
                text: "No data available",
                color: "#FD665F",
            });
            // console.warn("Horizontal bar chart data missing");
        }

        // Heatmap Chart
        const heatmapChart = charts.get("heatmapChart");
        if (isNational && heatmapChart && data?.chart_data?.heatmap) {
            heatmapChart.setOption({
                tooltip: {
                    position: "top",
                    formatter: function (params) {
                        const xLabel =
                            data.chart_data.heatmap.xAxis[params.data[0]];
                        const yLabel =
                            data.chart_data.heatmap.yAxis[params.data[1]];
                        const value = params.data[2];
                        return ` ${xLabel}<br>${yLabel}<br>${params.marker} Inflasi: ${value}%`;
                    },
                },

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
                    inverse: true, //  flip the yAxis order
                },
                visualMap: {
                    min: data.chart_data.heatmap.min ?? -5,
                    max: data.chart_data.heatmap.max ?? 5,
                    calculable: true,
                    orient: "horizontal",
                    left: "center",
                    bottom: 0,
                    inRange: { color: this.colorPalette.VisualMap },
                    formatter: function (value) {
                        return value.toFixed(2);
                    },
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
        } else if (heatmapChart) {
            heatmapChart.showLoading({
                text: "No data available",
                color: "#FD665F",
            });
            // console.warn("Heatmap chart data missing");
        }

        // Stacked Bar Chart
        const stackedBarChart = charts.get("stackedBarChart");
        if (isNational && stackedBarChart && data?.chart_data?.stackedBar) {
            const colorMap = {
                "Menurun (<0)": "#91CC75",
                "Stabil (=0)": "#FFCE34",
                "Naik (>0)": "#EE6666",
                "Data tidak tersedia": "#DCDDE2",
            };

            const series = data.chart_data.stackedBar.datasets.map((d) => {
                const label = d.label?.trim();
                return {
                    name: label,
                    type: "bar",
                    stack: "total",
                    data: d.data,
                    itemStyle: {
                        color: colorMap[label] || undefined,
                    },
                };
            });

            stackedBarChart.setOption({
                tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
                toolbox: {
                    feature: {
                        saveAsImage: { title: "Save as PNG" },
                        restore: {},
                    },
                },
                colors: [
                    "#DCDDE2", // Gray – Data tidak tersedia
                    "#EE6666", // Red – Turun (<0)
                    "#FFCE34", // Yellow – Stabil (=0)
                    "#91CC75", // Green – Naik (>0)
                ],
                legend: { bottom: 0 },
                grid: { left: "10%", right: "10%", bottom: "15%", top: "10%" },
                xAxis: {
                    type: "category",
                    data: data.chart_data.stackedBar.labels,
                },
                yAxis: { type: "value", name: "Jumlah Provinsi", max: 38 },
                series: series,
            });
            stackedBarChart.hideLoading();
        } else if (stackedBarChart) {
            stackedBarChart.showLoading({
                text: "No data available",
                color: "#FD665F",
            });
            // console.warn("Stacked bar chart data missing");
        }

        // Province Horizontal Bar Charts (01 to 05)
        [1, 2, 3, 4, 5].forEach((index) => {
            const chartId = `provHorizontalBarChart_0${index}`;
            const kdLevel = `0${index}`;
            const chart = charts.get(chartId);
            if (chart && data?.chart_data?.provHorizontalBar) {
                const provData = data.chart_data.provHorizontalBar.find(
                    (d) => d.kd_level === kdLevel
                );
                if (provData) {
                    chart.setOption({
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
                                itemStyle: { color: levelColorMap[kdLevel] },
                                label: { show: true, position: "right" },
                            },
                        ],
                    });
                    chart.hideLoading();
                } else {
                    chart.showLoading({
                        text: "No data available",
                        color: "#FD665F",
                    });
                    // console.warn(
                    //     `Prov horizontal bar data missing for kd_level ${kdLevel}`
                    // );
                }
            } else if (chart) {
                chart.showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
                // console.warn(
                //     `Prov horizontal bar chart data missing for ${chartId}`
                // );
            }
        });

        // Kabkot Horizontal Bar Chart (only for HK, kd_level 01)
        const kabkotHorizontalBarChart = charts.get(
            "kabkotHorizontalBarChart_01"
        );
        if (kabkotHorizontalBarChart && data?.chart_data?.kabkotHorizontalBar) {
            const kabkotData = data.chart_data.kabkotHorizontalBar.find(
                (d) => d.kd_level === "01"
            );
            if (kabkotData) {
                kabkotHorizontalBarChart.setOption({
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
                    yAxis: { type: "category", data: kabkotData.names },
                    series: [
                        {
                            name: "Inflasi",
                            type: "bar",
                            data: kabkotData.inflasi,
                            itemStyle: { color: this.colorPalette.HK },
                            label: { show: true, position: "right" },

                            emphasis: {
                                normal: { show: false },
                                label: { show: false },
                            },
                        },
                    ],
                });
                kabkotHorizontalBarChart.hideLoading();
            } else {
                kabkotHorizontalBarChart.showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
                // console.warn("Kabkot horizontal bar data missing");
            }
        } else if (kabkotHorizontalBarChart) {
            kabkotHorizontalBarChart.showLoading({
                text: "No data available",
                color: "#FD665F",
            });
            // console.warn("Kabkot horizontal bar chart data missing");
        }

        // Province Choropleth Charts (01 to 05)
        [1, 2, 3, 4, 5].forEach((index) => {
            const chartId = `provinsiChoropleth_0${index}`;
            const kdLevel = `0${index}`;
            const chart = charts.get(chartId);

            if (!chart) {
                return;
            }

            if (
                !data?.chart_data?.provinsiChoropleth ||
                !this.provinsiGeoJson
            ) {
                chart.showLoading({
                    text: "No data or GeoJSON available",
                    color: "#FD665F",
                });
                return;
            }

            const provData = data.chart_data.provinsiChoropleth.find(
                (d) => d.kd_level === kdLevel
            );
            if (!provData) {
                chart.showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
                return;
            }

            // Map GeoJSON features to chart data using raw inflasi
            const provChoroData = this.provinsiGeoJson.features
                .map((feature) => {
                    const regionCode = String(
                        feature.properties.provno
                    ).padStart(2, "0");
                    const index = provData.regions.findIndex(
                        (code) => String(code).padStart(2, "0") === regionCode
                    );

                    if (index === -1) {
                        return null;
                    }

                    return {
                        name: regionCode, // Use provno for chart
                        value: provData.inflasi[index], // Raw inflasi (null or number)
                    };
                })
                .filter((item) => item !== null);

            // Use provData.min/max directly, fallback to -5/5 if both null
            const min = provData.min !== null ? provData.min : -5;
            const max = provData.max !== null ? provData.max : 5;

            // Set chart options
            chart.setOption({
                tooltip: {
                    trigger: "item",
                    formatter: (params) => {
                        const provno = params.name;
                        const provinceName =
                            this.provinsiGeoJson.features.find(
                                (f) =>
                                    String(f.properties.provno).padStart(
                                        2,
                                        "0"
                                    ) === provno
                            )?.properties.provinsi || "Unknown Province";
                        const value =
                            params.value === null || isNaN(params.value)
                                ? "-"
                                : params.value + "%";
                        return `${params.marker} ${provinceName}: ${value}`;
                    },
                },
                toolbox: {
                    feature: {
                        saveAsImage: { title: "Save as PNG" },
                        restore: {},
                    },
                },
                visualMap: {
                    min,
                    max,
                    calculable: true,
                    inRange: { color: this.colorPalette.VisualMap },
                    pieces: [
                        { value: null, label: "N/A", color: "#d3d3d3" },
                        { min, max },
                    ],
                    left: "right",
                    bottom: 10,
                    formatter: (value) => value.toFixed(2),
                },
                series: [
                    {
                        roam: true,
                        zoom: 1,
                        scaleLimit: { min: 1, max: 5 },
                        name: "Inflasi",
                        type: "map",
                        map: "Provinsi_Indonesia",
                        data: provChoroData,
                        nameProperty: "provno",
                        emphasis: {
                            normal: { show: false },
                            label: { show: false },
                        },
                    },
                ],
            });

            chart.hideLoading();
        });

        // Kabkot Choropleth (only for HK, kd_level 01)
        // tried  using the same code as prov, but giving color error, not sure why.
        const kabkotChoropleth = charts.get("kabkotChoropleth_01");
        if (kabkotChoropleth && this.kabkotGeoJson) {
            const kabkotData = data?.chart_data?.kabkotChoropleth?.find(
                (d) => d.kd_level === "01"
            );
            if (kabkotData) {
                // Create a mapping of idkab codes to city/regency names using nmkab
                const codeToNameMap = new Map();
                this.kabkotGeoJson.features.forEach((feature) => {
                    const idkab = String(feature.properties.idkab);
                    const kabkotName = feature.properties.nmkab || "N/A"; // Use nmkab
                    codeToNameMap.set(idkab, kabkotName);
                });

                // Map GeoJSON features to chart data
                const matchLog = [];
                const mismatchLog = [];
                const kabkotChoroData = this.kabkotGeoJson.features
                    .map((feature) => {
                        const regionCode = String(feature.properties.idkab);
                        const index = kabkotData.regions.findIndex(
                            (code) => String(code) === regionCode
                        );

                        if (index === -1) {
                            mismatchLog.push({
                                kd_level: "01",
                                idkab: regionCode,
                                geojson_kabkot:
                                    feature.properties.nmkab || "N/A", // Log nmkab
                            });
                            return null; // Skip unmatched regions
                        }

                        const inflasiValue =
                            kabkotData.inflasi[index] !== null &&
                            !isNaN(kabkotData.inflasi[index]) &&
                            kabkotData.inflasi[index] !== undefined
                                ? Number(kabkotData.inflasi[index])
                                : null;

                        matchLog.push({
                            kd_level: "01",
                            idkab: regionCode,
                            region: kabkotData.regions[index],
                            name: feature.properties.nmkab || "N/A", // Log nmkab
                            inflasi: kabkotData.inflasi[index],
                            convertedInflasi: inflasiValue,
                        });

                        return {
                            name: regionCode, // Use idkab code for chart data
                            value: inflasiValue,
                        };
                    })
                    .filter((item) => item !== null);

                // Calculate min/max for visualMap
                const validInflasi = kabkotData.inflasi
                    .map((val) =>
                        val !== null && !isNaN(val) ? Number(val) : null
                    )
                    .filter((val) => val !== null);
                const min =
                    validInflasi.length > 0 ? Math.min(...validInflasi) : -5;
                const max =
                    validInflasi.length > 0 ? Math.max(...validInflasi) : 5;

                kabkotChoropleth.setOption({
                    toolbox: {
                        feature: {
                            saveAsImage: { title: "Save as PNG" },
                            restore: {},
                        },
                    },
                    tooltip: {
                        trigger: "item",
                        formatter: (params) => {
                            const idkab = params.name; // idkab code from kabkotChoroData
                            const kabkotName =
                                codeToNameMap.get(idkab) || "Unknown Kabkot"; // Use nmkab via codeToNameMap
                            const value =
                                params.value === null || isNaN(params.value)
                                    ? "-"
                                    : params.value + "%";
                            return `${params.marker} ${kabkotName}: ${value}`;
                        },
                    },
                    visualMap: {
                        min: min,
                        max: max,
                        calculable: true,
                        inRange: { color: this.colorPalette.VisualMap },
                        pieces: [
                            { value: null, label: "N/A", color: "#d3d3d3" },
                            { min, max },
                        ],
                        left: "right",
                        bottom: 10,
                    },
                    series: [
                        {
                            roam: true,
                            zoom: 1,
                            scaleLimit: { min: 1, max: 5 },
                            name: "Inflasi",
                            type: "map",
                            map: "Kabkot_Indonesia",
                            data: kabkotChoroData,
                            nameProperty: "idkab",
                            emphasis: {
                                normal: { show: false },
                                label: { show: false },
                            },
                        },
                    ],
                });
                kabkotChoropleth.hideLoading();
            } else {
                kabkotChoropleth.showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
                // console.warn("Kabkot choropleth data missing");
            }
        } else if (kabkotChoropleth) {
            kabkotChoropleth.showLoading({
                text: "No data available",
                color: "#FD665F",
            });
            // console.warn("Kabkot choropleth data, GeoJSON, or level not HK");
        }
    },
}));

Alpine.start();
