import "flowbite";
import Alpine from "alpinejs";
import collapse from "@alpinejs/collapse";
import * as echarts from "echarts";

window.Alpine = Alpine;

function formatNumber(value) {
    if (value == null) return "";
    return Math.round(Number(value)).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

const charts = new Map();

Alpine.plugin(collapse);

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
        ["Januari", 1], ["Februari", 2], ["Maret", 3], ["April", 4],
        ["Mei", 5], ["Juni", 6], ["Juli", 7], ["Agustus", 8],
        ["September", 9], ["Oktober", 10], ["November", 11], ["Desember", 12],
    ],
    komoditas: [],
    selectedKomoditas: "",
    colorPalette: {
        HK: "#5470C6",
        HK_Desa: "#73C0DE",
        HPB: "#8A9A5B",
        HPD: "#9A60B4",
        HP: "#FC8452",
    },

    async fetchWrapper(url, options = {}, successMessage = "Operasi berhasil", showSuccessModal = false) {
        try {
            const response = await fetch(url, {
                method: "GET",
                ...options,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    ...(options.method && options.method !== "GET"
                        ? { "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content }
                        : {}),
                    ...options.headers,
                },
            });

            const result = await response.json();

            if (!response.ok) {
                this.modalMessage = result.message || "Terjadi kesalahan.";
                this.$dispatch("open-modal", "error-modal");
                throw new Error(this.modalMessage);
            }

            if (showSuccessModal) {
                this.modalMessage = result.message || successMessage;
                this.$dispatch("open-modal", "success-modal");
            }

            return result;
        } catch (error) {
            console.error(`Fetch error at ${url}:`, error);
            this.modalMessage = error?.message || "Terjadi kesalahan.";
            this.$dispatch("open-modal", "error-modal");
            throw error;
        }
    },

    get priceLevels() {
        return [
            "Harga Produsen",
            "Harga Produsen Desa",
            "Harga Perdagangan Besar",
            "Harga Konsumen Kota",
            "Harga Konsumen Desa",
        ];
    },

    get summaryData() {
        return this.data?.chart_data?.summary || {};
    },

    get currentKomoditasIndex() {
        return this.komoditas.findIndex((k) => k.kd_komoditas == this.selectedKomoditas);
    },
    get prevKomoditasName() {
        const i = this.currentKomoditasIndex;
        return i > 0 ? this.komoditas[i - 1].nama_komoditas : null;
    },
    get nextKomoditasName() {
        const i = this.currentKomoditasIndex;
        return i >= 0 && i < this.komoditas.length - 1 ? this.komoditas[i + 1].nama_komoditas : null;
    },

    formatRupiah(value) {
        if (value == null) return "N/A";
        return "Rp" + formatNumber(value);
    },

    get isActivePeriod() {
        return +this.bulan === +this.activeBulan && +this.tahun === +this.activeTahun;
    },

    async init() {
        this.loading = true;
        try {
            const [komoditasResponse, bulanTahunResponse] = await Promise.all([
                this.fetchWrapper("/all-komoditas-harga", {}, "Data komoditas dimuat", false),
                this.fetchWrapper("/bulan-tahun", {}, "Data periode dimuat", false),
            ]);

            this.komoditas = komoditasResponse.data || [];
            if (this.komoditas.length === 0) {
                this.modalMessage = "Belum ada komoditas harga yang terdaftar. Silakan atur komoditas harga di menu Pengaturan.";
                this.$dispatch("open-modal", "error-modal");
            }
            this.selectedKomoditas = this.komoditas[0]?.kd_komoditas ?? "";
            const aktifData = bulanTahunResponse.data?.bt_aktif;
            this.bulan = aktifData?.bulan || "";
            this.tahun = aktifData?.tahun || "";
            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;
            this.tahunOptions = bulanTahunResponse.data?.tahun || (aktifData ? [aktifData.tahun] : []);

            await this.fetchData();
            this.loading = false;

            window.addEventListener("sidebar-toggle", () => this.resizeCharts());
            window.addEventListener("resize", () => this.resizeCharts());
        } catch (error) {
            console.error("Initialization failed:", error);
            this.modalMessage = "Gagal menginisialisasi aplikasi";
            this.$dispatch("open-modal", "error-modal");
        } finally {
            this.loading = false;
        }
    },

    initializeCharts() {
        const chartConfigs = [
            { id: "lineChart", type: "line", height: 384 },
            { id: "provHorizontalBarChart_01", type: "bar", height: 550, kd_level: "01" },
            { id: "provHorizontalBarChart_02", type: "bar", height: 550, kd_level: "02" },
            { id: "provHorizontalBarChart_03", type: "bar", height: 550, kd_level: "03" },
            { id: "provHorizontalBarChart_04", type: "bar", height: 550, kd_level: "04" },
            { id: "provHorizontalBarChart_05", type: "bar", height: 550, kd_level: "05" },
            { id: "kabkotHorizontalBarChart_01", type: "bar", height: 550, kd_level: "01" },
        ];

        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }

        this.resizeObserver = new ResizeObserver((entries) => {
            entries.forEach((entry) => {
                const chartId = entry.target.id;
                const chart = charts.get(chartId);
                if (chart && !chart.isDisposed()) {
                    chart.resize();
                }
            });
        });

        charts.forEach((chart, chartId) => {
            chart.dispose();
            charts.delete(chartId);
        });

        chartConfigs.forEach((config) => {
            const chartDiv = document.getElementById(config.id);
            if (chartDiv) {
                const chart = echarts.init(chartDiv);
                charts.set(config.id, chart);
                chart.showLoading({ text: "Loading data...", color: "#5470C6", textColor: "#000", maskColor: "rgba(255, 255, 255, 0.8)" });
                this.resizeObserver.observe(chartDiv);
            }
        });

        this.resizeCharts();
    },

    resizeCharts() {
        const paddingX = 32;
        charts.forEach((chart, chartId) => {
            const chartDiv = document.getElementById(chartId);
            if (chart && chartDiv && !chart.isDisposed() && chartDiv.offsetParent !== null) {
                chartDiv.style.display = "none";
                chartDiv.offsetHeight;
                chartDiv.style.display = "";
                const container = chartDiv.parentElement;
                const width = container.clientWidth - paddingX;
                const height = chartDiv.clientHeight;
                if (width > 0 && height > 0) {
                    chart.resize({ width, height });
                }
            }
        });
    },

    checkFormValidity() {
        if (!this.bulan || !this.tahun) {
            this.modalMessage = "Harap isi bulan dan tahun.";
            this.$dispatch("open-modal", "error-modal");
            return false;
        }
        return true;
    },

    async fetchData() {
        if (!this.checkFormValidity()) return;
        if (!this.selectedKomoditas && this.selectedKomoditas !== 0) {
            this.modalMessage = "Belum ada komoditas harga yang terdaftar. Silakan atur komoditas harga di menu Pengaturan.";
            this.$dispatch("open-modal", "error-modal");
            return;
        }

        this.errorMessage = "";
        this.errors = [];
        charts.forEach((chart) => chart.showLoading());

        try {
            const params = new URLSearchParams({
                bulan: this.bulan,
                tahun: this.tahun,
                kd_komoditas: this.selectedKomoditas,
            });

            const result = await this.fetchWrapper(`/api/harga?${params}`, {}, "Data harga dimuat", false);

            if (result.data?.errors?.length > 0) {
                this.errors = result.data.errors;
            }

            this.data = result.data;

            await new Promise((resolve) => setTimeout(resolve, 100));
            this.initializeCharts();
            this.updateCharts(result.data);
            this.resizeCharts();
        } catch (error) {
            console.error("Fetch data failed:", error);
            this.errorMessage = this.modalMessage || "Gagal mengambil data";
        } finally {
            charts.forEach((chart) => chart.hideLoading());
        }
    },

    dismissErrors() {
        this.errorMessage = "";
        this.errors = [];
    },

    stepKomoditas(direction) {
        const i = this.currentKomoditasIndex;
        const next = i + direction;
        if (next >= 0 && next < this.komoditas.length) {
            this.selectedKomoditas = this.komoditas[next].kd_komoditas;
        }
    },

    updateCharts(data) {
        this.data = data;

        const levelColorMap = {
            "01": this.colorPalette.HK,
            "02": this.colorPalette.HK_Desa,
            "03": this.colorPalette.HPB,
            "04": this.colorPalette.HPD,
            "05": this.colorPalette.HP,
        };

        // Line Chart - non-cumulative harga
        const lineChart = charts.get("lineChart");
        if (lineChart && data?.chart_data?.line) {
            const rawXAxis = data.chart_data.line.xAxis;

            const desiredOrder = [
                "Harga Produsen",
                "Harga Produsen Desa",
                "Harga Perdagangan Besar",
                "Harga Konsumen Kota",
                "Harga Konsumen Desa",
            ];

            const seriesData = [...data.chart_data.line.series]
                .sort((a, b) => desiredOrder.indexOf(a.name) - desiredOrder.indexOf(b.name))
                .map((s) => ({
                    name: s.name,
                    type: "line",
                    data: s.harga.map((v, i) => ({
                        value: v != null ? parseFloat(v) : null,
                        rentang_bawah: s.rentang_bawah?.[i] != null ? parseFloat(s.rentang_bawah[i]) : null,
                        rentang_atas: s.rentang_atas?.[i] != null ? parseFloat(s.rentang_atas[i]) : null,
                    })),
                    itemStyle: { color: this.colors[s.name] || this.colorPalette.HK },
                }));

            lineChart.setOption(
                {
                    tooltip: {
                        trigger: "axis",
                        confine: true,
                        axisPointer: { type: "cross" },
                        formatter: (params) => {
                            const idx = params[0]?.dataIndex ?? 0;
                            const month = rawXAxis[idx];
                            const rows = params
                                .map((p) => {
                                    const d = p.data;
                                    const value = d?.value != null ? `<b>${formatNumber(d.value)}</b>` : `-`;
                                    const bawah = d?.rentang_bawah != null ? formatNumber(d.rentang_bawah) : null;
                                    const atas = d?.rentang_atas != null ? formatNumber(d.rentang_atas) : null;
                                    let extra = "";
                                    if (bawah !== null || atas !== null) {
                                        extra = `<tr><td></td><td style="padding-left:12px;text-align:right;font-size:0.85em;color:#999;">Bawah: ${bawah ?? '-'} | Atas: ${atas ?? '-'}</td></tr>`;
                                    }
                                    return `<tr><td>${p.marker} ${p.seriesName}</td><td style="padding-left:12px;text-align:right;">${value}</td></tr>${extra}`;
                                })
                                .join("");
                            return `<b>${month}</b><table>${rows}</table>`;
                        },
                    },
                    legend: { bottom: 0, data: seriesData.map((s) => s.name) },
                    grid: { left: "3%", right: "4%", bottom: "20%", containLabel: true },
                    toolbox: {
                        feature: {
                            saveAsImage: { title: "Save as PNG" },
                            restore: {},
                        },
                    },
                    xAxis: { type: "category", data: rawXAxis },
                    yAxis: { type: "value", name: "Harga" },
                    series: seriesData,
                },
                true,
            );

            lineChart.hideLoading();
        } else if (lineChart) {
            lineChart.showLoading({ text: "No data available", color: "#FD665F" });
        }

        // Province Horizontal Bar Charts (01 to 05)
        [1, 2, 3, 4, 5].forEach((index) => {
            const chartId = `provHorizontalBarChart_0${index}`;
            const kdLevel = `0${index}`;
            const chart = charts.get(chartId);
            if (chart && data?.chart_data?.provHorizontalBar) {
                const provData = data.chart_data.provHorizontalBar.find((d) => d.kd_level === kdLevel);
                if (provData) {
                    const barData = (provData.harga || []).map((v, i) => ({
                        value: v != null ? parseFloat(v) : null,
                        rentang_bawah: provData.rentang_bawah?.[i] != null ? parseFloat(provData.rentang_bawah[i]) : null,
                        rentang_atas: provData.rentang_atas?.[i] != null ? parseFloat(provData.rentang_atas[i]) : null,
                    }));
                    chart.setOption({
                        tooltip: {
                            trigger: "axis",
                            axisPointer: { type: "shadow" },
                            formatter: (params) => {
                                const p = params[0];
                                const d = p.data;
                                const value = d?.value != null ? `<b>Rp${formatNumber(d.value)}</b>` : `-`;
                                const bawah = d?.rentang_bawah != null ? formatNumber(d.rentang_bawah) : null;
                                const atas = d?.rentang_atas != null ? formatNumber(d.rentang_atas) : null;
                                let extra = "";
                                if (bawah !== null || atas !== null) {
                                    extra = `<br/><span style="font-size:0.85em;color:#999;">Bawah: ${bawah ?? '-'} | Atas: ${atas ?? '-'}</span>`;
                                }
                                return `<b>${p.name}</b><br/>Harga: ${value}${extra}`;
                            },
                        },
                        toolbox: {
                            feature: {
                                saveAsImage: { title: "Save as PNG" },
                                restore: {},
                            },
                        },
                        grid: { left: "5%", right: "20%", bottom: "10%", top: "10%", containLabel: true },
                        dataZoom: [{ type: "slider", orient: "vertical", handleIcon: "roundRect" }],
                        xAxis: { type: "value", name: "Harga" },
                        yAxis: { type: "category", data: provData.names || [] },
                        series: [{
                            name: "Harga",
                            type: "bar",
                            data: barData,
                            itemStyle: { color: levelColorMap[kdLevel] },
                            label: { show: true, position: "right", formatter: (p) => p.data?.value != null ? "Rp" + formatNumber(p.data.value) : "" },
                        }],
                    });
                    chart.hideLoading();
                } else {
                    chart.showLoading({ text: "No data available", color: "#FD665F" });
                }
            } else if (chart) {
                chart.showLoading({ text: "No data available", color: "#FD665F" });
            }
        });

        // Kabkot Horizontal Bar Chart (HK only, level 01)
        const kabkotChart = charts.get("kabkotHorizontalBarChart_01");
        if (kabkotChart && data?.chart_data?.kabkotHorizontalBar) {
            const kabkotData = data.chart_data.kabkotHorizontalBar.find((d) => d.kd_level === "01");
            if (kabkotData) {
                const kabkotBarData = (kabkotData.harga || []).map((v, i) => ({
                    value: v != null ? parseFloat(v) : null,
                    rentang_bawah: kabkotData.rentang_bawah?.[i] != null ? parseFloat(kabkotData.rentang_bawah[i]) : null,
                    rentang_atas: kabkotData.rentang_atas?.[i] != null ? parseFloat(kabkotData.rentang_atas[i]) : null,
                }));
                kabkotChart.setOption({
                    tooltip: {
                        trigger: "axis",
                        axisPointer: { type: "shadow" },
                        formatter: (params) => {
                            const p = params[0];
                            const d = p.data;
                            const value = d?.value != null ? `<b>Rp${formatNumber(d.value)}</b>` : `-`;
                            const bawah = d?.rentang_bawah != null ? formatNumber(d.rentang_bawah) : null;
                            const atas = d?.rentang_atas != null ? formatNumber(d.rentang_atas) : null;
                            let extra = "";
                            if (bawah !== null || atas !== null) {
                                extra = `<br/><span style="font-size:0.85em;color:#999;">Bawah: ${bawah ?? '-'} | Atas: ${atas ?? '-'}</span>`;
                            }
                            return `<b>${p.name}</b><br/>Harga: ${value}${extra}`;
                        },
                    },
                    toolbox: {
                        feature: {
                            saveAsImage: { title: "Save as PNG" },
                            restore: {},
                        },
                    },
                    grid: { left: "5%", right: "20%", bottom: "10%", top: "10%", containLabel: true },
                    dataZoom: [{ type: "slider", orient: "vertical", handleIcon: "roundRect" }],
                    xAxis: { type: "value", name: "Harga" },
                    yAxis: { type: "category", data: kabkotData.names || [] },
                    series: [{
                        name: "Harga",
                        type: "bar",
                        data: kabkotBarData,
                        itemStyle: { color: this.colorPalette.HK },
                        label: { show: true, position: "right", formatter: (p) => p.data?.value != null ? "Rp" + formatNumber(p.data.value) : "" },
                    }],
                });
                kabkotChart.hideLoading();
            } else {
                kabkotChart.showLoading({ text: "No data available", color: "#FD665F" });
            }
        } else if (kabkotChart) {
            kabkotChart.showLoading({ text: "No data available", color: "#FD665F" });
        }
    },
}));

Alpine.start();
