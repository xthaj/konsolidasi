import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    provinces: [],
    kabkots: [],
    selectedProvince: "",
    selectedKabkot: "",
    search: "",
    kd_wilayah: "",
    wilayahLevel: "",
    loading: true,
    usersData: [],
    message: "",
    currentPage: 1,
    lastPage: 1,
    totalData: 0,
    wilayah_level: "",
    // selected_province: "{{ request('kd_wilayah_provinsi') }}",
    // selected_kabkot: "{{ request('kd_wilayah') }}",
    newUser: {
        username: "",
        nama_lengkap: "",
        password: "",
        showPassword: false,
        kd_wilayah: "0",
        wilayah_level: "pusat",
        selected_province: "",
        selected_kabkot: "",
        isAdminCheckbox: false,
        level: 1,
        isSSO: false, // Toggle for SSO vs Non-SSO
        searchSSOUsername: "", // Search input for SSO username
        ssoSearchResults: [], 
        isSearching: false,
        errors: {
            username: false,
            nama_lengkap: false,
            password: false,
            kd_wilayah: false,
            level: false,
        },
        usernameExists: false,
    },
    errors: {
        kd_wilayah: false,
    },
    editUser: {
        user_id: "",
        username: "",
        nama_lengkap: "",
        password: "",
        confirmPassword: "",
        kd_wilayah: "",
        wilayah_level: "pusat",
        selected_province: "",
        initial_role_label: "",
        is_admin: false,
        selected_kabkot: "",
        errors: {
            usernameLength: false,
            usernameUnique: false,
            password: false,
            confirmPassword: false,
            kd_wilayah: false,
        },
        usernameExists: false,
    },
    successMessage: "",
    failMessage: "",
    confirmMessage: "",
    modalMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async fetchWrapper(url, options = {}, successMessage = "Operasi berhasil", showSuccessModal = false) {
        try {
            const response = await fetch(url, {
                method: "GET",
                ...options,
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    ...(options.method && options.method !== "GET" ? {
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content
                    } : {}),
                    ...options.headers,
                },
            });
            const result = await response.json();

            if (!response.ok) {
                this.modalMessage = result.message || "Terjadi kesalahan saat memproses permintaan.";
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
            this.modalMessage = error.message || "Terjadi kesalahan saat memproses permintaan.";
            this.$dispatch("open-modal", "error-modal");
            throw error;
        }
    },

    async init() {
        this.loading = true;
        try {
            // ADD HERE: Fetch user data to set initial state
            const [userResponse, wilayahResponse] = await Promise.all([
                this.fetchWrapper("/rekonsiliasi/user-provinsi"),
                this.fetchWrapper("/segmented-wilayah"),
            ]);

            // Process user data
            const userData = userResponse.data;
            this.isPusat = userData.is_pusat;
            this.isProvinsi = userData.is_provinsi;
            this.kd_wilayah = userData.kd_wilayah || "0";
            this.wilayahLevel = userData.wilayah_level;
            if (this.wilayahLevel === "provinsi") {
                this.selectedProvince = userData.kd_wilayah;
            } else if (this.wilayahLevel === "kabkot") {
                this.selectedKabkot = userData.kd_wilayah;
                this.selectedProvince = userData.kd_parent || "";
            } else if (this.isPusat) {
                this.kd_wilayah = "0";
            }

            // Process wilayah data
            this.provinces = wilayahResponse.data.provinces || [];
            this.kabkots = wilayahResponse.data.kabkots || [];
        } catch (error) {
            this.message = "Failed to load wilayah data";
            console.error("Init error:", error);
        } finally {
            this.loading = false;
        }
    },

    getLevelString(level) {
        const levels = {
            0: "Admin Pusat",
            1: "Operator Pusat",
            2: "Admin Provinsi",
            3: "Operator Provinsi",
            4: "Admin Kabupaten/Kota",
            5: "Operator Kabupaten/Kota",
        };
        return levels[parseInt(level)] || "Unknown";
    },

    updateEditWilayah() {
        this.editUser.errors.kd_wilayah = false;
        if (this.editUser.wilayah_level === "pusat") {
            this.editUser.kd_wilayah = "0";
            this.editUser.selected_province = "";
            this.editUser.selected_kabkot = "";
        } else if (this.editUser.wilayah_level === "provinsi") {
            this.editUser.kd_wilayah = this.editUser.selected_province || "";
            this.editUser.selected_kabkot = "";
            if (!this.editUser.kd_wilayah) {
                this.editUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            }
        } else if (this.editUser.wilayah_level === "kabkot") {
            this.editUser.kd_wilayah = this.editUser.selected_kabkot || "";
            if (!this.editUser.selected_province || !this.editUser.selected_kabkot) {
                this.editUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            }
        }
    },

    updateWilayahOptions() {
        // EDIT HERE: Update to validate selections
        this.errors.kd_wilayah = false;
        this.updateKdWilayah();
    },

    updateKdWilayah() {
        this.errors.kd_wilayah = false;
        if (this.wilayahLevel === "pusat") {
            this.kd_wilayah = "0";
            this.selectedProvince = "";
            this.selectedKabkot = "";
        } else if (this.wilayahLevel === "provinsi") {
            this.kd_wilayah = this.selectedProvince || "";
            this.selectedKabkot = "";
            if (!this.kd_wilayah) {
                this.errors.kd_wilayah = "Pilih Provinsi terlebih dahulu.";
            }
        } else if (this.wilayahLevel === "kabkot") {
            if (!this.selectedProvince) {
                this.errors.kd_wilayah = "Pilih Provinsi terlebih dahulu.";
                this.kd_wilayah = "";
                this.selectedKabkot = "";
            } else {
                this.kd_wilayah = this.selectedKabkot || "";
                if (!this.selectedKabkot) {
                    this.errors.kd_wilayah = "Pilih Kabupaten/Kota.";
                }
            }
        }
    },

    async checkEditUserUsername() {
        if (!this.editUser.username) return;
        try {
            const data = await this.fetchWrapper(
                `/api/check-username?username=${this.editUser.username}&except=${this.editUser.user_id}`
            );
            this.editUser.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    checkFormValidity() {
        // EDIT HERE: Add kd_wilayah validation
        if (this.wilayahLevel === "kabkot" && !this.selectedProvince) {
            this.errors.kd_wilayah = "Pilih Provinsi terlebih dahulu.";
            return false;
        }
        return !this.errors.kd_wilayah;
    },

    async getWilayahUsers(isFilterSubmit = false) {
        if (!this.checkFormValidity()) return;

        // // Check if wilayahLevel is empty or kd_wilayah is empty (but not "0")
        if (!this.wilayahLevel || (this.wilayahLevel !== "pusat" && !this.kd_wilayah)) {
            this.message = "Pilih level wilayah dan wilayah yang valid.";
            this.usersData = [];
            return;
        }

        this.message = "";
        // kalau dari filter, reset page. Kalau dari tambah akun, stay di page tsbt
        if (isFilterSubmit) {
            this.currentPage = 1;
        }
        try {
            const params = new URLSearchParams();
            if (this.search) params.append("search", this.search);
            if (this.wilayahLevel) params.append("level_wilayah", this.wilayahLevel);
            if (this.kd_wilayah && this.wilayahLevel !== "pusat") {
                params.append("kd_wilayah", this.kd_wilayah);
            }
            params.append("page", this.currentPage);

            const result = await this.fetchWrapper(`/user?${params.toString()}`);
            this.message = result.message;
            this.usersData = result.data.users;
            this.currentPage = result.data.current_page;
            this.lastPage = result.data.last_page;
            this.totalData = result.data.total;
        } catch (error) {
            this.message = "Terjadi kesalahan saat mengambil data pengguna.";
            this.usersData = [];
        }
    },

    get filteredKabkots() {
        return this.selectedProvince
            ? this.kabkots.filter((k) => k.parent_kd === this.selectedProvince)
            : [];
    },

    get newUserFilteredKabkots() {
        return this.newUser.selected_province
            ? this.kabkots.filter((k) => k.parent_kd === this.newUser.selected_province)
            : [];
    },

    get editFilteredKabkots() {
        return this.editUser.selected_province
            ? this.kabkots.filter((k) => k.parent_kd === this.editUser.selected_province)
            : [];
    },

    get newUserHasErrors() {
        return Object.values(this.newUser.errors).some((error) => error);
    },

    validateNewUserNamaLengkap() {
        if (this.newUser.isSSO) {
            this.newUser.errors.nama_lengkap = !this.newUser.nama_lengkap
                ? "Nama lengkap wajib diisi (pilih dari hasil pencarian SSO)."
                : false;
            return;
        }
        if (!this.newUser.nama_lengkap) {
            this.newUser.errors.nama_lengkap = "Nama lengkap wajib diisi.";
        } else if (this.newUser.nama_lengkap.length > 255) {
            this.newUser.errors.nama_lengkap = "Nama lengkap terlalu panjang.";
        } else {
            this.newUser.errors.nama_lengkap = false;
        }
    },

    async validateNewUserUsername() {
        const regex = /^[a-zA-Z0-9_]+$/;
        if (!this.newUser.username) {
            this.newUser.errors.username = "Username wajib diisi.";
        } else if (this.newUser.username.length < 5) {
            this.newUser.errors.username =
                "Username harus lebih dari 4 karakter.";
        } else if (this.newUser.username.length > 20) {
            this.newUser.errors.username = "Username terlalu panjang.";
        } else if (!regex.test(this.newUser.username)) {
            this.newUser.errors.username =
                "Username hanya boleh berisi huruf, angka, dan underscore.";
        } else {
            await this.checkNewUserUsername();
            this.newUser.errors.username = this.newUser.usernameExists
                ? "Username sudah digunakan."
                : false;
        }
    },

    validateNewUserPassword() {
        if (this.newUser.isSSO) {
            this.newUser.errors.password = false;
            return;
        }
        if (!this.newUser.password) {
            this.newUser.errors.password = "Password wajib diisi.";
        } else if (this.newUser.password.length < 6) {
            this.newUser.errors.password = "Password minimal sepanjang 6 karakter.";
        } else if (this.newUser.password.length > 255) {
            this.newUser.errors.password = "Password terlalu panjang.";
        } else {
            this.newUser.errors.password = false;
        }
    },

    validateNewUserLevel() {
        const validLevels = [0, 1, 2, 3, 4, 5];
        this.newUser.errors.level = !validLevels.includes(this.newUser.level)
            ? "Level pengguna tidak valid."
            : false;
    },

    updateNewUserWilayah() {
        this.newUser.errors.kd_wilayah = false;
        if (this.newUser.wilayah_level === "pusat") {
            this.newUser.kd_wilayah = "0";
            this.newUser.selected_province = "";
            this.newUser.selected_kabkot = "";
        } else if (this.newUser.wilayah_level === "provinsi") {
            this.newUser.kd_wilayah = this.newUser.selected_province || "";
            if (!this.newUser.kd_wilayah) {
                this.newUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            } else if (this.newUser.kd_wilayah.length > 6) {
                this.newUser.errors.kd_wilayah = "Kode wilayah terlalu panjang.";
            }
            this.newUser.selected_kabkot = "";
        } else if (this.newUser.wilayah_level === "kabkot") {
            this.newUser.kd_wilayah = this.newUser.selected_kabkot || "";
            if (!this.newUser.selected_province || !this.newUser.selected_kabkot) {
                this.newUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
            } else if (this.newUser.kd_wilayah.length > 6) {
                this.newUser.errors.kd_wilayah = "Kode wilayah terlalu panjang.";
            }
        }
    },

    updateNewUserLevel() {
        const levelMap = {
            pusat: this.newUser.isAdminCheckbox ? 0 : 1,
            provinsi: this.newUser.isAdminCheckbox ? 2 : 3,
            kabkot: this.newUser.isAdminCheckbox ? 4 : 5,
        };
        this.newUser.level = levelMap[this.newUser.wilayah_level];
        this.validateNewUserLevel();
    },

    openAddUserModal() {
        this.newUser = {
            username: "",
            nama_lengkap: "",
            password: "",
            showPassword: false,
            kd_wilayah: "0",
            wilayah_level: "pusat",
            selected_province: "",
            selected_kabkot: "",
            isAdminCheckbox: false,
            level: 1,
            isSSO: false, // Default to non-SSO
            searchSSOUsername: "", // New: SSO search input
            ssoSearchResults: [], // New: SSO search results
            errors: {
                username: false,
                nama_lengkap: false,
                password: false,
                kd_wilayah: false,
                level: false,
            },
            usernameExists: false,
        };
        this.$dispatch("open-modal", "add-user");
    },

    async checkNewUserUsername() {
        if (!this.newUser.username) return;
        try {
            const data = await this.fetchWrapper(
                `/api/check-username?username=${this.newUser.username}`
            );
            this.newUser.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    async searchSSOUser() {
        if (!this.newUser.searchSSOUsername.trim()) {
            this.newUser.errors.username = "Masukkan username untuk mencari.";
            return;
        }

        this.newUser.isSearching = true;
        this.newUser.errors.username = false;
        try {
            const data = await this.fetchWrapper(
                `/sso/search-username?username=${encodeURIComponent(this.newUser.searchSSOUsername.trim())}`
            );
            // Auto-fill fields on success
            this.newUser.username = data.username;
            this.newUser.nama_lengkap = data.nama_lengkap;
            this.newUser.searchSSOUsername = ""; // Clear search input
        } catch (error) {
            console.error("Failed to search SSO user:", error);
        } finally {
            this.newUser.isSearching = false;
        }
    },

    async addUser() {
        if (!this.newUser.isSSO) {
            this.validateNewUserNamaLengkap();
            this.validateNewUserPassword();
        }
        this.updateNewUserWilayah();
        this.validateNewUserLevel();

        if (this.newUserHasErrors) return;

        // // Check username uniqueness before submission
        await this.checkNewUserUsername();
        if (this.newUser.usernameExists) {
            this.newUser.errors.username = "Username sudah digunakan.";
            return;
        }

        const userData = {
            nama_lengkap: this.newUser.nama_lengkap,
            username: this.newUser.username,
            kd_wilayah: this.newUser.kd_wilayah,
            level: this.newUser.level,
            user_sso: this.newUser.isSSO,
            password: this.newUser.isSSO ? null : this.newUser.password,
        };

        try {
            const data = await this.fetchWrapper("/user", {
                method: "POST",
                body: JSON.stringify(userData),
            }, "Berhasil menambah pengguna!", true);
            
            this.$dispatch("close");
            // Only fetch users if kd_wilayah is not empty
            if (this.wilayahLevel && this.kd_wilayah) {
            await this.getWilayahUsers();
        }
        } catch (error) {
            // Error handled by fetchWrapper
        }
    },

    openEditUserModal(user) {
        this.editUser = {
            user_id: user.user_id,
            username: user.username,
            nama_lengkap: user.nama_lengkap,
            kd_level: user.kd_level,
            level: user.level,
            kd_wilayah: user.kd_wilayah,
            nama_wilayah: user.kd_wilayah === "0" ? "Pusat" : user.nama_wilayah || "Unknown",
            wilayah_level: user.kd_wilayah === "0" ? "pusat" : [2, 3].includes(user.kd_level) ? "provinsi" : "kabkot",
            selected_province: [2, 3].includes(user.kd_level) ? user.kd_wilayah : "",
            selected_kabkot: [4, 5].includes(user.kd_level) ? user.kd_wilayah : "",
            password: "",
            confirmPassword: "",
            initial_role_label: [0, 2, 4].includes(parseInt(user.kd_level)) ? "Ganti menjadi Operator" : "Ganti menjadi Admin",
            role_toggle: false,
            errors: {},
            usernameExists: false,
        };
        this.$dispatch("open-modal", "edit-user");
    },

    async updateUserAttribute(attribute) {
        this.editUser.errors = {};

        // Validate input
        if (attribute === "username") {
            if (this.editUser.username.length < 5) {
                this.editUser.errors.usernameLength = "Username harus lebih dari 4 karakter.";
                return;
            }
            await this.checkEditUserUsername();
            if (this.editUser.usernameExists) {
                this.editUser.errors.usernameUnique = "Username sudah digunakan.";
                return;
            }
        } else if (attribute === "password") {
            if (this.editUser.password && this.editUser.password.length < 6) {
                this.editUser.errors.password = "Password minimal sepanjang 6 karakter.";
                return;
            }
            if (this.editUser.password !== this.editUser.confirmPassword) {
                this.editUser.errors.confirmPassword = "Password dan konfirmasi password berbeda.";
                return;
            }
        } else if (attribute === "wilayah") {
            if (this.editUser.wilayah_level !== "pusat" && !this.editUser.kd_wilayah) {
                this.editUser.errors.kd_wilayah = "Satuan kerja belum dipilih.";
                return;
            }
        }

        try {
            const payload = {};
            if (attribute === "username") {
                payload.username = this.editUser.username;
            } else if (attribute === "nama_lengkap") {
                payload.nama_lengkap = this.editUser.nama_lengkap;
            } else if (attribute === "password") {
                if (!this.editUser.password) return;
                if (this.editUser.user_id) payload.user_id = this.editUser.user_id;
                payload.password = this.editUser.password;
                payload.password_confirmation = this.editUser.confirmPassword;
            } else if (attribute === "role") {
                const currentLevel = parseInt(this.editUser.kd_level);
                const roleMap = {
                    0: this.editUser.role_toggle ? 1 : 0,
                    1: this.editUser.role_toggle ? 0 : 1,
                    2: this.editUser.role_toggle ? 3 : 2,
                    3: this.editUser.role_toggle ? 2 : 3,
                    4: this.editUser.role_toggle ? 5 : 4,
                    5: this.editUser.role_toggle ? 4 : 5,
                };
                payload.level = roleMap[currentLevel];
            } else if (attribute === "wilayah") {
                payload.kd_wilayah = this.editUser.wilayah_level === "pusat" ? "0" : this.editUser.kd_wilayah;
                const currentLevel = parseInt(this.editUser.kd_level);
                const isAdmin = [0, 2, 4].includes(currentLevel);
                const levelMap = {
                    pusat: isAdmin ? 0 : 1,
                    provinsi: isAdmin ? 2 : 3,
                    kabkot: isAdmin ? 4 : 5,
                };
                payload.level = levelMap[this.editUser.wilayah_level];
            }

            const url = attribute === "password" ? "/profile/password" : `/user/${this.editUser.user_id}`;
            const method = attribute === "password" ? "POST" : "PUT";

            await this.fetchWrapper(url, {
                method,
                body: JSON.stringify(payload),
            }, `Data ${attribute} berhasil diperbarui`, true);

            if (attribute === "password") {
                this.editUser.password = "";
                this.editUser.confirmPassword = "";
            } else {
                await this.getWilayahUsers();
                if (attribute === "wilayah") {
                    this.editUser.nama_wilayah = this.editUser.wilayah_level === "pusat"
                        ? "Pusat"
                        : this.editUser.wilayah_level === "provinsi"
                        ? this.provinces.find((p) => p.kd_wilayah === this.editUser.selected_province)?.nama_wilayah || "Unknown"
                        : this.editFilteredKabkots.find((k) => k.kd_wilayah === this.editUser.selected_kabkot)?.nama_wilayah || "Unknown";
                }
                if (payload.level !== undefined) {
                    this.editUser.kd_level = payload.level;
                    this.editUser.level = this.getLevelString(payload.level);
                    this.editUser.initial_role_label = [0, 2, 4].includes(payload.level)
                        ? "Ganti menjadi Operator"
                        : "Ganti menjadi Admin";
                    this.editUser.role_toggle = false;
                }
            }
        } catch (error) {
            // Error handled by fetchWrapper
        }
    },

    async deleteUser(user_id, username) {
        this.confirmMessage = `Apakah Anda yakin ingin menghapus pengguna "${username}"?`;
        this.confirmDetails = "Tindakan ini tidak dapat dibatalkan.";
        this.confirmAction = async () => {
            try {
                await this.fetchWrapper(`/user/${user_id}`, {
                    method: "DELETE",
                }, `Pengguna "${username}" berhasil dihapus`, true);
                await this.getWilayahUsers();
            } catch (error) {
                // Error handled by fetchWrapper
            }
        };
        this.$dispatch("open-modal", "confirm-action");
    },
}));

Alpine.start();