.rms-generator-preview-modal-custom {
            position: fixed !important;
            inset: 0 !important;
            z-index: 999999 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 20px;
            overflow-y: auto;
            overflow-x: hidden;
            box-sizing: border-box;
            overscroll-behavior: contain;
            background: rgba(15, 15, 18, 0.72);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
        }

        .rms-generator-preview-modal-custom[x-cloak] {
            display: none !important;
        }

        .rms-generator-preview-modal-custom .rms-preview-backdrop {
            position: absolute;
            inset: 0;
            background: transparent;
        }