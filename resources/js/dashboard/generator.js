export function rmsImageGenerator() {
    return {
        historyOpen: true,
        settingsOpen: true,
        activeStep: 1,

        init() {
            // On mobile, keep the History panel hidden on first load.
            // Desktop keeps History open by default.
            if (window.matchMedia('(max-width: 850px)').matches) {
                this.historyOpen = false;
            }

            this.$nextTick(() => {
                this.syncStep();
                if (window.Livewire) {
                    Livewire.hook('morph.updated', () => {
                        this.$nextTick(() => this.syncStep());
                    });
                }
            });
        },

        syncStep() {
            const hasTemplate = !!this.$wire.selectedTemplateId;
            const hasImages = !!this.$wire.imageOne || !!this.$wire.imageTwo;

            if (hasImages) {
                this.activeStep = 3;
            } else if (hasTemplate) {
                this.activeStep = 2;
            } else {
                this.activeStep = 1;
            }
        },

        closeAllMenus() {},

        toggleHistory() {
            this.historyOpen = !this.historyOpen;
            window.dispatchEvent(new CustomEvent('rms-generator-resize'));
        },

        setLivewireValue(property, value) {
            this.$wire.set(property, value);

            if (property === 'imageCount' || property === 'aspectRatio' || property === 'quality') {
                this.$nextTick(() => this.syncStep());
            }
        },

        aspectRatioLabel(value) {
            return {
                '1:1': '1:1 (Square)',
                '4:5': '4:5 (Portrait)',
                '3:4': '3:4 (Portrait)',
                '16:9': '16:9 (Landscape)',
                '9:16': '9:16 (Story)',
            }[value] || value;
        },

        qualityLabel(value) {
            return value === 'high' ? 'High Quality' : 'Standard';
        },

        generateVisual() {
            this.activeStep = 3;

            window.dispatchEvent(new CustomEvent('toast', {
                detail: {
                    type: 'info',
                    title: 'Generator siap',
                    message: 'Visual generation akan kita sambungkan ke OpenAI Image API pada tahap berikutnya.',
                    duration: 3500
                }
            }));
        }
    };
}
