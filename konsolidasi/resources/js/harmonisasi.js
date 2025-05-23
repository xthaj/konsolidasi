import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

let provinsiGeoJson = null;
let kabkotGeoJson = null;

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
    selectedLevel: "HK",
    isPusat: false,
    kd_wilayah: "",

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
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    checkFormValidity() {
        if (!this.isPusat && !this.selectedProvince) {
            return false;
        }
        return true;
    },

    getValidationMessage() {
        if (!this.isPusat && !this.selectedProvince) {
            return "Pilih Nasional/provinsi";
        }
        return "";
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
                    : "384px";
            fullscreenIcon.textContent = "fullscreen";
        }
    },

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
        this.updateKdWilayah();
    },

    updateKdWilayah() {
        if (this.isPusat) {
            this.kd_wilayah = "0";
        } else if (this.selectedProvince) {
            this.kd_wilayah = this.selectedProvince;
        }
        console.log("kd_wilayah updated to:", this.kd_wilayah);
    },

    togglePusat() {
        this.updateKdWilayah();
    },

    modalOpen: false,
    item: {
        id: null,
        komoditas: "",
        harga: "",
        wilayah: "",
        levelHarga: "",
        periode: "",
    },

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

// Color Palette
const colorPalette = {
    HK: "#5470C6",
    HK_Desa: "#73C0DE",
    HPB: "#8A9A5B",
    HP_Desa: "#9A60B4",
    HP: "#FC8452",
    Deflation: "#EE6666",
    Inflation: "#65B581",
    VisualMap: ["#FD665F", "#FFCE34", "#65B581"],
};

// Object to store chart instances
const charts = {};

// Initialize charts
function initializeCharts() {
    const chartConfigs = [
        { id: "stackedLineChart", type: "line", height: 384 },
        { id: "horizontalBarChart", type: "bar", height: 384 },
        { id: "heatmapChart", type: "heatmap", height: 550 },
        { id: "barChartsContainer", type: "bar", height: 384 },
        { id: "stackedBarChart", type: "bar", height: 384 },
        { id: "provHorizontalBarChart", type: "bar", height: 550 },
        { id: "kabkotHorizontalBarChart", type: "bar", height: 550 },
        { id: "provinsiChoropleth", type: "map", height: 1000 },
        { id: "kabkotChoropleth", type: "map", height: 1000 },
    ];

    chartConfigs.forEach((config) => {
        const chartDiv = document.getElementById(config.id);
        if (chartDiv) {
            charts[config.id] = echarts.init(chartDiv);
            charts[config.id].showLoading({
                text: "Loading data...",
                color: "#5470C6",
                textColor: "#000",
                maskColor: "rgba(255, 255, 255, 0.8)",
            });
            console.log(`Initialized ${config.id} with loading state`);
        } else {
            console.log(
                `Skipped initialization for ${config.id}: div not found`
            );
        }
    });
}

// Resize charts
function resizeCharts() {
    const paddingX = 32; // 16px left + 16px right from p-4
    Object.keys(charts).forEach((chartId) => {
        const chart = charts[chartId];
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
}

// Chart configurations
const shortenedHargaMap = {
    "Harga Konsumen Kota": "HK",
    "Harga Konsumen Desa": "HK Desa",
    "Harga Perdagangan Besar": "HPB",
    "Harga Produsen Desa": "HP Desa",
    "Harga Produsen": "HP",
};

document.addEventListener("DOMContentLoaded", async () => {
    // Define backend data
    const stackedLineData = window.stackedLineData;
    const horizontalBarData = window.horizontalBarData;
    const heatmapData = window.heatmapData;
    const barChartData = window.barChartsData;
    const stackedBarData = window.stackedBarData;
    const provHorizontalBarData = window.provHorizontalBarData;
    const kabkotHorizontalBarData = window.kabkotHorizontalBarData;
    const kdWilayah =
        document.querySelector('input[name="kd_wilayah"]')?.value || "0";

    // Initialize charts
    await initializeCharts();

    // Load GeoJSON data
    let provinsiGeoJson, kabkotGeoJson;
    try {
        const provResponse = await fetch("/geojson/Provinsi.json");
        const kabkotResponse = await fetch("/geojson/kab_indo_dummy4.json");
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
        echarts.registerMap("Provinsi_Indonesia", provinsiGeoJson);
        echarts.registerMap("Kabkot_Indonesia", kabkotGeoJson);
        console.log("GeoJSON Loaded and Maps Registered");
    } catch (error) {
        console.error("Error loading GeoJSON:", error);
        Object.values(charts).forEach((chart) => {
            chart.showLoading({
                text: "Error loading GeoJSON",
                color: "#FD665F",
            });
        });
        return;
    }

    // Stacked Line Chart Configuration
    const stackedLineOptions = {
        tooltip: { trigger: "axis" },
        legend: {
            bottom: 0,
            data: stackedLineData?.series?.map((s) => s.name) || [],
        },
        grid: { left: "3%", right: "4%", bottom: "20%", containLabel: true },
        toolbox: {
            feature: {
                saveAsImage: { title: "Save as PNG" },
                restore: {},
            },
        },
        xAxis: {
            type: "category",
            data: stackedLineData?.xAxis || [],
        },
        yAxis: { type: "value", name: "Inflasi (%)" },
        series:
            stackedLineData?.series?.map((series, index) => ({
                ...series,
                type: "line",
                itemStyle: {
                    color: [
                        colorPalette.HK,
                        colorPalette.HK_Desa,
                        colorPalette.HPB,
                        colorPalette.HP_Desa,
                        colorPalette.HP,
                    ][index % 5],
                },
            })) || [],
    };

    // Horizontal Bar Chart Configuration
    const horizontalBarOptions = {
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
            data:
                horizontalBarData?.datasets?.map((dataset) => dataset.label) ||
                [],
            name: "Level Harga",
        },
        series: [
            {
                label: { show: true, position: "right" },
                name: "Inflasi",
                type: "bar",
                data:
                    horizontalBarData?.datasets?.map(
                        (dataset) => dataset.inflasi[dataset.inflasi.length - 1]
                    ) || [],
                itemStyle: { color: colorPalette.HK },
            },
            {
                label: { show: true, position: "right" },
                name: "Andil",
                type: "bar",
                data:
                    horizontalBarData?.datasets?.map(
                        (dataset) => dataset.andil[dataset.andil.length - 1]
                    ) || [],
                itemStyle: { color: colorPalette.HK_Desa },
            },
        ],
    };

    // Heatmap Chart Configuration
    const heatmapOptions = {
        tooltip: {
            position: "top",
            formatter: function (params) {
                const xValue = heatmapData?.xAxis?.[params.data[0]] || "-";
                // Map full y-axis name to shortened name
                const fullYValue = heatmapData?.yAxis?.[params.data[1]] || "-";
                const yValue = shortenedHargaMap[fullYValue] || fullYValue;
                const value = params.data[2];
                const marker = params.marker;
                return `${xValue}<br>${yValue}<br>${marker} Inflasi: ${
                    value !== undefined ? value : "-"
                }%`;
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
            data: heatmapData?.xAxis || [],
            splitArea: { show: true },
            axisLabel: {
                formatter: function (value) {
                    // Map full name to shortened name
                    return shortenedHargaMap[value] || value;
                },
            },
        },
        yAxis: {
            type: "category",
            // Map full names to shortened names for yAxis
            data:
                heatmapData?.yAxis?.map(
                    (name) => shortenedHargaMap[name] || name
                ) || [],
            splitArea: { show: true },
        },
        visualMap: {
            type: "continuous",
            min: -2.5,
            max: 2.5,
            precision: 2,
            calculable: true,
            orient: "horizontal",
            left: "center",
            bottom: 0,
            inRange: { color: colorPalette.VisualMap },
        },
        dataZoom: [
            { type: "slider", orient: "vertical", handleIcon: "roundRect" },
        ],
        series: [
            {
                name: "Inflasi",
                type: "heatmap",
                data:
                    heatmapData?.values?.map((item) => [
                        item[0],
                        item[1],
                        item[2] || "-",
                    ]) || [],
                label: {
                    show: true,
                    formatter: function (params) {
                        return params.value[2] === 0 ? "0" : params.value[2];
                    },
                },
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowColor: "rgba(0, 0, 0, 0.5)",
                    },
                },
            },
        ],
    };

    // Bar Chart Configuration
    const grids = [];
    const xAxes = [];
    const yAxes = [];
    const series = [];
    const titles = [];
    const columnCount = 5;

    if (barChartData) {
        barChartData.forEach((data, idx) => {
            grids.push({
                show: true,
                borderWidth: 0,
                left: `${(idx / columnCount) * 100 + 2}%`,
                top: "10%",
                width: `${(1 / columnCount) * 100 - 4}%`,
                height: "70%",
                containLabel: true,
            });
            xAxes.push({
                type: "value",
                name: "Inflation (%)",
                gridIndex: idx,
                min: 0,
                max: Math.max(...data.values) * 1.2,
            });
            yAxes.push({
                type: "category",
                data: data.provinces,
                gridIndex: idx,
                axisLabel: { show: idx === 0, interval: 0, rotate: 45 },
            });
            series.push({
                name: data.name,
                type: "bar",
                xAxisIndex: idx,
                yAxisIndex: idx,
                data: data.values,
                itemStyle: { color: colorPalette.HK_Desa },
            });
            titles.push({
                text: data.name,
                textAlign: "center",
                left: `${(idx / columnCount) * 100 + (1 / columnCount) * 50}%`,
                top: "2%",
                textStyle: { fontSize: 12, fontWeight: "normal" },
            });
        });
    }

    const barChartOptions = {
        title: titles,
        grid: grids,
        xAxis: xAxes,
        yAxis: yAxes,
        series: series,
        tooltip: { trigger: "axis" },
        toolbox: {
            feature: {
                saveAsImage: { title: "Save as PNG" },
                restore: {},
            },
        },
    };

    // Stacked Bar Chart Configuration
    const stackedBarOptions = {
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
            data: stackedBarData?.labels || [],
            axisLabel: {
                formatter: function (value) {
                    // Map full name to shortened name
                    return shortenedHargaMap[value] || value;
                },
            },
        },
        yAxis: { type: "value", max: 38, name: "Jumlah Provinsi" },
        series:
            stackedBarData?.datasets?.map((dataset) => ({
                name: dataset.label,
                type: "bar",
                stack: dataset.stack,
                data: dataset.data,
                itemStyle: { color: dataset.backgroundColor },
            })) || [],
    };

    // Choropleth Options
    function prepareChoroplethData(geoJson, data, mapName) {
        return geoJson.features.map((feature) => {
            const regionCode =
                mapName === "Provinsi_Indonesia"
                    ? feature.properties.KODE_PROV
                    : feature.properties.idkab;
            const index = data.regions?.findIndex(
                (code) => String(code) === regionCode
            );
            const regionName = index !== -1 ? data.names[index] : null;
            return {
                name: regionCode,
                value: index !== -1 ? Number(data.inflasi[index]) : null,
                itemStyle: regionName ? { name: regionName } : {},
            };
        });
    }

    const choroplethOptions = (mapName, data, title) => ({
        // title: { text: title, left: "center" },
        toolbox: {
            feature: {
                saveAsImage: { title: "Save as PNG" },
                restore: {},
            },
        },
        tooltip: {
            trigger: "item",
            formatter: (params) => {
                const name = params.data?.itemStyle?.name || "-";
                const value = params.value || "No data";
                return `${name} ${params.marker} ${value}`;
            },
        },
        visualMap: {
            left: "right",
            min: -2.5,
            max: 2.5,
            inRange: { color: colorPalette.VisualMap },
            text: ["High", "Low"],
            calculable: true,
        },
        series: [
            {
                name: "Inflasi",
                type: "map",
                label: { normal: { show: false }, emphasis: { show: false } },
                map: mapName,
                data: data || [],
                nameProperty:
                    mapName === "Provinsi_Indonesia" ? "KODE_PROV" : "idkab",
            },
        ],
    });

    // Render charts
    async function renderCharts() {
        try {
            const isNational = kdWilayah === "0";

            // Stacked Line Chart
            if (
                charts["stackedLineChart"] &&
                stackedLineData &&
                stackedLineData.series &&
                stackedLineData.xAxis
            ) {
                charts["stackedLineChart"].setOption(stackedLineOptions);
                charts["stackedLineChart"].hideLoading();
            } else if (charts["stackedLineChart"]) {
                console.warn("No valid stacked line data provided.");
                charts["stackedLineChart"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }

            // Horizontal Bar Chart
            if (
                horizontalBarData &&
                horizontalBarData.datasets &&
                horizontalBarData.labels
            ) {
                charts["horizontalBarChart"].setOption(horizontalBarOptions);
                charts["horizontalBarChart"].hideLoading();
            } else {
                console.warn("No valid horizontal bar data provided.");
                charts["horizontalBarChart"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }

            // Heatmap Chart (National Only)
            if (
                isNational &&
                charts["heatmapChart"] &&
                heatmapData &&
                heatmapData.xAxis &&
                heatmapData.yAxis &&
                heatmapData.values
            ) {
                // Map full names to shortened names for yAxis
                const shortenedYAxis = heatmapData.yAxis.map(
                    (name) => shortenedHargaMap[name] || name
                );

                heatmapOptions.xAxis.data = heatmapData.xAxis;
                heatmapOptions.yAxis.data = shortenedYAxis; // Use shortened names
                heatmapOptions.series[0].data = heatmapData.values.map(
                    (item) => [item[0], item[1], item[2] || "-"]
                );

                const values = heatmapData.values
                    .map((item) => item[2])
                    .filter(
                        (value) =>
                            value !== null &&
                            value !== undefined &&
                            !isNaN(value)
                    );
                const minValue = values.length > 0 ? Math.min(...values) : 0;
                const maxValue = values.length > 0 ? Math.max(...values) : 10;
                const padding = (maxValue - minValue) * 0.1 || 1;

                heatmapOptions.visualMap.min = minValue - padding;
                heatmapOptions.visualMap.max = maxValue + padding;

                charts["heatmapChart"].setOption(heatmapOptions);
                charts["heatmapChart"].hideLoading();
            }

            // Bar Chart (National Only)
            if (
                isNational &&
                charts["barChartsContainer"] &&
                barChartData &&
                barChartData.length
            ) {
                charts["barChartsContainer"].setOption(barChartOptions);
                charts["barChartsContainer"].hideLoading();
            } else if (charts["barChartsContainer"]) {
                console.warn(
                    "No valid bar chart data provided or not national scope."
                );
                charts["barChartsContainer"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }

            // Stacked Bar Chart (National Only)
            if (
                isNational &&
                charts["stackedBarChart"] &&
                stackedBarData &&
                stackedBarData.labels &&
                stackedBarData.datasets
            ) {
                charts["stackedBarChart"].setOption(stackedBarOptions);
                charts["stackedBarChart"].hideLoading();
            } else if (charts["stackedBarChart"]) {
                console.warn(
                    "No valid stacked bar data provided or not national scope."
                );
                charts["stackedBarChart"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }

            // Province and Kabkot Horizontal Bar Charts
            if (
                charts["provHorizontalBarChart"] &&
                provHorizontalBarData &&
                provHorizontalBarData[0]
            ) {
                charts["provHorizontalBarChart"].setOption(
                    horizontalBarOptionsWilayah(
                        provHorizontalBarData[0],
                        "per Provinsi"
                    )
                );
                charts["provHorizontalBarChart"].hideLoading();
            } else if (charts["provHorizontalBarChart"]) {
                console.warn("No valid province horizontal bar data provided.");
                charts["provHorizontalBarChart"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }

            if (
                charts["kabkotHorizontalBarChart"] &&
                kabkotHorizontalBarData &&
                kabkotHorizontalBarData[0]
            ) {
                charts["kabkotHorizontalBarChart"].setOption(
                    horizontalBarOptionsWilayah(
                        kabkotHorizontalBarData[0],
                        "per Kabupaten/Kota"
                    )
                );
                charts["kabkotHorizontalBarChart"].hideLoading();
            } else if (charts["kabkotHorizontalBarChart"]) {
                console.warn("No valid kabkot horizontal bar data provided.");
                charts["kabkotHorizontalBarChart"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }

            // Choropleth Maps
            if (
                charts["provinsiChoropleth"] &&
                provHorizontalBarData &&
                provHorizontalBarData[0]
            ) {
                const provChoroData = prepareChoroplethData(
                    provinsiGeoJson,
                    provHorizontalBarData[0],
                    "Provinsi_Indonesia"
                );
                charts["provinsiChoropleth"].setOption(
                    choroplethOptions(
                        "Provinsi_Indonesia",
                        provChoroData,
                        "per Provinsi"
                    )
                );
                charts["provinsiChoropleth"].hideLoading();
            } else if (charts["provinsiChoropleth"]) {
                console.warn("No valid province choropleth data provided.");
                charts["provinsiChoropleth"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }

            if (
                charts["kabkotChoropleth"] &&
                kabkotHorizontalBarData &&
                kabkotHorizontalBarData[0]
            ) {
                const kabkotChoroData = prepareChoroplethData(
                    kabkotGeoJson,
                    kabkotHorizontalBarData[0],
                    "Kabkot_Indonesia"
                );
                charts["kabkotChoropleth"].setOption(
                    choroplethOptions(
                        "Kabkot_Indonesia",
                        kabkotChoroData,
                        "per Kabupaten/Kota"
                    )
                );
                charts["kabkotChoropleth"].hideLoading();
            } else if (charts["kabkotChoropleth"]) {
                console.warn("No valid kabkot choropleth data provided.");
                charts["kabkotChoropleth"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }
        } catch (error) {
            console.error("Error rendering charts:", error);
            Object.values(charts).forEach((chart) => {
                chart.showLoading({
                    text: "Error loading data",
                    color: "#FD665F",
                });
            });
        }
    }

    await renderCharts();

    setTimeout(resizeCharts, 100);

    const toggleAndilBtn = document.getElementById("toggleAndilBtn");
    const levelSelect = document.getElementById("levelHargaSelect");

    if (toggleAndilBtn) {
        let showingAndil = false;
        toggleAndilBtn.addEventListener("click", () => {
            showingAndil = !showingAndil;
            toggleAndilBtn.textContent = showingAndil
                ? "Lihat Inflasi"
                : "Lihat Andil";

            if (
                charts["stackedLineChart"] &&
                stackedLineData &&
                stackedLineData.series
            ) {
                const updatedSeries = showingAndil
                    ? stackedLineData.series.map((s) => ({
                          ...s,
                          data: s.andil || s.data,
                      }))
                    : stackedLineData.series.map((s) => ({
                          ...s,
                          data: s.data,
                      }));

                stackedLineOptions.yAxis.name = showingAndil
                    ? "Andil (%)"
                    : "Inflasi (%)";
                stackedLineOptions.series = updatedSeries.map((s, index) => ({
                    ...s,
                    type: "line",
                    itemStyle: {
                        color: [
                            colorPalette.HK,
                            colorPalette.HK_Desa,
                            colorPalette.HPB,
                            colorPalette.HP_Desa,
                            colorPalette.HP,
                        ][index % 5],
                    },
                }));

                charts["stackedLineChart"].setOption(stackedLineOptions, true);
                charts["stackedLineChart"].hideLoading();
            } else if (charts["stackedLineChart"]) {
                charts["stackedLineChart"].showLoading({
                    text: "No data available",
                    color: "#FD665F",
                });
            }
        });
    }

    if (levelSelect) {
        levelSelect.addEventListener("change", () => {
            const levelMap = { HK: 0, HD: 1, HPB: 2, HPD: 3, HP: 4 };
            const selectedLevel = levelMap[levelSelect.value] || 0;
            updateSelectCharts(selectedLevel, levelSelect.value);
            setTimeout(() => {
                resizeCharts();
            }, 350);
        });
    }
});

// Update charts
window.updateCharts = function (
    newStackedLineData,
    newHorizontalBarData,
    newHeatmapData
) {
    const isNational =
        document.querySelector('input[name="kd_wilayah"]')?.value === "0";

    if (
        charts["stackedLineChart"] &&
        newStackedLineData &&
        newStackedLineData.series &&
        newStackedLineData.xAxis
    ) {
        stackedLineOptions.legend.data = newStackedLineData.series.map(
            (s) => s.name
        );
        stackedLineOptions.xAxis.data = newStackedLineData.xAxis;
        stackedLineOptions.series = newStackedLineData.series.map(
            (series, index) => ({
                ...series,
                type: "line",
                itemStyle: {
                    color: [
                        colorPalette.HK,
                        colorPalette.HK_Desa,
                        colorPalette.HPB,
                        colorPalette.HP_Desa,
                        colorPalette.HP,
                    ][index % 5],
                },
            })
        );
        charts["stackedLineChart"].setOption(stackedLineOptions, true);
        charts["stackedLineChart"].hideLoading();
    } else if (charts["stackedLineChart"]) {
        charts["stackedLineChart"].showLoading({
            text: "No stacked line data",
            color: "#FD665F",
        });
    }

    if (
        charts["horizontalBarChart"] &&
        newHorizontalBarData &&
        newHorizontalBarData.datasets &&
        newHorizontalBarData.labels
    ) {
        horizontalBarOptions.yAxis.data = newHorizontalBarData.datasets.map(
            (dataset) => dataset.label
        );
        horizontalBarOptions.series[0].data = newHorizontalBarData.datasets.map(
            (dataset) => dataset.inflasi[dataset.inflasi.length - 1]
        );
        horizontalBarOptions.series[1].data = newHorizontalBarData.datasets.map(
            (dataset) => dataset.andil[dataset.andil.length - 1]
        );
        charts["horizontalBarChart"].setOption(horizontalBarOptions, true);
        charts["horizontalBarChart"].hideLoading();
    } else if (charts["horizontalBarChart"]) {
        charts["horizontalBarChart"].showLoading({
            text: "No horizontal bar data",
            color: "#FD665F",
        });
    }

    if (
        isNational &&
        charts["heatmapChart"] &&
        newHeatmapData &&
        newHeatmapData.xAxis &&
        newHeatmapData.yAxis &&
        newHeatmapData.values
    ) {
        heatmapOptions.xAxis.data = newHeatmapData.xAxis;
        heatmapOptions.yAxis.data = newHeatmapData.yAxis;
        heatmapOptions.series[0].data = newHeatmapData.values.map((item) => [
            item[0],
            item[1],
            item[2] || "-",
        ]);

        const values = newHeatmapData.values
            .map((item) => item[2])
            .filter(
                (value) =>
                    value !== null && value !== undefined && !isNaN(value)
            );
        const minValue = values.length > 0 ? Math.min(...values) : 0;
        const maxValue = values.length > 0 ? Math.max(...values) : 10;
        const padding = (maxValue - minValue) * 0.1 || 1;

        heatmapOptions.visualMap.min = minValue - padding;
        heatmapOptions.visualMap.max = maxValue + padding;

        charts["heatmapChart"].setOption(heatmapOptions, true);
        charts["heatmapChart"].hideLoading();
    } else if (charts["heatmapChart"]) {
        charts["heatmapChart"].showLoading({
            text: "No heatmap data",
            color: "#FD665F",
        });
    }
};

// Update bar charts
window.updateBarCharts = function (newBarChartData) {
    const isNational =
        document.querySelector('input[name="kd_wilayah"]')?.value === "0";

    if (
        isNational &&
        charts["barChartsContainer"] &&
        newBarChartData &&
        newBarChartData.length
    ) {
        newBarChartData.forEach((data, idx) => {
            xAxes[idx].max = Math.max(...data.values) * 1.2;
            yAxes[idx].data = data.provinces;
            series[idx].data = data.values;
            titles[idx].text = data.name;
        });
        charts["barChartsContainer"].setOption({
            title: titles,
            xAxis: xAxes,
            yAxis: yAxes,
            series: series,
        });
        charts["barChartsContainer"].hideLoading();
    } else if (charts["barChartsContainer"]) {
        charts["barChartsContainer"].showLoading({
            text: "No bar chart data",
            color: "#FD665F",
        });
    }
};

// Update select charts
window.updateSelectCharts = function (levelIndex, levelValue) {
    const isNational =
        document.querySelector('input[name="kd_wilayah"]')?.value === "0";
    const provData = window.provHorizontalBarData?.[levelIndex] || {
        regions: [],
        names: [],
        inflasi: [],
    };
    const kabkotData = window.kabkotHorizontalBarData?.[levelIndex] || {
        regions: [],
        names: [],
        inflasi: [],
    };

    // Log data for debugging
    console.log("levelIndex:", levelIndex, "levelValue:", levelValue);
    console.log("provHorizontalBarData:", window.provHorizontalBarData);
    console.log("provData:", provData);
    console.log("kabkotData:", kabkotData);

    // Province Horizontal Bar Chart
    if (charts["provHorizontalBarChart"] && provData.names.length) {
        charts["provHorizontalBarChart"].setOption(
            horizontalBarOptionsWilayah(provData, "per Provinsi"),
            true
        );
        charts["provHorizontalBarChart"].hideLoading();
    } else if (charts["provHorizontalBarChart"]) {
        charts["provHorizontalBarChart"].showLoading({
            text: "No province data",
            color: "#FD665F",
        });
    }

    // Kabkot Horizontal Bar Chart (HK only)
    if (
        levelValue === "HK" &&
        charts["kabkotHorizontalBarChart"] &&
        kabkotData.names.length
    ) {
        charts["kabkotHorizontalBarChart"].setOption(
            horizontalBarOptionsWilayah(kabkotData, "per Kabupaten/Kota"),
            true
        );
        charts["kabkotHorizontalBarChart"].hideLoading();
    } else if (charts["kabkotHorizontalBarChart"]) {
        charts["kabkotHorizontalBarChart"].showLoading({
            text:
                levelValue === "HK"
                    ? "No kabkot data"
                    : "Not applicable for this level",
            color: "#FD665F",
        });
    }

    // Province Choropleth
    if (
        charts["provinsiChoropleth"] &&
        provData.names.length && // Use names to align with horizontal bar
        provData.inflasi.length &&
        provinsiGeoJson
    ) {
        // Fallback for missing regions: derive from names or GeoJSON
        const regions =
            provData.regions && provData.regions.length
                ? provData.regions
                : provinsiGeoJson.features.map((f) => f.properties.KODE_PROV);

        // Ensure regions, names, and inflasi align
        if (
            regions.length !== provData.names.length ||
            regions.length !== provData.inflasi.length
        ) {
            console.warn("Data length mismatch:", {
                regions: regions.length,
                names: provData.names.length,
                inflasi: provData.inflasi.length,
            });
            charts["provinsiChoropleth"].showLoading({
                text: "Data mismatch in regions/names/inflasi",
                color: "#FD665F",
            });
            return;
        }

        const provChoroData = prepareChoroplethData(
            provinsiGeoJson,
            { ...provData, regions },
            "Provinsi_Indonesia"
        );
        charts["provinsiChoropleth"].clear();
        charts["provinsiChoropleth"].setOption(
            choroplethOptions(
                "Provinsi_Indonesia",
                provChoroData,
                "per Provinsi"
            ),
            true
        );
        charts["provinsiChoropleth"].hideLoading();
    } else if (charts["provinsiChoropleth"]) {
        const errorText = !provinsiGeoJson
            ? "GeoJSON not loaded"
            : !provData.names.length || !provData.inflasi.length
            ? "No province choropleth data"
            : "Unknown error";
        charts["provinsiChoropleth"].showLoading({
            text: errorText,
            color: "#FD665F",
        });
        console.error("Choropleth error:", {
            hasGeoJson: !!provinsiGeoJson,
            namesLength: provData.names?.length,
            inflasiLength: provData.inflasi?.length,
            regionsLength: provData.regions?.length,
        });
    }

    // Kabkot Choropleth (HK only)
    if (
        levelValue === "HK" &&
        charts["kabkotChoropleth"] &&
        kabkotData.regions.length &&
        kabkotGeoJson
    ) {
        const kabkotChoroData = prepareChoroplethData(
            kabkotGeoJson,
            kabkotData,
            "Kabkot_Indonesia"
        );
        charts["kabkotChoropleth"].setOption(
            choroplethOptions(
                "Kabkot_Indonesia",
                kabkotChoroData,
                "per Kabupaten/Kota"
            ),
            true
        );
        charts["kabkotChoropleth"].hideLoading();
    } else if (charts["kabkotChoropleth"]) {
        charts["kabkotChoropleth"].showLoading({
            text:
                levelValue === "HK"
                    ? kabkotGeoJson
                        ? "No kabkot choropleth data"
                        : "GeoJSON not loaded"
                    : "Not applicable for this level",
            color: "#FD665F",
        });
    }
};

// Horizontal Bar Chart Options
const horizontalBarOptionsWilayah = (data, title) => ({
    // title: { text: title },
    tooltip: { trigger: "axis", axisPointer: { type: "shadow" } },
    grid: {
        left: "5%",
        right: "20%",
        bottom: "10%",
        top: "10%",
        containLabel: true,
    },
    dataZoom: [{ type: "slider", orient: "vertical", handleIcon: "roundRect" }],
    toolbox: {
        feature: {
            saveAsImage: { title: "Save as PNG" },
            restore: {},
        },
    },
    xAxis: { type: "value", name: "Inflasi (%)" },
    yAxis: { type: "category", data: data.names || [] },
    series: [
        {
            label: { show: true, position: "right" },
            name: "Inflasi",
            type: "bar",
            data: data.inflasi || [],
            itemStyle: { color: colorPalette.HK },
        },
    ],
});

window.updateSelectCharts = updateSelectCharts;

// Resize on window resize
window.addEventListener("resize", () => {
    clearTimeout(window.resizeTimeout);
    window.resizeTimeout = setTimeout(resizeCharts, 350);
});

// Handle Alpine.js sidebar toggle and visibility changes
document.addEventListener("alpine:init", () => {
    Alpine.effect(() => {
        setTimeout(resizeCharts, 350);
    });
});
