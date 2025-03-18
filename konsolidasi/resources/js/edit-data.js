Alpine.data('webData', () => ({
    loading: true,

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
    sortColumn: 'kd_komoditas',
    sortDirection: 'asc',
    deleteRekonsiliasi: false,
    modalData: { id: '', komoditas: '' },
    get isActivePeriod() {
        return this.bulan === this.activeBulan && this.tahun === this.activeTahun;
    },

    async init() {
        this.loading = true;

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
        } finally {
            this.loading = false; // Turn off loading after initialization
        }
    },

    openDeleteModal(id, komoditas) {
        this.modalData = { id, komoditas };
        this.deleteRekonsiliasi = false;
        this.$dispatch('open-modal', 'confirm-delete');
        console.log('Opening modal with:', this.modalData);
    },

    async confirmDelete() {
        if (!this.deleteRekonsiliasi) {
            console.log('Delete aborted: deleteRekonsiliasi is false');
            return;
        }

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const response = await fetch(`/data/delete/${this.modalData.id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ delete_rekonsiliasi: true })
            });

            if (response.ok) {
                console.log('Delete successful, reloading page');
                location.reload();
            } else {
                console.error('Delete failed:', response.status);
                alert('Gagal menghapus');
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Error saat menghapus');
        } finally {
            this.$dispatch('close-modal', 'confirm-delete');
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(k => k.parent_kd === this.selectedProvince);
    },

    checkFormValidity() {

        if (this.selectedKdLevel === 'all') {
            this.isPusat = true;
            this.updateKdWilayah();
            return true;
        }
        return this.isPusat || !!this.selectedProvince;
    },

    getValidationMessage() {
        if (this.selectedKdLevel !== 'all' && !this.isPusat && !this.selectedProvince) {
            return 'Pilih Nasional/provinsi/kabupaten kota untuk level harga ini.';
        }

        return ''; // Shouldn't reach here if checkFormValidity is false
    },

    confirmDelete() {
        if (!this.deleteRekonsiliasi) return;

        fetch(`/data/delete/${this.modalData.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ delete_rekonsiliasi: true })
        })
        .then(response => response.ok ? location.reload() : alert('Gagal menghapus'))
        .catch(error => {
            console.error('Delete error:', error);
            alert('Error saat menghapus');
        })
        .finally(() => this.$dispatch('close-modal', 'confirm-delete'));
    },

    updateKdWilayah() {
        if (this.selectedKdLevel === 'all') {
            this.isPusat = true;
            this.kd_wilayah = '0';
        } else if (this.isPusat) {
            this.kd_wilayah = '0';
        } else if (this.selectedKabkot && this.selectedKdLevel === '01') {
            this.kd_wilayah = this.selectedKabkot;
        } else {
            this.kd_wilayah = this.selectedProvince;
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
