export function rmsImageGenerator() {
    return {
        /*
        |--------------------------------------------------------------------------
        | GENERAL STATE
        |--------------------------------------------------------------------------
        */

        historyOpen: true,
        settingsOpen: true,
        activeStep: 1,

        generating: false,

        /*
        |--------------------------------------------------------------------------
        | PREVIEW STATE
        |--------------------------------------------------------------------------
        */

        previewOpen: false,
        previewImage: null,
        previewImageError: false,

        /*
        |--------------------------------------------------------------------------
        | INTERNAL STATE
        |--------------------------------------------------------------------------
        */

        __initialized: false,
        __livewireHookRegistered: false,
        __navigationListenerRegistered: false,

        /*
        |--------------------------------------------------------------------------
        | INIT
        |--------------------------------------------------------------------------
        */

        init() {
            /*
             * Always start with a clean preview state.
             * This prevents an old modal from surviving
             * Livewire navigation.
             */
            this.resetPreview();

            /*
             * Mobile default
             */
            if (window.matchMedia('(max-width: 760px)').matches) {
                this.historyOpen = true;
            }

            /*
             * Initial step synchronization
             */
            this.$nextTick(() => {
                this.syncStep();
            });

            /*
             * Register Livewire morph hook only once.
             */
            this.registerLivewireHook();

            /*
             * Register Livewire navigation listeners only once.
             */
            this.registerNavigationListeners();

            this.__initialized = true;
        },

        /*
        |--------------------------------------------------------------------------
        | LIVEWIRE MORPH
        |--------------------------------------------------------------------------
        */

        registerLivewireHook() {
            if (this.__livewireHookRegistered) {
                return;
            }

            if (!window.Livewire) {
                return;
            }

            this.__livewireHookRegistered = true;

            Livewire.hook('morph.updated', () => {
                /*
                 * IMPORTANT:
                 *
                 * Do NOT reset preview here.
                 * The preview modal is teleported outside
                 * the Livewire DOM.
                 *
                 * Only synchronize the generator step.
                 */
                this.$nextTick(() => {
                    this.syncStep();
                });
            });
        },

        /*
        |--------------------------------------------------------------------------
        | LIVEWIRE NAVIGATION
        |--------------------------------------------------------------------------
        */

        registerNavigationListeners() {
            if (this.__navigationListenerRegistered) {
                return;
            }

            this.__navigationListenerRegistered = true;

            /*
             * Before navigating away:
             * close modal and unlock page.
             */
            document.addEventListener(
                'livewire:navigating',
                () => {
                    this.resetPreview();
                }
            );

            /*
             * After navigation:
             * make sure old preview state cannot survive.
             */
            document.addEventListener(
                'livewire:navigated',
                () => {
                    this.resetPreview();

                    this.$nextTick(() => {
                        this.syncStep();
                    });
                }
            );
        },

        /*
        |--------------------------------------------------------------------------
        | STEP SYNCHRONIZATION
        |--------------------------------------------------------------------------
        */

        syncStep() {
            try {
                const hasTemplate =
                    !!this.$wire.selectedTemplateId;

                const hasImages =
                    !!this.$wire.imageOne;

                if (hasImages) {
                    this.activeStep = 3;
                } else if (hasTemplate) {
                    this.activeStep = 2;
                } else {
                    this.activeStep = 1;
                }
            } catch (error) {
                console.warn(
                    '[Generator] Step synchronization skipped:',
                    error
                );
            }
        },

        /*
        |--------------------------------------------------------------------------
        | MENU
        |--------------------------------------------------------------------------
        */

        closeAllMenus() {
            /*
             * Keep this method intentionally safe.
             *
             * Individual dropdowns should manage their
             * own Alpine state.
             */
        },

        /*
        |--------------------------------------------------------------------------
        | LIVEWIRE VALUE
        |--------------------------------------------------------------------------
        */

        setLivewireValue(property, value) {
            this.$wire.set(property, value);

            this.$nextTick(() => {
                this.syncStep();
            });
        },

        /*
        |--------------------------------------------------------------------------
        | LABEL HELPERS
        |--------------------------------------------------------------------------
        */

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
            return value === 'high'
                ? 'High Quality'
                : 'Standard';
        },

        /*
        |--------------------------------------------------------------------------
        | GENERATE
        |--------------------------------------------------------------------------
        */

        async generateVisual() {
            /*
             * Prevent duplicate clicks.
             */
            if (this.generating) {
                return;
            }

            this.generating = true;
            this.activeStep = 3;

            try {
                /*
                 * Laravel should create the generation record
                 * and dispatch the background job.
                 *
                 * OpenAI processing should NOT block the page.
                 */
                await this.$wire.generate();
            } catch (error) {
                console.error(
                    '[Generator] Could not queue generation:',
                    error
                );
            } finally {
                this.generating = false;

                this.$nextTick(() => {
                    this.syncStep();
                });
            }
        },

        /*
        |--------------------------------------------------------------------------
        | IMAGE URL NORMALIZER
        |--------------------------------------------------------------------------
        */

        normalizeImageUrl(payload) {
            if (!payload) {
                return '';
            }

            /*
             * Prefer backend-provided URL.
             */
            if (
                typeof payload.url === 'string' &&
                payload.url.trim() !== ''
            ) {
                return payload.url;
            }

            /*
             * Fallback to storage path.
             */
            if (
                typeof payload.path === 'string' &&
                payload.path.trim() !== ''
            ) {
                const cleanPath = payload.path
                    .replace(/^\/+/, '');

                return `/storage/${cleanPath}`;
            }

            /*
             * Some records may use image_path.
             */
            if (
                typeof payload.image_path === 'string' &&
                payload.image_path.trim() !== ''
            ) {
                const cleanPath = payload.image_path
                    .replace(/^\/+/, '');

                return `/storage/${cleanPath}`;
            }

            return '';
        },

        /*
        |--------------------------------------------------------------------------
        | OPEN PREVIEW
        |--------------------------------------------------------------------------
        */

        openPreview(image) {
            /*
             * Do nothing if no image object was supplied.
             */
            if (!image || typeof image !== 'object') {
                console.warn(
                    '[Generator] openPreview() called without image data.'
                );

                return;
            }

            /*
             * Normalize payload.
             */
            const payload = {
                ...image
            };

            /*
             * Resolve actual image URL.
             */
            const url = this.normalizeImageUrl(payload);

            /*
             * Reset previous error.
             */
            this.previewImageError = false;

            /*
             * Store image.
             */
            this.previewImage = {
                ...payload,
                url
            };

            /*
             * If there is no URL, show empty state.
             */
            if (!url) {
                this.previewImageError = true;
            }

            /*
             * Open modal AFTER state is ready.
             */
            this.previewOpen = true;

            /*
             * Lock document scrolling.
             */
            this.lockPreviewScroll();

            /*
             * Focus close button after Alpine renders.
             */
            this.$nextTick(() => {
                const closeButton =
                    document.querySelector(
                        '.rms-preview-close'
                    );

                if (closeButton) {
                    closeButton.focus();
                }
            });
        },

        /*
        |--------------------------------------------------------------------------
        | CLOSE PREVIEW
        |--------------------------------------------------------------------------
        */

        closePreview() {
            /*
             * Close FIRST.
             */
            this.previewOpen = false;

            /*
             * Clear preview data.
             */
            this.previewImage = null;

            this.previewImageError = false;

            /*
             * Restore document scroll.
             */
            this.unlockPreviewScroll();
        },

        /*
        |--------------------------------------------------------------------------
        | RESET PREVIEW
        |--------------------------------------------------------------------------
        */

        resetPreview() {
            this.previewOpen = false;

            this.previewImage = null;

            this.previewImageError = false;

            this.unlockPreviewScroll();
        },

        /*
        |--------------------------------------------------------------------------
        | SCROLL LOCK
        |--------------------------------------------------------------------------
        */

        lockPreviewScroll() {
            document.documentElement.classList.add(
                'rms-preview-lock'
            );

            document.body.classList.add(
                'rms-preview-lock'
            );
        },

        unlockPreviewScroll() {
            document.documentElement.classList.remove(
                'rms-preview-lock'
            );

            document.body.classList.remove(
                'rms-preview-lock'
            );
        },

        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD URL
        |--------------------------------------------------------------------------
        */

        downloadUrl(maxMb, quality, format) {
            if (!this.previewImage?.id) {
                return '#';
            }

            const params = new URLSearchParams({
                generatedImage:
                    String(this.previewImage.id),

                max_mb:
                    String(maxMb),

                quality:
                    String(quality),

                format:
                    String(format),
            });

            return `/generated-images/${this.previewImage.id}/download?${params.toString()}`;
        },

        /*
        |--------------------------------------------------------------------------
        | PREVIEW IMAGE ERROR
        |--------------------------------------------------------------------------
        */

        handlePreviewImageError() {
            this.previewImageError = true;
        },
    };
}