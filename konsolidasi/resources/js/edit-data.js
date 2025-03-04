Alpine.data('webData', () => ({
    provinces: [],
    kabkots: [],
    komoditas: [],
    selectedProvince: '', // Now a string (kd_wilayah) or empty
    selectedKabkot: '',
    selectedKomoditas: '',
    selectedKdLevel: '',
    dropdowns: { province: false },
    isPusat: false,
    kd_wilayah: '',

    modalOpen: false,
    item: { id: null, komoditas: '', harga: '', wilayah: '', levelHarga: '', periode: '' },

    async init() {
        try {
            const wilayahResponse = await fetch('/api/wilayah');
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];

            const komoditasResponse = await fetch('/api/komoditas');
            const komoditasData = await komoditasResponse.json();
            this.komoditas = komoditasData.kd_komoditas || [];
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
            this.kd_wilayah = '0'; // National checked = '0'
        } else if (this.selectedKabkot && this.selectedKdLevel === '01') {
            this.kd_wilayah = this.selectedKabkot; // Kabupaten/Kota for HK
        } else if (this.selectedProvince && this.selectedKdLevel === '01') {
            this.kd_wilayah = this.selectedProvince; // Province for HK
        } else {
            this.kd_wilayah = ''; // Default empty
        }
    },

    togglePusat() {
        this.isPusat = !this.isPusat;
        this.updateKdWilayah();
    },

    openModal(id, komoditas, harga, wilayah, levelHarga, periode) {
        this.item = { id, komoditas, harga, wilayah, levelHarga, periode };
        this.modalOpen = true;
    },

    closeModal() {
        this.modalOpen = false;
        this.item = { id: null, komoditas: '', harga: '', wilayah: '', levelHarga: '', periode: '' };
    }
}));
