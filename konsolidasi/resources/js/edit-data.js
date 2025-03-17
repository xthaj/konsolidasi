Alpine.data('webData', () => ({
    bulan: '',
    tahun: '',
    activeBulan: '', // Store the active bulan
    activeTahun: '', // Store the active tahun
    tahunOptions: [],


    provinces: [],
    kabkots: [],
    komoditas: [],
    selectedProvince: '',
    selectedKabkot: '',
    selectedKomoditas: '',
    selectedKdLevel: '',
    isPusat: false,
    kd_wilayah: '',
    item: { id: null, komoditas: '', harga: '', wilayah: '', levelHarga: '' },
    sortColumn: '{{ request(\'sort\', \'kd_komoditas\') }}',
    sortDirection: '{{ request(\'direction\', \'asc\') }}',

    get isActivePeriod() {
        return this.bulan === this.activeBulan && this.tahun === this.activeTahun;
    },

    async init() {
        try {
            const wilayahResponse = await fetch('/api/wilayah');
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];

            const komoditasResponse = await fetch('/api/komoditas');
            const komoditasData = await komoditasResponse.json();
            this.komoditas = komoditasData || [];

            // Fetch Bulan and Tahun
            const bulanTahunResponse = await fetch('/api/bulan_tahun');
            const bulanTahunData = await bulanTahunResponse.json();

            const aktifData = bulanTahunData.bt_aktif; // First active record
            this.bulan = aktifData ? String(aktifData.bulan).padStart(2, '0') : '';
            this.tahun = aktifData ? aktifData.tahun : '';

            this.activeBulan = this.bulan;
            this.activeTahun = this.tahun;

            // Populate tahunOptions, fallback if tahun is missing
            this.tahunOptions = bulanTahunData.tahun || (aktifData ? [aktifData.tahun] : []);
        } catch (error) {
            console.error('Failed to load data:', error);
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(k => k.parent_kd === this.selectedProvince);
    },

    updateKdWilayah() {
        if (this.isPusat) {
            this.kd_wilayah = '0';
        } else if (this.selectedKabkot && this.selectedKdLevel === '01') {
            this.kd_wilayah = this.selectedKabkot;
        } else if (this.selectedProvince && this.selectedKdLevel === '01') {
            this.kd_wilayah = this.selectedProvince;
        } else {
            this.kd_wilayah = '';
        }
    },

    togglePusat() {
        this.isPusat = !this.isPusat;
        this.updateKdWilayah();
    },

    setItem(id, komoditas, harga, wilayah, levelHarga) {
        console.log('Setting item:', { id, komoditas, harga, wilayah, levelHarga });
        this.item = { id, komoditas, harga, wilayah, levelHarga };

        const form = document.querySelector('#edit-harga-form');
        if (form) {
            form.action = `/data/update/${id}`; // Directly set the action
            console.log('Form action updated to:', form.action);
        } else {
            console.error('Edit form not found!');
        }
    }
}));
