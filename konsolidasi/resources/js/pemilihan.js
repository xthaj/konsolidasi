Alpine.data('webData', () => ({
    provinces: [],
    kabkots: [],
    selectedProvince: {},
    selectedKabkot: '',
    dropdowns: { province: false },
    isPusat: false,
    kd_wilayah: '',
    username: '',
    password: '',
    confirmPassword: '',
    errors: {
        usernameLength: false,
        usernameUnique: false,
        password: false,
        confirmPassword: false,
        kd_wilayah: false,
    },
    usernameExists: false,

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
            this.kd_wilayah = '0';
        } else if (this.selectedKabkot) {
            this.kd_wilayah = this.selectedKabkot;
        } else if (this.selectedProvince.kd_wilayah) {
            this.kd_wilayah = this.selectedProvince.kd_wilayah;
        } else {
            this.kd_wilayah = '';
        }
    },

    togglePusat() {
        this.isPusat = !this.isPusat;
        this.updateKdWilayah();
    },

    async checkUsername() {
        try {
            let response = await fetch(`/api/check-username?username=${this.username}`);
            let data = await response.json();
            this.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    async validateForm() {
        this.errors.usernameLength = this.username.length < 6;
        this.errors.password = this.password.length < 6;
        this.errors.confirmPassword = this.password !== this.confirmPassword;
        this.errors.kd_wilayah = !this.isPusat && !this.selectedProvince.kd_wilayah && !this.selectedKabkot;

        await this.checkUsername();
        this.errors.usernameUnique = this.usernameExists;

        if (!this.errors.usernameLength && !this.errors.usernameUnique && !this.errors.password && !this.errors.confirmPassword && !this.errors.kd_wilayah) {
            if (this.isPusat) {
                this.kd_wilayah = '0';
            }
            this.$el.submit();
        }
    },
}));
