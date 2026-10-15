/*
 * Rizky Moto Shop — Login Alpine Component
 */

window.loginForm = () => ({
    submitting: false,

    async startLogin() {
        if (this.submitting) return;

        this.submitting = true;

        try {
            // Send immediately; do not add an artificial delay before auth.
            await this.$wire.login();
        } catch (error) {
            // Livewire validation failures are expected; allow retry immediately.
            this.submitting = false;
            if (!(error instanceof Error) || !/validation|422/i.test(error.message)) {
                console.error('Login request failed:', error);
            }
        }
    },

    resetLogin() {
        this.submitting = false;
    }
});