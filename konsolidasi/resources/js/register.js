document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM fully loaded');
    document.addEventListener('alpine:init', () => {
        console.log('alpine:init event fired');
        Alpine.data('registerData', () => ({
            provinces: [],
            kabkots: [],
            selectedProvince: {},
            selectedKabkot: '',
            dropdowns: { province: false },
            isPusat: false,
            kd_wilayah: '',

            async init() {
                try {
                    let response = await fetch('/api/wilayah');
                    let data = await response.json();
                    this.provinces = data.provinces;
                    this.kabkots = data.kabkots;
                } catch (error) {
                    console.error("Failed to load wilayah data:", error);
                }
            },

            get filteredKabkots() {
                if (!this.selectedProvince.kd_wilayah) return [];
                return this.kabkots.filter(k => k.parent_kd == this.selectedProvince.kd_wilayah);
            },

            selectProvince(province) {
                this.selectedProvince = province;
                this.selectedKabkot = '';
                this.closeDropdown('province');
                this.updateKdWilayah();
            },

            toggleDropdown(menu) {
                this.dropdowns[menu] = !this.dropdowns[menu];
            },

            closeDropdown(menu) {
                this.dropdowns[menu] = false;
            },

            updateKdWilayah() {
                if (this.isPusat) {
                    this.kd_wilayah = '1';
                } else if (this.selectedKabkot) {
                    this.kd_wilayah = this.selectedKabkot;
                } else if (this.selectedProvince.kd_wilayah) {
                    this.kd_wilayah = this.selectedProvince.kd_wilayah;
                } else {
                    this.kd_wilayah = '';
                }
            },

            togglePusat() {
                this.updateKdWilayah();
            },
        }));
    });
});
