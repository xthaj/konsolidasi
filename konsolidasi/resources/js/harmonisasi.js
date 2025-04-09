import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

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

        return ""; // Shouldn't reach here if checkFormValidity is false
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
        this.updateKdWilayah();
    },

    updateKdWilayah() {
        console.log(
            "isPusat:",
            this.isPusat,
            "selectedProvince:",
            this.selectedProvince
        );
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
            [4, 1, 0],
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
        // console.log("Provinsi GeoJSON:", provinsiGeoJson);
        // console.log("Kabkot GeoJSON:", kabkotGeoJson);
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
        legend: {
            bottom: 0,
            data: stackedLineData.series.map((s) => s.name),
        },
        grid: { left: "3%", right: "4%", bottom: "20%", containLabel: true },
        toolbox: {
            feature: {
                saveAsImage: { title: "Save as PNG" },
                // restore: {},
            },
        },
        xAxis: {
            type: "category",
            data: stackedLineData.xAxis,
        },
        yAxis: { type: "value", name: "Inflasi (%)" },
        series: stackedLineData.series.map((series) => ({
            ...series,
            type: "line",
            stack: "Total",
        })),
    };

    // Horizontal Bar Chart Configuration (Inflasi and Andil)
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
                feature: {
                    saveAsImage: { title: "Save as PNG" },
                    // restore: {},
                },
            },
        },
        legend: { bottom: 0, data: ["Inflasi", "Andil"] },
        grid: {
            containLabel: true,
            left: "5%",
            right: "15%",
        },
        xAxis: { type: "value", name: "Nilai (%)" },
        yAxis: {
            type: "category",
            data: horizontalBarData.datasets.map((dataset) => dataset.label),
            name: "Level Harga",
        },
        series: [
            {
                label: {
                    show: true,
                    position: "right",
                },
                name: "Inflasi",
                type: "bar",
                data: horizontalBarData.datasets.map(
                    (dataset) => dataset.inflasi[dataset.inflasi.length - 1]
                ),
                itemStyle: { color: "#5470C6" },
            },
            {
                label: {
                    show: true,
                    position: "right",
                },
                name: "Andil",
                type: "bar",
                data: horizontalBarData.datasets.map(
                    (dataset) => dataset.andil[dataset.andil.length - 1]
                ),
                itemStyle: { color: "#73C0DE" },
            },
        ],
    };

    const shortenedHargaMap = {
        "Harga Perdagangan Besar": "HPB",
        "Harga Konsumen Kota": "HK",
        "Harga Konsumen Desa": "HK Desa",
        "Harga Produsen Desa": "HP Desa",
        "Harga Produsen": "HP",
    };

    // Heatmap Chart Configuration (Inflasi by Province)
    const heatmapOptions = {
        tooltip: {
            position: "top",
            formatter: function (params) {
                const xValue = heatmapData.xAxis[params.data[0]]; // x-axis value (e.g., level name)
                const yValue = heatmapData.yAxis[params.data[1]]; // y-axis value (e.g., province name)
                const value = params.data[2]; // heatmap value (inflasi)
                const marker = params.marker; // ECharts dot

                return `${xValue}<br>${yValue}<br>${marker} Inflasi: ${
                    value !== undefined ? value : "-"
                }%`;
            },
        },
        toolbox: {
            feature: {
                saveAsImage: { title: "Save as PNG" },
                // restore: {},
            },
        },
        grid: { left: "5%", right: "15%", containLabel: true },
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
            type: "continuous",
            min: -1,
            max: 1,
            precision: 2,
            calculable: true,
            orient: "horizontal",
            left: "center",
            bottom: 0,
            inRange: {
                color: ["#65B581", "#FFCE34", "#FD665F"],
            },
        },
        dataZoom: [
            {
                type: "slider",
                orient: "vertical",
            },
        ],
        series: [
            {
                name: "Inflasi",
                type: "heatmap",
                data: heatmapData.values,
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
            containLabel: true,
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
        yAxis: { type: "value", name: "Jumlah Provinsi" },
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
        grid: {
            left: "5%",
            right: "20%",
            bottom: "10%",
            top: "10%",
            containLabel: true,
        },
        dataZoom: [
            {
                type: "slider",
                orient: "vertical",
            },
        ],
        xAxis: { type: "value", name: "Inflasi (%)" },
        yAxis: { type: "category", data: data.names },
        series: [
            {
                label: {
                    show: true,
                    position: "right",
                },
                name: "Inflasi",
                type: "bar",
                data: data.inflasi,
                itemStyle: { color: "#5470C6" },
            },
        ],
    });

    // Choropleth Options
    function prepareChoroplethData(geoJson, data, mapName) {
        return geoJson.features.map((feature) => {
            const regionCode =
                mapName === "Provinsi_Indonesia"
                    ? feature.properties.KODE_PROV
                    : feature.properties.idkab;

            // Find match in data.regions
            const index = data.regions.findIndex(
                (code) => String(code) === regionCode
            );

            // Only use name from data if it exists, otherwise leave it out
            const regionName = index !== -1 ? data.names[index] : null;

            return {
                name: regionCode, // Code for mapping
                value: index !== -1 ? Number(data.inflasi[index]) : null, // Inflation value
                itemStyle: regionName ? { name: regionName } : {}, // Only add name if it exists
            };
        });
    }

    const choroplethOptions = (mapName, data, title) => ({
        title: { text: title, left: "center" },
        tooltip: {
            trigger: "item",
            formatter: (params) => {
                const name = params.data.itemStyle.name || "-"; // Use name if present, otherwise "-"
                const value = params.value || "No data";
                return `${name} ${params.marker} ${value}`;
            },
        },
        visualMap: {
            left: "right",
            min: -2.5,
            max: 2.5,
            inRange: {
                color: ["#65B581", "#FFCE34", "#FD665F"],
            },
            text: ["High", "Low"],
            calculable: true,
        },
        series: [
            {
                name: "Inflasi",
                type: "map",
                label: {
                    show: false, // Disable any default labels on hover
                },
                map: mapName,
                data: data,
                nameProperty:
                    mapName === "Provinsi_Indonesia" ? "KODE_PROV" : "idkab",
            },
        ],
    });

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

        if (
            updatedHeatmap.xAxis &&
            updatedHeatmap.yAxis &&
            updatedHeatmap.values
        ) {
            heatmapOptions.xAxis.data = updatedHeatmap.xAxis;
            heatmapOptions.yAxis.data = updatedHeatmap.yAxis;
            heatmapOptions.series[0].data = updatedHeatmap.values.map(
                (item) => [item[0], item[1], item[2] || "-"]
            );

            const values = updatedHeatmap.values
                .map((item) => item[2])
                .filter(
                    (value) =>
                        value !== null &&
                        value !== undefined &&
                        value !== "-" &&
                        !isNaN(value)
                );

            const minValue = values.length > 0 ? Math.min(...values) : 0;
            const maxValue = values.length > 0 ? Math.max(...values) : 10;
            const padding = (maxValue - minValue) * 0.1 || 1;

            heatmapOptions.visualMap.min = minValue - padding;
            heatmapOptions.visualMap.max = maxValue + padding;

            heatmapChart.setOption(heatmapOptions, true);
        }
    }

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

    let showingAndil = false;
    toggleAndilBtn.addEventListener("click", () => {
        showingAndil = !showingAndil;
        toggleAndilBtn.textContent = showingAndil
            ? "Lihat Inflasi"
            : "Lihat Andil";

        // Debug to confirm instance
        // console.log("stackedLineChart:", stackedLineChart);
        // console.log(
        //     "setOption available:",
        //     typeof stackedLineChart.setOption === "function"
        // );

        // Prepare updated data
        const updatedSeries = showingAndil
            ? stackedLineData.series.map((s) => ({
                  ...s,
                  data: s.andil || s.data,
              }))
            : stackedLineData.series.map((s) => ({ ...s, data: s.data }));

        // Update options dynamically (like heatmapOptions)
        stackedLineOptions.yAxis.name = showingAndil
            ? "Andil (%)"
            : "Inflasi (%)";
        stackedLineOptions.series = updatedSeries.map((s) => ({
            ...s,
            type: "line",
            stack: "Total",
        }));

        // Apply update (like heatmapChart.setOption)
        stackedLineChart.setOption(stackedLineOptions, true);
    });

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

        // console.log("provData:", provData);

        // Update bar charts with names
        provHorizontalBarChart.setOption(
            horizontalBarOptionsWilayah(provData, "per Provinsi"),
            true
        );
        kabkotHorizontalBarChart.setOption(
            horizontalBarOptionsWilayah(kabkotData, "per Kabupaten/Kota"),
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
                "per Provinsi"
            ),
            true
        );
        kabkotChoropleth.setOption(
            choroplethOptions(
                "Kabkot_Indonesia",
                kabkotChoroData,
                "per Kabupaten/Kota"
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
            HK: 0, // Harga Konsumen Kota
            HD: 1, // Harga Konsumen Desa
            HPB: 2, // Harga Perdagangan Besar
            HPD: 3, // Harga Produsen Desa
            HP: 4, // Harga Produsen
        };

        const selectedLevel = levelMap[levelSelect.value] || 0;
        updateSelectCharts(selectedLevel); // Fixed function name
        resizeCharts();
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
            // Set initial data
            heatmapOptions.xAxis.data = heatmapData.xAxis;
            heatmapOptions.yAxis.data = heatmapData.yAxis;
            heatmapOptions.series[0].data = heatmapData.values.map((item) => [
                item[0],
                item[1],
                item[2],
            ]);

            // Calculate min/max for visualMap
            const values = heatmapData.values.map((item) => item[2]);

            const minValue = values.length > 0 ? Math.min(...values) : 0;
            const maxValue = values.length > 0 ? Math.max(...values) : 10;
            const padding = (maxValue - minValue) * 0.1 || 1;

            heatmapOptions.visualMap.min = minValue - padding;
            heatmapOptions.visualMap.max = maxValue + padding;

            // Apply options to chart
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

    // Expose update functions globally
    window.updateCharts = updateCharts;
    window.updateSelectCharts = updateSelectCharts;

    // Log initial data
    // console.log("Initial Stacked Line Data:", stackedLineData);
    // console.log("Initial Horizontal Bar Data:", horizontalBarData);
    // console.log("Initial Heatmap Data:", heatmapData);
    // console.log("Initial Bar Chart Data:", provHorizontalBarData);
});

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
        if (chartDiv && !charts[config.id]) {
            charts[config.id] = echarts.init(chartDiv);
            console.log(`Initialized ${config.id}`);

            // Optional: Set placeholder options to avoid blank charts
            // if (config.type === "map") {
            //     charts[config.id].setOption({
            //         title: { text: `Loading ${config.id}...`, left: "center" },
            //         series: [{ type: "map", map: config.id === "provinsiChoropleth" ? "Provinsi_Indonesia" : "Kabkot_Indonesia" }],
            //     });
            // }
        }
    });
}

// Resize charts
function resizeCharts() {
    const paddingX = 32; // 16px left + 16px right from p-4

    console.log("Charts object:", charts);

    Object.keys(charts).forEach((chartId) => {
        const chart = charts[chartId];
        const chartDiv = document.getElementById(chartId);
        if (chart && chartDiv && !chart.isDisposed()) {
            const container = chartDiv.parentElement;
            const width = container.clientWidth - paddingX;
            const height = chartDiv.clientHeight; // h-96 (384px) or h-[550px]
            if (width > 0 && height > 0) {
                chart.resize({ width, height });
                console.log(`Resized ${chartId}: ${width}x${height}`);
            }
        }
    });
}

// Initialize on page load
document.addEventListener("DOMContentLoaded", async () => {
    await initializeCharts();
    setTimeout(resizeCharts, 100); // Delay to ensure initial layout is settled

    const toggleAndilBtn = document.getElementById("toggleAndilBtn");
    const levelSelect = document.getElementById("levelHargaSelect");

    // if (toggleAndilBtn) {
    //     let showingAndil = false;
    //     toggleAndilBtn.addEventListener("click", () => {
    //         showingAndil = !showingAndil;
    //         toggleAndilBtn.textContent = showingAndil
    //             ? "Lihat Inflasi"
    //             : "Lihat Andil";
    //         const chart = charts["stackedLineChart"];
    //         const data = window.stackedLineData;
    //         const updatedSeries = showingAndil
    //             ? data.series.map((s) => ({ ...s, data: s.andil || s.data }))
    //             : data.series.map((s) => ({ ...s, data: s.data }));
    //         chart.setOption(
    //             {
    //                 yAxis: { name: showingAndil ? "Andil (%)" : "Inflasi (%)" },
    //                 series: updatedSeries.map((s) => ({
    //                     ...s,
    //                     type: "line",
    //                     stack: "Total",
    //                 })),
    //             },
    //             true
    //         );
    //     });
    // }

    if (levelSelect) {
        levelSelect.addEventListener("change", () => {
            const levelMap = { HK: 0, HD: 1, HPB: 2, HPD: 3, HP: 4 };
            const selectedLevel = levelMap[levelSelect.value] || 0;
            updateSelectCharts(selectedLevel);
            setTimeout(() => {
                resizeCharts(); //  resize after DOM updates
            }, 350);
        });
    }
});

// Resize on window resize
window.addEventListener("resize", () => {
    clearTimeout(window.resizeTimeout);
    window.resizeTimeout = setTimeout(resizeCharts, 350); // Match transition duration-300
});

// Handle Alpine.js sidebar toggle and visibility changes
document.addEventListener("alpine:init", () => {
    Alpine.effect(() => {
        setTimeout(resizeCharts, 350); // Resize after visibility changes (e.g., sidebar or x-show)
    });
});

// Update select charts (for level changes)
function updateSelectCharts(levelIndex) {
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

    const provChart = charts["provHorizontalBarChart"];
    const kabkotChart = charts["kabkotHorizontalBarChart"];
    const provChoroChart = charts["provinsiChoropleth"];
    const kabkotChoroChart = charts["kabkotChoropleth"];

    if (provChart) {
        provChart.setOption(
            {
                yAxis: { data: provData.names },
                series: [{ data: provData.inflasi }],
            },
            true
        );
    }
    if (kabkotChart) {
        kabkotChart.setOption(
            {
                yAxis: { data: kabkotData.names },
                series: [{ data: kabkotData.inflasi }],
            },
            true
        );
    }
    if (provChoroChart) {
        const provChoroData = prepareChoroplethData(
            echarts.getMap("Provinsi_Indonesia").geoJson,
            provData,
            "Provinsi_Indonesia"
        );
        provChoroChart.setOption({ series: [{ data: provChoroData }] }, true);
    }
    if (kabkotChoroChart) {
        const kabkotChoroData = prepareChoroplethData(
            echarts.getMap("Kabkot_Indonesia").geoJson,
            kabkotData,
            "Kabkot_Indonesia"
        );
        kabkotChoroChart.setOption(
            { series: [{ data: kabkotChoroData }] },
            true
        );
    }

    // resizeCharts();
}

window.updateCharts = (stackedLine, horizontalBar, heatmap) => {
    if (stackedLine)
        charts["stackedLineChart"]?.setOption(
            { series: stackedLine.series, xAxis: stackedLine.xAxis },
            true
        );
    if (horizontalBar)
        charts["horizontalBarChart"]?.setOption(
            {
                series: [{ data: horizontalBar.inflasi }],
                yAxis: { data: horizontalBar.labels },
            },
            true
        );
    if (heatmap)
        charts["heatmapChart"]?.setOption(
            {
                series: [{ data: heatmap.values }],
                xAxis: heatmap.xAxis,
                yAxis: heatmap.yAxis,
            },
            true
        );
};
window.updateSelectCharts = updateSelectCharts;
