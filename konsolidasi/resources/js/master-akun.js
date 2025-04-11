import "flowbite";
import Alpine from "alpinejs";
window.Alpine = Alpine;

Alpine.data("webData", () => ({
    provinces: [],
    kabkots: [],
    selectedProvince: "",
    selectedKabkot: "",
    kd_wilayah: "",
    loading: true,
    usersData: [],
    wilayah_level: "{{ request('wilayah_level') }}",
    selected_province: "{{ request('kd_wilayah_provinsi') }}",
    selected_kabkot: "{{ request('kd_wilayah') }}",
    newUser: {
        username: "",
        nama_lengkap: "",
        password: "",
        confirmPassword: "",
        is_admin: false,
        kd_wilayah: "0",
        wilayah_level: "pusat",
        selected_province: "",
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
    editUser: {
        user_id: "",
        username: "",
        nama_lengkap: "",
        password: "",
        confirmPassword: "",
        is_admin: false,
        kd_wilayah: "",
        wilayah_level: "pusat",
        selected_province: "",
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
    failDetails: null,
    confirmMessage: "",
    confirmDetails: null,
    confirmAction: null,

    async init() {
        this.loading = true;
        try {
            const wilayahResponse = await fetch("/api/wilayah");
            const wilayahData = await wilayahResponse.json();
            this.provinces = wilayahData.provinces || [];
            this.kabkots = wilayahData.kabkots || [];
            await this.getWilayahUsers();

            this.$watch("$dispatch", (event) => {
                if (event === "close" && this.successMessage) {
                    window.location.reload();
                }
            });
        } catch (error) {
            console.error("Failed to load data:", error);
        } finally {
            this.loading = false;
        }
    },

    async checkEditUserUsername() {
        if (!this.editUser.username) return;
        try {
            let response = await fetch(
                `/api/check-username?username=${this.editUser.username}&except=${this.editUser.user_id}`
            );
            let data = await response.json();
            this.editUser.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    async getWilayahUsers() {
        try {
            const url = new URL("/api/users", window.location.origin);
            if (this.selectedKabkot)
                url.searchParams.append("kd_wilayah", this.selectedKabkot);
            else if (this.selectedProvince)
                url.searchParams.append("kd_wilayah", this.selectedProvince);

            const response = await fetch(url);
            const data = await response.json();
            this.usersData = data.data || [];
        } catch (error) {
            this.failMessage = "Failed to fetch users";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    get filteredKabkots() {
        if (!this.selectedProvince) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.selectedProvince
        );
    },

    get newUserFilteredKabkots() {
        if (!this.newUser.selected_province) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.newUser.selected_province
        );
    },

    get editFilteredKabkots() {
        if (!this.editUser.selected_province) return [];
        return this.kabkots.filter(
            (k) => k.parent_kd === this.editUser.selected_province
        );
    },

    updateKdWilayah() {
        this.kd_wilayah = this.selectedKabkot || this.selectedProvince || "";
    },

    updateNewUserWilayah() {
        if (this.newUser.wilayah_level === "pusat") {
            this.newUser.kd_wilayah = "0";
            this.newUser.selected_province = "";
            this.newUser.selected_kabkot = "";
        } else if (this.newUser.wilayah_level === "provinsi") {
            this.newUser.kd_wilayah = this.newUser.selected_province || "";
            this.newUser.selected_kabkot = "";
        } else if (this.newUser.wilayah_level === "kabkot") {
            this.newUser.kd_wilayah =
                this.newUser.selected_kabkot ||
                this.newUser.selected_province ||
                "";
        }
    },

    updateEditWilayah() {
        if (this.editUser.wilayah_level === "pusat") {
            this.editUser.kd_wilayah = "0";
            this.editUser.selected_province = "";
            this.editUser.selected_kabkot = "";
        } else if (this.editUser.wilayah_level === "provinsi") {
            this.editUser.kd_wilayah = this.editUser.selected_province || "";
            this.editUser.selected_kabkot = "";
        } else if (this.editUser.wilayah_level === "kabkot") {
            this.editUser.kd_wilayah =
                this.editUser.selected_kabkot ||
                this.editUser.selected_province ||
                "";
        }
    },

    openAddUserModal() {
        this.newUser = {
            username: "",
            nama_lengkap: "",
            password: "",
            confirmPassword: "",
            is_admin: false,
            kd_wilayah: "0",
            wilayah_level: "pusat",
            selected_province: "",
            selected_kabkot: "",
            errors: {
                usernameLength: false,
                usernameUnique: false,
                password: false,
                confirmPassword: false,
                kd_wilayah: false,
            },
            usernameExists: false,
        };
        this.$dispatch("open-modal", "add-user");
    },

    async checkNewUserUsername() {
        try {
            let response = await fetch(
                `/api/check-username?username=${this.newUser.username}`
            );
            let data = await response.json();
            this.newUser.usernameExists = data.exists;
        } catch (error) {
            console.error("Failed to check username:", error);
        }
    },

    async addUser() {
        // Validate form
        this.newUser.errors.usernameLength = this.newUser.username.length < 6;
        this.newUser.errors.password = this.newUser.password.length < 6;
        this.newUser.errors.confirmPassword =
            this.newUser.password !== this.newUser.confirmPassword;
        this.newUser.errors.kd_wilayah =
            (this.newUser.wilayah_level === "provinsi" &&
                !this.newUser.selected_province) ||
            (this.newUser.wilayah_level === "kabkot" &&
                !this.newUser.selected_kabkot);

        await this.checkNewUserUsername();
        this.newUser.errors.usernameUnique = this.newUser.usernameExists;

        if (
            this.newUser.errors.usernameLength ||
            this.newUser.errors.usernameUnique ||
            this.newUser.errors.password ||
            this.newUser.errors.confirmPassword ||
            this.newUser.errors.kd_wilayah
        ) {
            return;
        }

        // Prepare data for submission
        const userData = {
            username: this.newUser.username,
            nama_lengkap: this.newUser.nama_lengkap,
            password: this.newUser.password,
            is_admin: this.newUser.is_admin,
            kd_wilayah: this.newUser.kd_wilayah,
            is_pusat: this.newUser.wilayah_level === "pusat",
        };

        try {
            const response = await fetch("/api/users", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
                body: JSON.stringify(userData),
            });

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message || "Failed to add user";
                this.failDetails = data.errors || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            this.usersData.push(data);
            this.successMessage = "Berhasil menambah pengguna!";
            this.$dispatch("open-modal", "success-update-bulan-tahun");
            this.$dispatch("close");
        } catch (error) {
            this.failMessage = "An unexpected error occurred";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    openEditUserModal(user) {
        this.editUser = {
            user_id: user.user_id,
            username: user.username,
            nama_lengkap: user.nama_lengkap,
            password: "",
            confirmPassword: "",
            is_admin: user.is_admin,
            kd_wilayah: user.kd_wilayah || "",
            wilayah_level: user.is_pusat
                ? "pusat"
                : this.kabkots.some((k) => k.kd_wilayah === user.kd_wilayah)
                ? "kabkot"
                : "provinsi",
            selected_province: this.provinces.some(
                (p) => p.kd_wilayah === user.kd_wilayah
            )
                ? user.kd_wilayah
                : this.kabkots.find((k) => k.kd_wilayah === user.kd_wilayah)
                      ?.parent_kd || "",
            selected_kabkot: this.kabkots.some(
                (k) => k.kd_wilayah === user.kd_wilayah
            )
                ? user.kd_wilayah
                : "",
            errors: {
                usernameLength: false,
                usernameUnique: false,
                password: false,
                confirmPassword: false,
                kd_wilayah: false,
            },
            usernameExists: false,
        };
        console.log(
            "is_admin:",
            this.editUser.is_admin,
            typeof this.editUser.is_admin
        );
        this.updateEditWilayah();
        this.$dispatch("open-modal", "edit-user");
    },

    async updateUser() {
        // Validate form
        this.editUser.errors.usernameLength = this.editUser.username.length < 6;
        this.editUser.errors.password =
            this.editUser.password && this.editUser.password.length < 8;
        this.editUser.errors.confirmPassword =
            this.editUser.password &&
            this.editUser.password !== this.editUser.confirmPassword;
        this.editUser.errors.kd_wilayah =
            (this.editUser.wilayah_level === "provinsi" &&
                !this.editUser.selected_province) ||
            (this.editUser.wilayah_level === "kabkot" &&
                !this.editUser.selected_kabkot);

        await this.checkEditUserUsername();
        this.editUser.errors.usernameUnique = this.editUser.usernameExists;

        if (
            this.editUser.errors.usernameLength ||
            this.editUser.errors.usernameUnique ||
            this.editUser.errors.password ||
            this.editUser.errors.confirmPassword ||
            this.editUser.errors.kd_wilayah
        ) {
            return;
        }

        // Prepare data for submission
        const userData = {
            username: this.editUser.username,
            nama_lengkap: this.editUser.nama_lengkap,
            password: this.editUser.password || undefined,
            is_admin: this.editUser.is_admin,
            kd_wilayah: this.editUser.kd_wilayah,
            is_pusat: this.editUser.wilayah_level === "pusat",
        };

        try {
            const response = await fetch(
                `/api/users/${this.editUser.user_id}`,
                {
                    method: "PUT",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                    body: JSON.stringify(userData),
                }
            );

            const data = await response.json();

            if (!response.ok) {
                this.failMessage = data.message || "Failed to update user";
                this.failDetails = data.errors || null;
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
                return;
            }

            const index = this.usersData.findIndex(
                (u) => u.user_id === data.user_id
            );
            if (index !== -1) this.usersData[index] = data;
            this.successMessage = "User successfully updated!";
            this.$dispatch("open-modal", "success-update-bulan-tahun");
            this.$dispatch("close");
        } catch (error) {
            this.failMessage = "An unexpected error occurred";
            this.failDetails = { error: error.message };
            this.$dispatch("open-modal", "fail-update-bulan-tahun");
        }
    },

    deleteUser(user_id, username) {
        this.confirmMessage = `Apakah Anda yakin ingin menghapus pengguna "${username}"?`;
        this.confirmDetails = "Tindakan ini tidak dapat dibatalkan.";
        this.confirmAction = async () => {
            try {
                const response = await fetch(`/api/users/${user_id}`, {
                    method: "DELETE",
                    headers: {
                        Accept: "application/json",
                        "X-CSRF-TOKEN": document.querySelector(
                            'meta[name="csrf-token"]'
                        ).content,
                    },
                });

                const data = await response.json();

                if (!response.ok) {
                    this.failMessage = data.message || "Failed to delete user";
                    this.failDetails = data.errors || null;
                    this.$dispatch("open-modal", "fail-update-bulan-tahun");
                    return;
                }

                this.successMessage = "Berhasil menghapus pengguna!";
                this.$dispatch("open-modal", "success-update-bulan-tahun");
                // Refresh page after success
                setTimeout(() => window.location.reload(), 1000); // Delay to show success modal
            } catch (error) {
                this.failMessage = "An unexpected error occurred";
                this.failDetails = { error: error.message };
                this.$dispatch("open-modal", "fail-update-bulan-tahun");
            }
        };
        this.$dispatch("open-modal", "confirm-action");
    },
}));

Alpine.start();
