<section class="rms-nvidia-card" wire:key="nvidia-provider-playground">
    <style>
        .rms-nvidia-card{display:grid;gap:18px;margin:0 0 22px;color:#18181b;min-width:0}
        .rms-nvidia-hero{position:relative;overflow:hidden;display:flex;align-items:flex-start;justify-content:space-between;gap:18px;padding:24px;border-radius:18px;background:radial-gradient(circle at 88% 0%,#76b90038,transparent 34%),linear-gradient(125deg,#111827,#202124);color:#fff}
        .rms-nvidia-hero:after{content:"";position:absolute;right:8%;top:-90px;width:210px;height:210px;border:1px solid #ffffff20;border-radius:50%;box-shadow:0 0 0 22px #ffffff08,0 0 0 44px #ffffff04;pointer-events:none}
        .rms-nvidia-hero-copy{position:relative;z-index:1;min-width:0}
        .rms-nvidia-kicker{display:block;color:#a3d447;font-size:9px;font-weight:900;letter-spacing:.16em}
        .rms-nvidia-hero h3{margin:8px 0 6px;color:#fff;font-size:clamp(21px,2.4vw,28px);font-weight:850;letter-spacing:-.045em}
        .rms-nvidia-hero p{max-width:660px;margin:0;color:#c5c8cf;font-size:11px;line-height:1.7}
        .rms-nvidia-status{position:relative;z-index:1;display:inline-flex;align-items:center;gap:7px;flex:0 0 auto;padding:8px 10px;border:1px solid #ffffff22;border-radius:999px;background:#ffffff08;color:#e4e4e7;font-size:9px;font-weight:850}
        .rms-nvidia-status i{width:6px;height:6px;border-radius:50%;background:#a1a1aa}
        .rms-nvidia-status.is-ready i{background:#84cc16;box-shadow:0 0 0 4px #84cc1620}
        .rms-nvidia-status.is-error i{background:#f87171;box-shadow:0 0 0 4px #f8717120}
        .rms-nvidia-panel{padding:20px;border:1px solid #e4e4e7;border-radius:16px;background:#fff;box-shadow:0 8px 24px #18181b05;min-width:0}
        .rms-nvidia-panel-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:16px}
        .rms-nvidia-panel-head h4{margin:0;color:#18181b;font-size:14px;font-weight:850;letter-spacing:-.025em}
        .rms-nvidia-panel-head p{margin:5px 0 0;color:#71717a;font-size:10px;line-height:1.6}
        .rms-nvidia-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .rms-nvidia-field{display:grid;gap:7px;min-width:0}
        .rms-nvidia-field-full{grid-column:1/-1}
        .rms-nvidia-field label{color:#52525b;font-size:10px;font-weight:800}
        .rms-nvidia-field input,.rms-nvidia-field select,.rms-nvidia-field textarea{display:block;width:100%;min-width:0;border:1px solid #e4e4e7;border-radius:10px;background:#fff;padding:11px 12px;color:#27272a;font:inherit;font-size:11px;outline:none;transition:border-color .15s,box-shadow .15s}
        .rms-nvidia-field input:focus,.rms-nvidia-field select:focus,.rms-nvidia-field textarea:focus{border-color:#84a83d;box-shadow:0 0 0 3px #84b82a18}
        .rms-nvidia-field textarea{min-height:135px;resize:vertical;line-height:1.65}
        .rms-nvidia-hint{color:#a1a1aa;font-size:9px;line-height:1.55}
        .rms-nvidia-key-row{display:flex;align-items:center;gap:9px;min-width:0}
        .rms-nvidia-key-row input{flex:1}
        .rms-nvidia-masked{padding:11px 12px;border:1px solid #e4e4e7;border-radius:10px;background:#fafafa;color:#52525b;font-family:ui-monospace,monospace;font-size:10px;overflow-wrap:anywhere}
        .rms-nvidia-actions{display:flex;align-items:center;flex-wrap:wrap;gap:9px;margin-top:16px}
        .rms-nvidia-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:38px;padding:9px 13px;border:1px solid #e4e4e7;border-radius:10px;background:#fff;color:#3f3f46;font-size:10px;font-weight:850;cursor:pointer;transition:transform .15s,border-color .15s,background .15s}
        .rms-nvidia-btn:hover{transform:translateY(-1px);border-color:#a1a1aa}
        .rms-nvidia-btn:disabled{opacity:.55;cursor:wait;transform:none}
        .rms-nvidia-btn-primary{border-color:#202124;background:#202124;color:#fff}
        .rms-nvidia-btn-primary:hover{border-color:#343a40;background:#343a40}
        .rms-nvidia-btn-green{border-color:#76a52c;background:#76a52c;color:#fff}
        .rms-nvidia-btn-danger{color:#b91c1c;border-color:#fecaca}
        .rms-nvidia-notice{display:flex;align-items:flex-start;gap:9px;padding:12px 13px;border:1px solid #e4e4e7;border-radius:11px;background:#fafafa;color:#52525b;font-size:10px;line-height:1.6}
        .rms-nvidia-notice strong{color:#27272a}
        .rms-nvidia-notice.is-success{border-color:#bbf7d0;background:#f0fdf4;color:#166534}
        .rms-nvidia-notice.is-error{border-color:#fecaca;background:#fef2f2;color:#991b1b}
        .rms-nvidia-playground-grid{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(0,.95fr);gap:14px;align-items:start}
        .rms-nvidia-result{display:grid;gap:12px;min-width:0}
        .rms-nvidia-preview{display:grid;place-items:center;min-height:260px;overflow:hidden;border:1px dashed #d4d4d8;border-radius:13px;background:radial-gradient(circle at 50% 0%,#f4f4f5,#fafafa 58%);padding:12px}
        .rms-nvidia-preview img{display:block;width:100%;height:auto;max-height:620px;object-fit:contain;border-radius:8px}
        .rms-nvidia-preview-empty{max-width:240px;text-align:center;color:#a1a1aa}
        .rms-nvidia-preview-empty strong{display:block;margin:10px 0 5px;color:#71717a;font-size:11px}
        .rms-nvidia-preview-empty span{font-size:9px;line-height:1.6}
        .rms-nvidia-preview-symbol{display:grid;place-items:center;width:42px;height:42px;margin:0 auto;border:1px solid #e4e4e7;border-radius:13px;background:#fff;color:#71717a;font-size:20px}
        .rms-nvidia-log-list{display:grid;gap:8px}
        .rms-nvidia-log{display:grid;grid-template-columns:8px minmax(0,1fr) auto;align-items:start;gap:10px;padding:12px;border:1px solid #f0f0f2;border-radius:11px;background:#fff}
        .rms-nvidia-log-dot{width:7px;height:7px;margin-top:4px;border-radius:50%;background:#a1a1aa}
        .rms-nvidia-log-dot.success{background:#22c55e}.rms-nvidia-log-dot.error{background:#ef4444}.rms-nvidia-log-dot.warning{background:#f59e0b}
        .rms-nvidia-log-copy{min-width:0}.rms-nvidia-log-copy strong{display:block;color:#3f3f46;font-size:10px;font-weight:850;overflow-wrap:anywhere}
        .rms-nvidia-log-copy p{margin:4px 0;color:#71717a;font-size:9px;line-height:1.55;overflow-wrap:anywhere}
        .rms-nvidia-log-copy small{color:#a1a1aa;font-size:8px;overflow-wrap:anywhere}
        .rms-nvidia-log-status{padding:4px 6px;border-radius:6px;background:#f4f4f5;color:#71717a;font-size:7px;font-weight:900;letter-spacing:.07em;text-transform:uppercase}
        .rms-nvidia-log-status.success{background:#dcfce7;color:#166534}.rms-nvidia-log-status.error{background:#fee2e2;color:#991b1b}
        @media(max-width:900px){.rms-nvidia-playground-grid{grid-template-columns:minmax(0,1fr)}}
        @media(max-width:620px){.rms-nvidia-hero{flex-direction:column;padding:19px}.rms-nvidia-panel{padding:15px}.rms-nvidia-fields{grid-template-columns:minmax(0,1fr)}.rms-nvidia-field-full{grid-column:auto}.rms-nvidia-actions{align-items:stretch;flex-direction:column}.rms-nvidia-actions .rms-nvidia-btn{width:100%}.rms-nvidia-status{align-self:flex-start}.rms-nvidia-preview{min-height:200px}.rms-nvidia-log{grid-template-columns:7px minmax(0,1fr)}.rms-nvidia-log-status{grid-column:2;justify-self:start}}
    </style>

    <div class="rms-nvidia-hero">
        <div class="rms-nvidia-hero-copy">
            <span class="rms-nvidia-kicker">PROVIDER 03 · NVIDIA API CATALOG</span>
            <h3>NVIDIA Image Playground</h3>
            <p>Simpan credential secara terenkripsi, uji endpoint hosted, generate gambar dari prompt, dan pantau hasilnya melalui Global Activity Log.</p>
        </div>
        <span class="rms-nvidia-status {{ $configured ? ($connectionStatus === 'error' ? 'is-error' : 'is-ready') : '' }}">
            <i></i>{{ $configured ? ($connectionStatus === 'success' ? 'Connection tested' : ($connectionStatus === 'error' ? 'Needs attention' : 'API key saved')) : 'Not configured' }}
        </span>
    </div>

    <section class="rms-nvidia-panel">
        <div class="rms-nvidia-panel-head">
            <div><h4>API Configuration</h4><p>Credential disimpan di database dengan Laravel encrypted cast. Key asli tidak pernah ditampilkan ulang atau ditulis ke log.</p></div>
        </div>
        <div class="rms-nvidia-fields">
            <div class="rms-nvidia-field rms-nvidia-field-full">
                <label for="nvidia-api-key">NVIDIA API key</label>
                @if ($configured)
                    <div class="rms-nvidia-key-row">
                        <div class="rms-nvidia-masked">{{ $maskedKey }}</div>
                        <span class="rms-nvidia-hint">Key tersimpan</span>
                    </div>
                    <input id="nvidia-api-key" type="password" wire:model="apiKeyInput" autocomplete="new-password" placeholder="Masukkan key baru untuk mengganti credential">
                    <span class="rms-nvidia-hint">Kosongkan untuk mempertahankan key yang sekarang.</span>
                @else
                    <input id="nvidia-api-key" type="password" wire:model="apiKeyInput" autocomplete="new-password" placeholder="nvapi-..." required>
                    <span class="rms-nvidia-hint">Ambil API key dari NVIDIA API Catalog. Jangan kirim key melalui chat atau log.</span>
                @endif
                @error('apiKeyInput') <span class="rms-nvidia-hint" style="color:#dc2626">{{ $message }}</span> @enderror
            </div>
            <div class="rms-nvidia-field">
                <label for="nvidia-model">Default model</label>
                <select id="nvidia-model" wire:model="model">
                    @foreach ($models as $item)
                        <option value="{{ $item['id'] }}">{{ $item['label'] }}</option>
                    @endforeach
                </select>
                <span class="rms-nvidia-hint">Endpoint resmi dipilih otomatis berdasarkan model.</span>
            </div>
            <div class="rms-nvidia-field">
                <label>Last test</label>
                <div class="rms-nvidia-masked">{{ $lastTestedAt ?: 'Belum pernah dites' }} · {{ strtoupper($connectionStatus) }}</div>
                <span class="rms-nvidia-hint">Tes koneksi melakukan satu request generasi gambar sungguhan.</span>
            </div>
        </div>
        <div class="rms-nvidia-actions">
            <button type="button" class="rms-nvidia-btn rms-nvidia-btn-primary" wire:click="saveConfiguration" wire:loading.attr="disabled" wire:target="saveConfiguration">
                <span wire:loading.remove wire:target="saveConfiguration">Simpan konfigurasi</span><span wire:loading wire:target="saveConfiguration">Menyimpan…</span>
            </button>
            <button type="button" class="rms-nvidia-btn rms-nvidia-btn-green" wire:click="testConnection" wire:loading.attr="disabled" wire:target="testConnection" @disabled(!$configured)>
                <span wire:loading.remove wire:target="testConnection">Test connection</span><span wire:loading wire:target="testConnection">Testing endpoint…</span>
            </button>
            @if ($configured)
                <button type="button" class="rms-nvidia-btn rms-nvidia-btn-danger" wire:click="removeApiKey" wire:confirm="Hapus NVIDIA API key dari konfigurasi?">Remove key</button>
            @endif
        </div>
        @if ($testStatus !== 'idle' && $testMessage)
            <div class="rms-nvidia-notice {{ $testStatus === 'success' ? 'is-success' : ($testStatus === 'error' ? 'is-error' : '') }}" style="margin-top:14px">
                <span>{{ $testStatus === 'success' ? '✓' : ($testStatus === 'error' ? '!' : '…') }}</span><div><strong>{{ $testStatus === 'success' ? 'Request berhasil' : ($testStatus === 'error' ? 'Request gagal' : 'Sedang diproses') }}</strong><br>{{ $testMessage }} @if($testHttpStatus) · HTTP {{ $testHttpStatus }} @endif @if($testDurationMs) · {{ $testDurationMs }} ms @endif</div>
            </div>
        @endif
    </section>

    <div class="rms-nvidia-playground-grid">
        <section class="rms-nvidia-panel">
            <div class="rms-nvidia-panel-head"><div><h4>Generate test image</h4><p>Gunakan prompt singkat terlebih dahulu untuk memverifikasi akses model dan format respons.</p></div></div>
            <div class="rms-nvidia-fields">
                <div class="rms-nvidia-field rms-nvidia-field-full">
                    <label for="nvidia-test-prompt">Prompt</label>
                    <textarea id="nvidia-test-prompt" wire:model="prompt" maxlength="10000" placeholder="Describe the image you want to generate…"></textarea>
                    @error('prompt') <span class="rms-nvidia-hint" style="color:#dc2626">{{ $message }}</span> @enderror
                </div>
                <div class="rms-nvidia-field">
                    <label for="nvidia-test-model">Model untuk tes</label>
                    <select id="nvidia-test-model" wire:model="model">
                        @foreach ($models as $item)
                            <option value="{{ $item['id'] }}">{{ $item['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rms-nvidia-field">
                    <label for="nvidia-reference">Reference image (opsional)</label>
                    <input id="nvidia-reference" type="file" wire:model="referenceImage" accept="image/png,image/jpeg,image/webp">
                    <span class="rms-nvidia-hint">JPG, PNG, atau WebP · maksimal 10 MB. Preview API NVIDIA bisa membatasi image editing ke gambar contoh yang disediakan model.</span>
                    @error('referenceImage') <span class="rms-nvidia-hint" style="color:#dc2626">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="rms-nvidia-actions">
                <button type="button" class="rms-nvidia-btn rms-nvidia-btn-primary" wire:click="generateTestImage" wire:loading.attr="disabled" wire:target="generateTestImage,referenceImage" @disabled(!$configured || $isTesting)>
                    <span wire:loading.remove wire:target="generateTestImage">Generate test image</span><span wire:loading wire:target="generateTestImage">Generating image…</span>
                </button>
            </div>
            @if ($testStatus !== 'idle' && $testMessage)
                <div class="rms-nvidia-notice {{ $testStatus === 'success' ? 'is-success' : ($testStatus === 'error' ? 'is-error' : '') }}" style="margin-top:14px">
                    <span>{{ $testStatus === 'success' ? '✓' : ($testStatus === 'error' ? '!' : '…') }}</span><div>{{ $testMessage }} @if($testHttpStatus) · HTTP {{ $testHttpStatus }} @endif @if($testDurationMs) · {{ $testDurationMs }} ms @endif</div>
                </div>
            @endif
        </section>

        <section class="rms-nvidia-panel rms-nvidia-result">
            <div class="rms-nvidia-panel-head"><div><h4>Generated output</h4><p>Hasil tes disimpan pada storage publik aplikasi agar dapat dibuka setelah request selesai.</p></div></div>
            <div class="rms-nvidia-preview">
                @if ($testImageUrl)
                    <img src="{{ $testImageUrl }}" alt="NVIDIA generated test image">
                @else
                    <div class="rms-nvidia-preview-empty"><div class="rms-nvidia-preview-symbol">✦</div><strong>Belum ada hasil generate</strong><span>Gambar hasil tes akan muncul di area ini setelah NVIDIA mengembalikan output yang valid.</span></div>
                @endif
            </div>
            @if ($testImageUrl)
                <div class="rms-nvidia-actions" style="margin-top:0">
                    <a class="rms-nvidia-btn rms-nvidia-btn-primary" href="{{ $testImageUrl }}" target="_blank" rel="noopener">Open full image ↗</a>
                    <a class="rms-nvidia-btn" href="{{ $testImageUrl }}" download>Save image ↓</a>
                </div>
            @endif
        </section>
    </div>

    <section class="rms-nvidia-panel">
        <div class="rms-nvidia-panel-head"><div><h4>Recent NVIDIA activity</h4><p>Log khusus ini juga masuk ke Settings → Activity Log sebagai global audit trail.</p></div></div>
        <div class="rms-nvidia-log-list">
            @forelse ($logs as $log)
                <article class="rms-nvidia-log">
                    <span class="rms-nvidia-log-dot {{ $log['status'] }}"></span>
                    <div class="rms-nvidia-log-copy">
                        <strong>{{ $log['title'] }}</strong>
                        <p>{{ $log['description'] }}</p>
                        <small>{{ $log['created_at'] }}@if(isset($log['metadata']['model'])) · {{ $log['metadata']['model'] }}@endif @if(isset($log['metadata']['http_status'])) · HTTP {{ $log['metadata']['http_status'] }}@endif @if(isset($log['metadata']['duration_ms'])) · {{ $log['metadata']['duration_ms'] }} ms@endif</small>
                    </div>
                    <span class="rms-nvidia-log-status {{ $log['status'] }}">{{ $log['status'] }}</span>
                </article>
            @empty
                <div class="rms-nvidia-preview-empty" style="max-width:none;padding:20px"><strong>Belum ada aktivitas NVIDIA</strong><span>Simpan API key atau jalankan tes pertama untuk membuat log.</span></div>
            @endforelse
        </div>
    </section>
</section>
