<div class="rms-api-page" wire:key="settings-api-keys-panel">
    <style>
        .rms-api-page{--api-ink:#202631;--api-muted:#7b8493;--api-line:#e7ebf0;--api-green:#159653;color:var(--api-ink);animation:apiPageIn .42s ease both}
        @keyframes apiPageIn{from{opacity:0;transform:translateY(9px)}to{opacity:1;transform:translateY(0)}}
        @keyframes apiCardIn{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes apiCalendarIn{from{opacity:0;transform:translateY(-7px) scale(.98)}to{opacity:1;transform:translateY(0) scale(1)}}
        .rms-api-page .api-card{margin-bottom:18px;background:#fff;border:1px solid var(--api-line);border-radius:20px;box-shadow:0 8px 26px rgba(20,31,48,.045);transition:box-shadow .22s ease,border-color .22s ease,transform .22s ease;animation:apiCardIn .45s ease both}
        .rms-api-page .api-card:hover{border-color:#d8e5dc;box-shadow:0 14px 34px rgba(20,31,48,.075)}
        .rms-api-page .api-field-label{display:block;margin:0 0 8px;color:#424b59;font-size:12px;font-weight:750}
        .rms-api-page .api-control{width:100%;height:48px;border:1px solid #dfe4ea;border-radius:12px;background:#fff;padding:0 14px;color:#27303c;font-size:13px;outline:none;transition:border-color .18s ease,box-shadow .18s ease}
        .rms-api-page .api-control::placeholder{color:#a0a8b4}
        .rms-api-page .api-control:hover{border-color:#c8d2dc}
        .rms-api-page .api-control:focus{border-color:#37a96b;box-shadow:0 0 0 4px rgba(36,166,96,.11)}
.rms-api-page .api-date-trigger{display:flex!important;flex-direction:row!important;align-items:center!important;justify-content:space-between!important;gap:12px!important;text-align:left!important;line-height:1.2}
        .rms-api-page .api-date-trigger > span{display:block;min-width:0;flex:1}
        .rms-api-page .api-date-trigger > svg{display:block;flex:0 0 18px;width:18px;height:18px;margin:0}
        .rms-api-page .api-primary{display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:47px;padding:0 19px;border:0;border-radius:12px;background:linear-gradient(135deg,#1ba45c,#108348);color:#fff;font-size:12px;font-weight:800;box-shadow:0 7px 16px rgba(21,150,83,.18);transition:transform .18s ease,box-shadow .18s ease}
        .rms-api-page .api-primary:hover{transform:translateY(-2px);box-shadow:0 11px 22px rgba(21,150,83,.24)}
        .rms-api-page .api-primary:active{transform:scale(.98)}
        .rms-api-page .api-primary:disabled{opacity:.55;cursor:not-allowed;transform:none}
        .rms-api-page .api-key-row{transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}
        .rms-api-page .api-key-row:hover{transform:translateY(-2px);border-color:#d4e6da;box-shadow:0 9px 20px rgba(20,31,48,.05)}
        .rms-api-page .api-copy{display:inline-flex;align-items:center;gap:7px;border:1px solid #cdebd8;border-radius:10px;background:#fff;padding:9px 12px;color:#137d45;font-size:11px;font-weight:750;transition:background .18s ease,transform .18s ease}
        .rms-api-page .api-copy:hover{background:#effaf3;transform:translateY(-1px)}
        .rms-api-page .api-status{display:inline-flex;align-items:center;gap:6px;border-radius:999px;padding:6px 9px;font-size:9px;font-weight:850;letter-spacing:.07em}
        .rms-api-page .api-status:before{content:"";width:6px;height:6px;border-radius:50%;background:currentColor}
        .rms-api-page .api-status-active{background:#eaf8ef;color:#16834a}
        .rms-api-page .api-status-inactive{background:#f1f3f6;color:#7d8794}
        .rms-api-page .api-note{background:linear-gradient(120deg,#f8fbf9,#f5f8fb);border:1px solid #e6eee8;border-radius:16px}
        .api-calendar{position:absolute;z-index:99999;width:min(340px,calc(100vw - 32px));max-height:min(440px,calc(100vh - 24px));overflow-y:auto;padding:17px;border:1px solid #e6ebef;border-radius:18px;background:#fff;box-shadow:0 22px 60px rgba(25,39,55,.2);animation:apiCalendarIn .18s ease both}
        .api-calendar-day{display:grid;place-items:center;width:36px;height:36px;border-radius:11px;color:#344052;font-size:12px;font-weight:650;transition:background .15s,color .15s,transform .15s}
        .api-calendar-day:hover:not(:disabled){background:#edf8f1;color:#138449;transform:scale(1.06)}
        .api-calendar-day:disabled{color:#c5cbd3;cursor:not-allowed}
        .api-calendar-day.is-selected{background:#159653;color:#fff;box-shadow:0 5px 12px rgba(21,150,83,.2)}
        .api-calendar-day.is-today:not(.is-selected){box-shadow:inset 0 0 0 1px #159653;color:#128149}
        .api-calendar-nav{display:grid;place-items:center;width:34px;height:34px;border:1px solid #e8edf1;border-radius:10px;background:#fff;color:#536071;transition:background .15s,border-color .15s}
        .api-calendar-nav:hover{background:#f1faf4;border-color:#ccebd7;color:#128149}
        .api-calendar-link{font-size:11px;font-weight:750;color:#16834a;transition:color .15s}
        .api-calendar-link:hover{color:#075d31}
        @media(max-width:640px){.rms-api-page .api-card{border-radius:16px}.rms-api-page .api-primary{width:100%}}
        @media(prefers-reduced-motion:reduce){.rms-api-page,.rms-api-page *{animation:none!important;transition:none!important}}
    </style>

    <section class="rms-settings-panel-head">
        <div class="rms-settings-panel-brand">
            <span class="rms-settings-panel-mark rms-openai-mark">API</span>
            <div><span>DEVELOPER ACCESS</span><strong>API Key Management</strong><small>Kelola akses aplikasi eksternal ke Image Generation API.</small></div>
        </div>
        <div class="rms-api-connection-badge"><i></i> SECURE ACCESS</div>
    </section>

    <section class="api-card p-5 sm:p-6" style="animation-delay:.05s">
        <div class="mb-6 flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M7 14a5 5 0 1 1 3.9 1.86L9 18H7v2H4v-3l4.1-4.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="14" cy="9" r="1" fill="currentColor"/></svg>
                </span>
                <div><h3 class="text-[15px] font-extrabold tracking-tight text-slate-800">Create new API key</h3><p class="mt-1 text-xs leading-5 text-slate-500">Buat credential full access untuk Codex atau aplikasi eksternal.</p></div>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-100 bg-emerald-50 px-3 py-2 text-[10px] font-bold text-emerald-700"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> 60 requests / minute</span>
        </div>

        <form wire:submit="createApiKey" class="grid grid-cols-1 gap-x-5 gap-y-5 md:grid-cols-2">
            <div>
                <label class="api-field-label" for="api-key-name">Key name <span class="text-rose-500">*</span></label>
                <input id="api-key-name" class="api-control" type="text" wire:model="keyName" placeholder="Contoh: Codex local client" maxlength="120" required autocomplete="off">
                @error('keyName')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-[10px] text-slate-400">Gunakan nama perangkat atau aplikasi agar mudah dikenali.</p>
            </div>



            <div>
                <label class="api-field-label" for="api-key-expiry">Expiry date <span class="font-normal text-slate-400">(optional)</span></label>
                <div class="relative" x-data="{
                    open:false,
                    month: new Date(new Date().getFullYear(), new Date().getMonth(), 1),
                    selected: @entangle('expiresAt').live,
                    position: {top:0,left:0},
                    monthNames: ['January','February','March','April','May','June','July','August','September','October','November','December'],
                    get heading(){ return this.monthNames[this.month.getMonth()] + ' ' + this.month.getFullYear() },
                    get days(){
                        const first = new Date(this.month.getFullYear(), this.month.getMonth(), 1);
                        const offset = (first.getDay() + 6) % 7;
                        const count = new Date(this.month.getFullYear(), this.month.getMonth()+1, 0).getDate();
                        const total = Math.ceil((offset + count)/7)*7;
                        const today = new Date(); today.setHours(0,0,0,0);
                        return Array.from({length:total}, (_,i) => {
                            const date = new Date(this.month.getFullYear(), this.month.getMonth(), i-offset+1);
                            const iso=date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0')+'-'+String(date.getDate()).padStart(2,'0');
                            const local = new Date(date.getFullYear(),date.getMonth(),date.getDate());
                            return {iso:iso,day:date.getDate(),outside:date.getMonth()!==this.month.getMonth(),past:local<=today,today:local.getTime()===today.getTime()};
                        });
                    },
                    changeMonth(step){ this.month = new Date(this.month.getFullYear(),this.month.getMonth()+step,1) },
                    choose(day){ if(day.past)return; this.selected=day.iso; this.open=false; },
                    formatSelected(){ if(!this.selected)return ''; const p=this.selected.split('-'); return new Date(Number(p[0]),Number(p[1])-1,Number(p[2])).toLocaleDateString('id-ID',{day:'2-digit',month:'long',year:'numeric'}); },
                    show(){
                        if(this.selected){const p=this.selected.split('-');this.month=new Date(Number(p[0]),Number(p[1])-1,1)}
                        const rect=this.$refs.trigger.getBoundingClientRect();
                        const width=Math.min(340,window.innerWidth-32);
                        const left=Math.max(16,Math.min(rect.left+window.scrollX,window.scrollX+window.innerWidth-width-16));
                        const top=rect.bottom+window.scrollY+10;
                        this.position={top:top,left:left};
                        this.open=true;
                    }
                }" @keydown.escape.window="open=false">
                    <input type="hidden" wire:model="expiresAt">
                    <button x-ref="trigger" type="button" class="api-control api-date-trigger" :class="open ? 'border-emerald-400 ring-4 ring-emerald-500/10' : ''" @click="open ? open=false : show()" aria-haspopup="dialog" :aria-expanded="open.toString()">
                        <span :class="selected ? 'text-slate-700' : 'text-slate-400'" x-text="formatSelected() || 'Pilih tanggal kedaluwarsa'"></span>
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0 text-slate-500"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" stroke="currentColor" stroke-width="1.6"/><path d="M7.5 3.5v3M16.5 3.5v3M4 9.5h16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
                    </button>
                    <template x-teleport="body">
                        <div x-cloak x-show="open" x-transition.origin.top.left class="api-calendar" :style="'top:'+position.top+'px;left:'+position.left+'px'" @click.outside="open=false" role="dialog" aria-label="Pilih tanggal kedaluwarsa">
                            <div class="mb-4 flex items-center justify-between">
                                <div><span class="block text-[9px] font-extrabold uppercase tracking-[.15em] text-emerald-700">Expiry calendar</span><strong class="mt-1 block text-base font-extrabold tracking-tight text-slate-800" x-text="heading"></strong></div>
                                <div class="flex gap-1.5">
                                    <button type="button" class="api-calendar-nav" @click="changeMonth(-1)" aria-label="Bulan sebelumnya"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none"><path d="m15 18-6-6 6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                    <button type="button" class="api-calendar-nav" @click="changeMonth(1)" aria-label="Bulan berikutnya"><svg viewBox="0 0 24 24" class="h-4 w-4" fill="none"><path d="m9 18 6-6-6-6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                </div>
                            </div>
                            <div class="mb-2 grid grid-cols-7 justify-items-center text-[10px] font-bold text-slate-400"><template x-for="label in ['Mo','Tu','We','Th','Fr','Sa','Su']" :key="label"><span x-text="label"></span></template></div>
                            <div class="grid grid-cols-7 justify-items-center gap-y-1">
                                <template x-for="day in days" :key="day.iso"><button type="button" class="api-calendar-day" :class="{'opacity-30':day.outside,'is-selected':selected===day.iso,'is-today':day.today}" :disabled="day.past" @click="choose(day)" :aria-label="day.iso" x-text="day.day"></button></template>
                            </div>
                            <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-3">
                                <button type="button" class="api-calendar-link" @click="selected='';open=false">Clear date</button>
                                <button type="button" class="api-calendar-link" @click="month=new Date(new Date().getFullYear(),new Date().getMonth(),1)">This month</button>
                            </div>
                        </div>
                    </template>
                </div>
                @error('expiresAt')<p class="mt-1.5 text-xs font-medium text-rose-600">{{ $message }}</p>@enderror
                <p class="mt-1.5 text-[10px] text-slate-400">Kosongkan jika key tidak memiliki tanggal kedaluwarsa.</p>
            </div>

            <div class="md:col-span-2 rounded-2xl border border-emerald-100 bg-gradient-to-r from-emerald-50/80 to-white p-4">
                <div class="flex items-start gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white text-emerald-700 shadow-sm">
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M12 3 19 6v5c0 4.7-2.9 8.2-7 10-4.1-1.8-7-5.3-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <div class="min-w-0">
                        <strong class="text-xs font-extrabold text-slate-800">Full Image Generation Access</strong>
                        <p class="mt-1 text-[11px] leading-5 text-slate-600">API key ini dapat mengakses semua Store dan Template aktif milik workspace. Setiap request bebas menentukan Store, Template, model, judul, foto produk, serta foto produk terpasang (opsional).</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            <span class="rounded-full border border-emerald-100 bg-white px-2.5 py-1 text-[9px] font-bold text-emerald-700">All Stores</span>
                            <span class="rounded-full border border-emerald-100 bg-white px-2.5 py-1 text-[9px] font-bold text-emerald-700">All Templates</span>
                            <span class="rounded-full border border-emerald-100 bg-white px-2.5 py-1 text-[9px] font-bold text-emerald-700">Model selection</span>
                            <span class="rounded-full border border-emerald-100 bg-white px-2.5 py-1 text-[9px] font-bold text-emerald-700">Optional installed photo</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse items-stretch justify-between gap-3 border-t border-slate-100 pt-5 md:col-span-2 md:flex-row md:items-center">
                <p class="text-[10px] leading-5 text-slate-400">Secret dibuat secara acak dan hanya ditampilkan satu kali.</p>
                <button class="api-primary" type="submit" wire:loading.attr="disabled" wire:target="createApiKey">
                    <svg wire:loading.remove wire:target="createApiKey" viewBox="0 0 24 24" fill="none" class="h-4 w-4"><path d="M7 14a5 5 0 1 1 3.9 1.86L9 18H7v2H4v-3l4.1-4.1" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="14" cy="9" r="1" fill="currentColor"/></svg>
                    <svg wire:loading wire:target="createApiKey" viewBox="0 0 24 24" fill="none" class="h-4 w-4 animate-spin"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    <span wire:loading.remove wire:target="createApiKey">Create API key</span><span wire:loading wire:target="createApiKey">Creating secure key...</span>
                </button>
            </div>
        </form>
    </section>

    @if ($newlyCreatedSecret !== '')
        <section class="api-card border-emerald-200 bg-emerald-50/70 p-5 sm:p-6" style="animation-delay:.08s">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><h3 class="text-sm font-extrabold text-emerald-900">Your new API key is ready</h3><p class="mt-1 max-w-xl text-xs leading-5 text-emerald-800">Secret lengkap hanya ditampilkan sekali. Simpan sekarang di password manager atau environment variable.</p></div><button type="button" class="text-xs font-bold text-emerald-700" wire:click="dismissSecret">Dismiss</button></div>
            <code class="mt-4 block break-all rounded-xl border border-emerald-200 bg-white p-4 text-xs leading-6 text-slate-700">{{ $newlyCreatedSecret }}</code>
            <button type="button" class="api-copy mt-3" x-on:click="navigator.clipboard.writeText(@js($newlyCreatedSecret))"><svg viewBox="0 0 24 24" fill="none" class="h-4 w-4"><rect x="8" y="8" width="11" height="12" rx="2" stroke="currentColor" stroke-width="1.6"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h2" stroke="currentColor" stroke-width="1.6"/></svg>Copy API key</button>
        </section>
    @endif

    <section class="api-card p-5 sm:p-6" style="animation-delay:.1s">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <div><h3 class="text-[15px] font-extrabold tracking-tight text-slate-800">Your API keys</h3><p class="mt-1 text-xs leading-5 text-slate-500">Secret tidak disimpan dalam bentuk teks dan tidak dapat dilihat kembali.</p></div>
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-[11px] font-bold text-slate-600">{{ $this->keys->count() }} {{ $this->keys->count() === 1 ? 'key' : 'keys' }}</span>
        </div>
        <div class="space-y-3">
            @forelse ($this->keys as $key)
                @php $expired = $key->expires_at && $key->expires_at->isPast(); $revoked = $key->revoked_at !== null; $status = $revoked ? 'Revoked' : ($expired ? 'Expired' : 'Active'); @endphp
                <article wire:key="api-key-{{ $key->id }}" class="api-key-row flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 sm:p-5 md:flex-row md:items-center md:justify-between">
                    <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><strong class="break-words text-sm font-bold text-slate-800">{{ $key->name }}</strong><span class="api-status {{ $revoked || $expired ? 'api-status-inactive' : 'api-status-active' }}">{{ strtoupper($status) }}</span></div><code class="mt-2 block text-xs text-slate-500">{{ $key->prefix }}••••••••</code><div class="mt-2.5 flex flex-wrap gap-x-4 gap-y-2 text-[10px] font-medium text-slate-500"><span>{{ $key->store?->name ?? 'Full access · All active Stores' }}</span><span>{{ number_format($key->usage_count) }} requests</span><span>{{ $key->last_used_at?->diffForHumans() ?? 'Never used' }}</span>@if ($key->expires_at)<span>Expires {{ $key->expires_at->format('d M Y') }}</span>@endif</div></div>
                    @if (! $revoked)<flux:button size="sm" variant="danger" icon="ban" wire:click="revokeApiKey({{ $key->id }})" wire:confirm="Nonaktifkan API key ini? Aplikasi yang menggunakan key ini akan kehilangan akses.">Revoke</flux:button>@else<span class="text-[11px] font-semibold text-slate-400">Access disabled</span>@endif
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 px-5 py-12 text-center"><span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-white text-slate-400 shadow-sm"><svg viewBox="0 0 24 24" fill="none" class="h-6 w-6"><path d="M7 14a5 5 0 1 1 3.9 1.86L9 18H7v2H4v-3l4.1-4.1" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div class="mt-3 text-sm font-bold text-slate-700">No API keys yet</div><p class="mx-auto mt-1 max-w-sm text-xs leading-5 text-slate-500">Buat API key pertama untuk menghubungkan Codex atau aplikasi eksternal ke Image Generation API.</p></div>
            @endforelse
        </div>
    </section>

    <section class="api-note p-4 sm:p-5" style="animation-delay:.14s"><div class="flex items-start gap-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white text-emerald-700 shadow-sm"><svg viewBox="0 0 24 24" fill="none" class="h-5 w-5"><path d="M12 3 19 6v5c0 4.7-2.9 8.2-7 10-4.1-1.8-7-5.3-7-10V6l7-3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/><path d="m9 12 2 2 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg></span><div><strong class="text-xs font-extrabold text-slate-800">Security notes</strong><ul class="mt-2 grid gap-x-8 gap-y-1.5 text-[11px] leading-5 text-slate-600 md:grid-cols-2"><li>API key hanya ditampilkan saat pertama kali dibuat.</li><li>Server menyimpan SHA-256 hash, bukan secret aslinya.</li><li>Scope generation, read, dan cancel tersedia dengan batas default 60 request per menit.</li><li>Full access berlaku untuk seluruh Store dan Template aktif pada workspace pemilik key.</li></ul></div></div></section>
</div>