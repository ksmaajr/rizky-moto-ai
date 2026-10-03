/*
 * Rizky Moto Shop — Login Alpine Component
 */

window.loginForm = () => ({
    submitting: false,

    async startLogin() {
        if (this.submitting) {
            return;
        }

        this.submitting = true;

        // Beri waktu agar toast "Memproses login" terlihat
        await new Promise(resolve => setTimeout(resolve, 2000));

        try {
            await this.$wire.login();
        } catch (error) {
            this.submitting = false;
            console.error('Login request failed:', error);
        }
    },

    resetLogin() {
        this.submitting = false;
    }
});