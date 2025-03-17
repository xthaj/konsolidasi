Alpine.data('webData', () => ({
    loading: true,

    bulan: '',
    tahun: '',
    activeBulan: '', // Store the active bulan
    activeTahun: '', // Store the active tahun
    tahunOptions: [],

    confirmMessage: '',
    confirmDetails: '',
    formEvent: null,

            initDelete(event) {
                this.formEvent = event;
                this.confirmMessage = 'Are you sure you want to delete this data?';
                this.confirmDetails = 'Seluruh rekonsiliasi terkait akan dihapus dan tidak bisa dikembalikan. Untuk memperbarui data, lakukan update/upload.';
                this.$dispatch('open-modal', 'confirm-action');
            },

            executeConfirmAction() {
                if (this.formEvent) {
                    this.$refs.deleteForm.submit();
                }
                this.$dispatch('close');
            },


    get isActivePeriod() {
        return this.bulan === this.activeBulan && this.tahun === this.activeTahun;
    },

    async init() {
        this.loading = true;
        try {
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
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false; // Turn off loading after initialization
        }
    },

    toggleDropdown(menu) {
        this.dropdowns[menu] = !this.dropdowns[menu];
    },

    closeDropdown(menu) {
        this.dropdowns[menu] = false;
    },

}));
