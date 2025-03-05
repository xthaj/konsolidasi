// document.addEventListener('alpine:init', () => {
    Alpine.data('webData', () => ({
        provinces: [],
        kabkots: [],
        komoditas: [],
        selectedProvinces: [],
        selectedKabkots: [],
        selectedKomoditas: [],
        dropdowns: { komoditas: false },
        kd_wilayah: '',
        bulan: '',
        tahun: '',
        selectedKdLevel: '',
        selectAllProvincesChecked: false,
        selectAllKabkotsChecked: false,
        selectAllKomoditasChecked: false,
        tableData: [],
        filteredProvinces: [],
        filteredKabkots: [],
        filteredKomoditas: [],
        modalContent: {
            success: false,
            items: [],
            missingItems: []
        },

        async init() {
            try {
                const wilayahResponse = await fetch('/api/wilayah');
                const wilayahData = await wilayahResponse.json();
                this.provinces = wilayahData.provinces || [];
                this.kabkots = wilayahData.kabkots || [];
                this.filteredProvinces = [...this.provinces];
                this.filteredKabkots = [...this.kabkots];

                const komoditasResponse = await fetch('/api/komoditas');
                const komoditasData = await komoditasResponse.json();
                this.komoditas = komoditasData || [];
                this.filteredKomoditas = [...this.komoditas];

                this.bulan = document.querySelector('select[name="bulan"]').value;
                this.tahun = document.querySelector('select[name="tahun"]').value;
                this.selectedKdLevel = document.querySelector('select[name="kd_level"]').value;

                console.log('Initialized:', { bulan: this.bulan, tahun: this.tahun, kd_level: this.selectedKdLevel });
            } catch (error) {
                console.error('Failed to load data:', error);
            }
        },

        async addRow() {
            // Validate single selections for bulan, tahun, and kd_level
            if (!this.bulan || !this.tahun || !this.selectedKdLevel) {
                console.error('Please select bulan, tahun, and a level harga');
                return;
            }

            // Validate at least one Provinsi or Kabupaten/Kota (if kd_level is '01')
            const selectedWilayah = this.selectedKdLevel === '01'
                ? [...this.selectedProvinces, ...this.selectedKabkots]
                : [...this.selectedProvinces];

            if (selectedWilayah.length === 0) {
                this.modalContent = {
                    success: false,
                    items: [],
                    missingItems: []
                };
                this.$dispatch('open-modal', 'error-modal');
                return;
            }

            // Multiple komoditas can be selected
            const komoditasToAdd = this.selectedKomoditas.length > 0
                ? this.komoditas.filter(k => this.selectedKomoditas.includes(k.kd_komoditas))
                : this.komoditas;

            if (komoditasToAdd.length === 0) {
                console.error('Please select at least one komoditas');
                return;
            }

            // Generate combinations with single bulan, tahun, kd_level, but multiple wilayah and komoditas
            const levelHargaMapping = {
                '01': 'Harga Konsumen Kota',
                '02': 'Harga Konsumen Desa',
                '03': 'Harga Perdagangan Besar',
                '04': 'Harga Produsen Desa',
                '05': 'Harga Produsen'
            };
            const levelHargaDisplay = levelHargaMapping[this.selectedKdLevel];

            const combinations = [];
            selectedWilayah.forEach(wilayah => {
                komoditasToAdd.forEach(komoditas => {
                    combinations.push({
                        kd_wilayah: wilayah.kd_wilayah,
                        nama_wilayah: wilayah.nama_wilayah,
                        level_harga: levelHargaDisplay,
                        kd_komoditas: komoditas.kd_komoditas,
                        nama_komoditas: komoditas.nama_komoditas,
                        bulan: this.bulan,
                        tahun: this.tahun
                    });
                });
            });

            // TODO: Limit the number of combinations to prevent excessive requests
            const MAX_COMBINATIONS = 100;
            if (combinations.length > MAX_COMBINATIONS) {
                this.$dispatch('open-modal', 'limit-error');
                return;
            }

            // Batch the inflasi_id requests into a single POST API call
            const requestPayload = combinations.map(combo => ({
                bulan: combo.bulan,
                tahun: combo.tahun,
                kd_level: this.selectedKdLevel,
                kd_wilayah: combo.kd_wilayah,
                kd_komoditas: combo.kd_komoditas,
                nama_wilayah: combo.nama_wilayah,
                level_harga: combo.level_harga,
                nama_komoditas: combo.nama_komoditas
            }));

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/api/inflasi-id', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(requestPayload)
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const results = await response.json();
                console.log('API Results:', results); // Log the results

                const itemsWithInflasiId = [];
                const missingItems = [];

                results.forEach(result => {
                    const combo = combinations.find(c =>
                        c.kd_wilayah === result.kd_wilayah && c.kd_komoditas === result.kd_komoditas
                    );
                    if (result.inflasi_id) {
                        itemsWithInflasiId.push({
                            ...combo,
                            inflasi_id: result.inflasi_id,
                            bulan_tahun_id: result.bulan_tahun_id,
                            harga: result.harga || '0.00'
                        });
                    } else {
                        missingItems.push(`${result.nama_wilayah} - ${result.nama_komoditas} tidak ditemukan`);
                    }
                });

                // Update modal content
                this.modalContent = {
                    success: missingItems.length === 0,
                    items: itemsWithInflasiId,
                    missingItems: missingItems
                };

                console.log('Modal Content:', this.modalContent); // Log the modal content

                // Show the confirmation modal
                this.$dispatch('open-modal', 'confirm-add');
            } catch (error) {
                console.error('Error fetching inflasi_ids:', error);
                this.modalContent = {
                    success: false,
                    items: [],
                    missingItems: ['Error fetching inflasi_ids. Please try again.']
                };
                this.$dispatch('open-modal', 'confirm-add');
            }
        },

        confirmAddToTable() {
            this.tableData = [...this.tableData, ...this.modalContent.items];
            this.$dispatch('close');
            console.log('Table Data Updated:', this.tableData);
        },

        async confirmRekonsiliasi() {
            console.log('tableData before submission:', this.tableData);

            const formData = new FormData();
            this.tableData.forEach(item => {
                formData.append('inflasi_ids[]', item.inflasi_id);
                formData.append('bulan_tahun_ids[]', parseInt(item.bulan_tahun_id, 10));
            });

            console.log('FormData to send:');
            for (let pair of formData.entries()) {
                console.log(`${pair[0]}: ${pair[1]}`);
            }

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch('/rekonsiliasi/confirm', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData // Send as FormData, not JSON
                });

                const result = await response.json();
                console.log('Server response:', result);

                if (result.success) {
                    alert('Rekonsiliasi berhasil!');
                    this.tableData = []; // Clear table after success
                } else {
                    alert('Rekonsiliasi gagal: ' + (result.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error: ' + error.message);
            }
        },

        removeRow(index) {
            this.tableData.splice(index, 1);
        },

        updateKdWilayah() {
            if (this.selectedKdLevel !== '01') {
                this.selectedKabkots = [];
                this.selectAllKabkotsChecked = false;
            }
            if (this.selectedKabkots.length > 0) {
                this.kd_wilayah = this.selectedKabkots[this.selectedKabkots.length - 1].kd_wilayah;
            } else if (this.selectedProvinces.length > 0) {
                this.kd_wilayah = this.selectedProvinces[this.selectedProvinces.length - 1].kd_wilayah;
            } else {
                this.kd_wilayah = '';
            }
        },

        // Provinsi Methods
        selectAllProvinces(checked) {
            if (checked) {
                this.selectedProvinces = [...this.filteredProvinces];
            } else {
                this.selectedProvinces = [];
            }
            this.selectAllProvincesChecked = checked;
            this.updateKdWilayah();
        },

        updateSelectAllProvinces() {
            this.selectAllProvincesChecked = this.selectedProvinces.length === this.filteredProvinces.length;
        },

        toggleProvince(provinsi) {
            const index = this.selectedProvinces.findIndex(p => p.kd_wilayah === provinsi.kd_wilayah);
            if (index === -1) {
                this.selectedProvinces.push(provinsi);
            } else {
                this.selectedProvinces.splice(index, 1);
            }
            this.updateSelectAllProvinces();
            this.updateKdWilayah();
        },

        // Kabupaten/Kota Methods
        selectAllKabkots(checked) {
            if (checked) {
                this.selectedKabkots = [...this.filteredKabkots];
            } else {
                this.selectedKabkots = [];
            }
            this.selectAllKabkotsChecked = checked;
            this.updateKdWilayah();
        },

        updateSelectAllKabkots() {
            this.selectAllKabkotsChecked = this.selectedKabkots.length === this.filteredKabkots.length;
        },

        toggleKabkot(kabkot) {
            const index = this.selectedKabkots.findIndex(k => k.kd_wilayah === kabkot.kd_wilayah);
            if (index === -1) {
                this.selectedKabkots.push(kabkot);
            } else {
                this.selectedKabkots.splice(index, 1);
            }
            this.updateSelectAllKabkots();
            this.updateKdWilayah();
        },

        // Komoditas Methods
        selectAllKomoditas(checked) {
            if (checked) {
                this.selectedKomoditas = this.filteredKomoditas.map(k => k.kd_komoditas);
            } else {
                this.selectedKomoditas = [];
            }
            this.selectAllKomoditasChecked = checked;
        },

        updateSelectAllKomoditas() {
            this.selectAllKomoditasChecked = this.selectedKomoditas.length === this.filteredKomoditas.length;
        },

        searchProvince(query) {
            query = query.toLowerCase();
            this.filteredProvinces = this.provinces.filter(province =>
                province.nama_wilayah.toLowerCase().includes(query)
            );
            this.updateSelectAllProvinces();
        },

        searchKabkot(query) {
            query = query.toLowerCase();
            this.filteredKabkots = this.kabkots.filter(kabkot =>
                kabkot.nama_wilayah.toLowerCase().includes(query)
            );
            this.updateSelectAllKabkots();
        },

        searchKomoditas(query) {
            query = query.toLowerCase();
            this.filteredKomoditas = this.komoditas.filter(komoditas =>
                komoditas.nama_komoditas.toLowerCase().includes(query)
            );
            this.updateSelectAllKomoditas();
        },

        toggleDropdown(menu) {
            this.dropdowns[menu] = !this.dropdowns[menu];
        },

        closeDropdown(menu) {
            this.dropdowns[menu] = false;
        }
    }));
// });
