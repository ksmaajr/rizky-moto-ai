<?php

use App\Concerns\ProfileValidationRules;
use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Pengaturan Akun')] class extends Component {
    use ProfileValidationRules;
    use PasswordValidationRules;
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public $profilePhoto = null;
    public bool $showDeletePhotoConfirm = false;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    #[Computed]
    public function profilePhotoUrl(): ?string
    {
        $path = Auth::user()->getAttribute('profile_photo_path');
        return $path ? Storage::disk('public')->url($path) : null;
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();
        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        Flux::toast(variant: 'success', text: 'Informasi profil berhasil diperbarui.');
    }

    public function uploadProfilePhoto(): void
    {
        $validated = $this->validate([
            'profilePhoto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'profilePhoto.image' => 'File harus berupa gambar.',
            'profilePhoto.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'profilePhoto.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $user = Auth::user();
        $oldPath = $user->getAttribute('profile_photo_path');
        $newPath = $validated['profilePhoto']->storePublicly('profile-photos', 'public');

        if (! $newPath) {
            Flux::toast(variant: 'danger', text: 'Foto profil gagal diunggah. Silakan coba lagi.');
            return;
        }

        $user->forceFill(['profile_photo_path' => $newPath])->save();

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->reset('profilePhoto');
        unset($this->profilePhotoUrl);

        Flux::toast(variant: 'success', text: 'Foto profil berhasil diperbarui.');
    }

    public function confirmDeleteProfilePhoto(): void
    {
        $this->showDeletePhotoConfirm = true;
    }

    public function cancelDeleteProfilePhoto(): void
    {
        $this->showDeletePhotoConfirm = false;
    }

    public function deleteProfilePhoto(): void
    {
        $user = Auth::user();
        $path = $user->getAttribute('profile_photo_path');

        if (! $path) {
            $this->showDeletePhotoConfirm = false;
            Flux::toast(variant: 'info', text: 'Belum ada foto profil untuk dihapus.');
            return;
        }

        $user->forceFill(['profile_photo_path' => null])->save();
        Storage::disk('public')->delete($path);

        $this->showDeletePhotoConfirm = false;
        unset($this->profilePhotoUrl);

        Flux::toast(variant: 'success', text: 'Foto profil berhasil dihapus.');
    }

    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $exception) {
            $this->reset('current_password', 'password', 'password_confirmation');
            throw $exception;
        }

        Auth::user()->update(['password' => $validated['password']]);
        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: 'Password berhasil diperbarui.');
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();
        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
        Flux::toast(variant: 'success', text: 'Email verifikasi berhasil dikirim ulang.');
    }
}; ?>

<div class="account-settings-page">
    <style>
        .account-settings-page{--as-ink:#18181b;--as-muted:#71717a;--as-line:#e8e8ed;--as-green:#16a34a;max-width:1120px;margin:0 auto;padding:clamp(22px,4vw,48px) clamp(16px,3vw,34px) 64px;color:var(--as-ink);font-family:Inter,Arial,sans-serif;animation:as-page-in .55s cubic-bezier(.2,.75,.25,1) both}
        .account-settings-page *{box-sizing:border-box}
        @keyframes as-page-in{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
        @keyframes as-card-in{from{opacity:0;transform:translateY(16px) scale(.99)}to{opacity:1;transform:translateY(0) scale(1)}}
        @keyframes as-modal-in{from{opacity:0;transform:translateY(12px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
        @keyframes as-shine{0%{transform:translateX(-130%)}100%{transform:translateX(130%)}}
        .as-back{display:inline-flex;align-items:center;gap:9px;padding:8px 0;color:#71717a;text-decoration:none;font-size:12px;font-weight:700;transition:color .2s,transform .2s}
        .as-back:hover{color:#18181b;transform:translateX(-3px)}
        .as-hero{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:22px 0 28px}
        .as-kicker{display:flex;align-items:center;gap:8px;color:#16a34a;font-size:10px;font-weight:850;letter-spacing:.16em;text-transform:uppercase}
        .as-kicker:before{content:"";width:7px;height:7px;border-radius:50%;background:#22c55e;box-shadow:0 0 0 4px #dcfce7}
        .as-title{margin:12px 0 7px;font-family:Poppins,Inter,sans-serif;font-size:clamp(25px,4vw,36px);font-weight:800;letter-spacing:-.045em;line-height:1.12}
        .as-subtitle{margin:0;color:#777782;font-size:13px;line-height:1.7}
        .as-secure{display:inline-flex;align-items:center;gap:8px;padding:10px 13px;border:1px solid #e7eee8;border-radius:12px;background:#fbfefb;color:#477454;font-size:10px;font-weight:750;white-space:nowrap}
        .as-grid{display:grid;grid-template-columns:minmax(0,1.08fr) minmax(320px,.92fr);gap:18px;align-items:start}
        .as-card{position:relative;overflow:hidden;border:1px solid var(--as-line);border-radius:20px;background:rgba(255,255,255,.96);box-shadow:0 8px 30px rgba(24,24,27,.045);animation:as-card-in .65s cubic-bezier(.2,.75,.25,1) both;transition:box-shadow .25s,border-color .25s,transform .25s}
        .as-card:hover{border-color:#d9e8dc;box-shadow:0 15px 38px rgba(24,24,27,.075);transform:translateY(-2px)}
        .as-card:nth-child(2){animation-delay:.09s}
        .as-card-head{display:flex;align-items:flex-start;gap:13px;padding:22px 23px 18px;border-bottom:1px solid #f0f0f2}
        .as-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border:1px solid #e6f2e8;border-radius:13px;background:linear-gradient(145deg,#f0fdf4,#e8f7ec);color:#16803d}
        .as-card-title{margin:1px 0 5px;font-family:Poppins,Inter,sans-serif;font-size:14px;font-weight:750;letter-spacing:-.02em}
        .as-card-desc{margin:0;color:#85858f;font-size:11px;line-height:1.6}
        .as-card-body{padding:22px 23px 24px}
        .as-field{display:grid;gap:8px;margin-bottom:18px}
        .as-label{display:flex;justify-content:space-between;align-items:center;gap:10px;color:#3f3f46;font-size:11px;font-weight:750}
        .as-input{display:block;width:100%;height:46px;padding:0 14px;border:1px solid #e4e4e7;border-radius:11px;outline:none;background:#fff;color:#18181b;font:500 12px Inter,Arial,sans-serif;transition:border-color .2s,box-shadow .2s,background .2s}
        .as-input::placeholder{color:#b2b2bb}.as-input:hover{border-color:#c9d8cc}.as-input:focus{border-color:#22a34a;box-shadow:0 0 0 4px rgba(34,163,74,.09)}.as-input.is-invalid{border-color:#ef4444}
        .as-error{color:#dc2626;font-size:10px;font-weight:600}.as-help{color:#92929b;font-size:10px;line-height:1.6}
        .as-email-state{display:inline-flex;align-items:center;gap:5px;color:#16803d;font-size:9px;font-weight:800}.as-email-state:before{content:"";width:6px;height:6px;border-radius:50%;background:#22c55e}
        .as-button{position:relative;display:inline-flex;align-items:center;justify-content:center;gap:9px;min-height:42px;padding:0 17px;overflow:hidden;border:0;border-radius:11px;background:linear-gradient(115deg,#15803d,#22a34a);box-shadow:0 5px 12px rgba(22,163,74,.17);color:#fff;font:750 11px Inter,Arial,sans-serif;cursor:pointer;transition:transform .2s,box-shadow .2s,filter .2s}
        .as-button:after{position:absolute;top:0;left:0;width:40%;height:100%;background:linear-gradient(90deg,transparent,rgba(255,255,255,.2),transparent);content:"";transform:translateX(-130%)}
        .as-button:hover{transform:translateY(-2px);box-shadow:0 8px 18px rgba(22,163,74,.24);filter:saturate(1.08)}.as-button:hover:after{animation:as-shine .7s ease}.as-button:active{transform:translateY(0)}.as-button[disabled]{opacity:.65;cursor:wait;transform:none}
        .as-button-row{display:flex;align-items:center;justify-content:space-between;gap:12px;padding-top:5px}
        .as-password-note{display:flex;align-items:flex-start;gap:9px;margin-top:3px;padding:12px;border:1px solid #edf0ed;border-radius:11px;background:#fafcf9;color:#777782;font-size:10px;line-height:1.6}
        .as-password-note svg{flex:0 0 15px;color:#27934b}
        .as-verification{margin:-5px 0 18px;padding:11px 12px;border:1px solid #fde7c2;border-radius:10px;background:#fffbeb;color:#9a6700;font-size:10px;line-height:1.6}
        .as-verification button{margin-left:4px;color:#166534;font-weight:800;text-decoration:underline}
        .as-photo-card{grid-column:1/-1}.as-photo-layout{display:flex;align-items:center;gap:22px}
        .as-photo-preview{position:relative;display:grid;place-items:center;flex:0 0 112px;width:112px;height:112px;overflow:hidden;border:4px solid #fff;border-radius:28px;background:linear-gradient(145deg,#171717,#343434);box-shadow:0 7px 22px rgba(0,0,0,.12);color:#fff;font:800 34px Poppins, sans-serif}
        .as-photo-preview img{width:100%;height:100%;object-fit:cover}
        .as-photo-actions{display:flex;flex-wrap:wrap;gap:9px;margin-top:14px}
        .as-button-secondary{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:38px;padding:0 13px;border:1px solid #e4e4e7;border-radius:10px;background:#fff;color:#45454d;font:700 10px Inter,sans-serif;cursor:pointer;transition:.2s}
        .as-button-secondary:hover{border-color:#b9d9c0;background:#f7fcf8;color:#166534;transform:translateY(-1px)}
        .as-button-danger{border-color:#fee2e2;color:#dc2626}.as-button-danger:hover{border-color:#fecaca;background:#fff7f7;color:#b91c1c}
        .as-file-input{position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;clip-path:inset(50%)}
        .as-upload-progress{height:4px;overflow:hidden;margin-top:12px;border-radius:20px;background:#e9f5ec}.as-upload-progress span{display:block;width:100%;height:100%;background:linear-gradient(90deg,#16a34a,#86efac);transform-origin:left;animation:as-shine 1.3s linear infinite}
        .as-modal-backdrop{position:fixed;inset:0;z-index:9999;display:grid;place-items:center;padding:20px;background:rgba(10,12,15,.62);backdrop-filter:blur(12px);-webkit-backdrop-filter:blur(12px);animation:as-fade-in .2s ease both}
        @keyframes as-fade-in{from{opacity:0}to{opacity:1}}
        .as-confirm-modal{width:min(100%,420px);padding:26px;border:1px solid rgba(255,255,255,.12);border-radius:22px;background:linear-gradient(145deg,#fff,#f8faf9);box-shadow:0 30px 100px rgba(0,0,0,.35);animation:as-modal-in .28s cubic-bezier(.2,.8,.2,1) both}
        .as-confirm-icon{display:grid;place-items:center;width:48px;height:48px;margin-bottom:17px;border:1px solid #fee2e2;border-radius:15px;background:#fff1f2;color:#dc2626}
        .as-confirm-modal h3{margin:0 0 7px;color:#18181b;font:800 17px Poppins,Inter,sans-serif}.as-confirm-modal p{margin:0;color:#71717a;font-size:11px;line-height:1.7}
        .as-modal-actions{display:flex;justify-content:flex-end;gap:9px;margin-top:24px}
        .as-footer{display:flex;align-items:center;justify-content:center;gap:7px;margin-top:22px;color:#a1a1aa;font-size:9px}
        @media(max-width:760px){.account-settings-page{padding-top:20px}.as-hero{align-items:flex-start;flex-direction:column;margin:18px 0 22px}.as-secure{align-self:flex-start}.as-grid{grid-template-columns:1fr}.as-card-head{padding:19px 18px 16px}.as-card-body{padding:19px 18px 21px}.as-photo-card{grid-column:auto}.as-photo-layout{align-items:flex-start;flex-direction:column}}
        @media(prefers-reduced-motion:reduce){.account-settings-page,.as-card,.as-confirm-modal,.as-modal-backdrop{animation:none!important;transition:none!important}.as-button:after{display:none}}
    </style>

    <a href="{{ route('dashboard') }}" wire:navigate class="as-back">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/><path d="M9 12h12"/></svg>
        Kembali ke Dashboard
    </a>

    <header class="as-hero">
        <div><div class="as-kicker">ACCOUNT CENTER</div><h1 class="as-title">Pengaturan Akun</h1><p class="as-subtitle">Kelola foto, identitas, dan keamanan akun Rizky Moto Shop AI Anda.</p></div>
        <div class="as-secure"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/><path d="m9 12 2 2 4-4"/></svg>Pengaturan privat & aman</div>
    </header>

    <div class="as-grid">
        <section class="as-card as-photo-card">
            <div class="as-card-head"><div class="as-icon"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div><div><h2 class="as-card-title">Foto Profil</h2><p class="as-card-desc">Pasang foto agar akun Anda lebih mudah dikenali.</p></div></div>
            <div class="as-card-body">
                <div class="as-photo-layout">
                    <div class="as-photo-preview">
                        @if ($this->profilePhotoUrl)
                            <img src="{{ $this->profilePhotoUrl }}" alt="Foto profil">
                        @else
                            {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(Auth::user()->name, 0, 1)) }}
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="as-label">Foto akun Anda <span class="as-help">JPG, PNG, WEBP · Maks. 2 MB</span></div>
                        <p class="as-help" style="margin:7px 0 0">Gunakan foto persegi agar hasil tampil optimal pada menu profil dan dashboard.</p>
                        <div class="as-photo-actions">
                            <label for="profile-photo-upload" class="as-button-secondary" style="cursor:pointer">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 16V4m-5 5 5-5 5 5"/><path d="M4 16v4h16v-4"/></svg>
                                Pilih Foto
                            </label>
                            <input id="profile-photo-upload" class="as-file-input" type="file" accept="image/jpeg,image/png,image/webp" wire:model="profilePhoto">
                            @if ($this->profilePhotoUrl)
                                <button type="button" class="as-button-secondary as-button-danger" wire:click="confirmDeleteProfilePhoto">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>
                                    Hapus Foto
                                </button>
                            @endif
                        </div>
                        @error('profilePhoto')<div class="as-error" style="margin-top:8px">{{ $message }}</div>@enderror
                        @if ($profilePhoto)
                            <div class="as-help" style="margin-top:10px">File dipilih: {{ $profilePhoto->getClientOriginalName() }}</div>
                            <div wire:loading wire:target="profilePhoto" class="as-upload-progress"><span></span></div>
                            <button type="button" class="as-button" style="margin-top:12px" wire:click="uploadProfilePhoto" wire:loading.attr="disabled" wire:target="uploadProfilePhoto,profilePhoto">
                                <span wire:loading.remove wire:target="uploadProfilePhoto">Simpan Foto Profil</span><span wire:loading wire:target="uploadProfilePhoto">Mengunggah...</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="as-card">
            <div class="as-card-head"><div class="as-icon"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg></div><div><h2 class="as-card-title">Informasi Profil</h2><p class="as-card-desc">Perbarui nama dan alamat email akun Anda.</p></div></div>
            <form wire:submit="updateProfileInformation" class="as-card-body">
                <div class="as-field"><label class="as-label" for="account-name">Nama lengkap</label><input id="account-name" class="as-input @error('name') is-invalid @enderror" type="text" wire:model.blur="name" placeholder="Masukkan nama lengkap" autocomplete="name" maxlength="255" required>@error('name')<span class="as-error">{{ $message }}</span>@enderror</div>
                <div class="as-field"><label class="as-label" for="account-email">Alamat email @if (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail())<span class="as-email-state">TERVERIFIKASI</span>@endif</label><input id="account-email" class="as-input @error('email') is-invalid @enderror" type="email" wire:model.blur="email" placeholder="nama@email.com" autocomplete="email" maxlength="255" required>@error('email')<span class="as-error">{{ $message }}</span>@enderror<span class="as-help">Email digunakan untuk masuk ke dashboard dan menerima informasi akun.</span></div>
                @if (Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail())<div class="as-verification">Email Anda belum diverifikasi. <button type="button" wire:click="resendVerificationNotification">Kirim ulang email verifikasi</button></div>@endif
                <div class="as-button-row"><span class="as-help">Perubahan disimpan langsung ke akun.</span><button class="as-button" type="submit" wire:loading.attr="disabled" wire:target="updateProfileInformation"><span wire:loading.remove wire:target="updateProfileInformation">Simpan Profil</span><span wire:loading wire:target="updateProfileInformation">Menyimpan...</span></button></div>
            </form>
        </section>

        <section class="as-card">
            <div class="as-card-head"><div class="as-icon"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="10" width="18" height="11" rx="2"/><path d="M7 10V7a5 5 0 0 1 10 0v3"/><circle cx="12" cy="15.5" r="1"/></svg></div><div><h2 class="as-card-title">Keamanan Password</h2><p class="as-card-desc">Ganti password untuk menjaga keamanan akun.</p></div></div>
            <form wire:submit="updatePassword" class="as-card-body">
                <div class="as-field"><label class="as-label" for="current-password">Password saat ini</label><input id="current-password" class="as-input @error('current_password') is-invalid @enderror" type="password" wire:model="current_password" placeholder="Masukkan password saat ini" autocomplete="current-password" required>@error('current_password')<span class="as-error">{{ $message }}</span>@enderror</div>
                <div class="as-field"><label class="as-label" for="new-password">Password baru</label><input id="new-password" class="as-input @error('password') is-invalid @enderror" type="password" wire:model="password" placeholder="Buat password baru" autocomplete="new-password" required>@error('password')<span class="as-error">{{ $message }}</span>@enderror</div>
                <div class="as-field"><label class="as-label" for="confirm-password">Konfirmasi password baru</label><input id="confirm-password" class="as-input @error('password_confirmation') is-invalid @enderror" type="password" wire:model="password_confirmation" placeholder="Ulangi password baru" autocomplete="new-password" required>@error('password_confirmation')<span class="as-error">{{ $message }}</span>@enderror</div>
                <div class="as-password-note"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M12 11v5m0-8h.01"/></svg>Gunakan password yang kuat dan unik. Password saat ini wajib diisi untuk mengonfirmasi perubahan.</div>
                <div class="as-button-row" style="margin-top:17px"><span class="as-help">Password disimpan dengan hash aman.</span><button class="as-button" type="submit" wire:loading.attr="disabled" wire:target="updatePassword"><span wire:loading.remove wire:target="updatePassword">Perbarui Password</span><span wire:loading wire:target="updatePassword">Memperbarui...</span></button></div>
            </form>
        </section>
    </div>

    <div class="as-footer"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z"/></svg>Perubahan akun dilindungi autentikasi dan validasi server.</div>

    @if ($showDeletePhotoConfirm)
        <div class="as-modal-backdrop" wire:click.self="cancelDeleteProfilePhoto" wire:keydown.escape.window="cancelDeleteProfilePhoto">
            <section class="as-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="delete-photo-title">
                <div class="as-confirm-icon"><svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/><path d="M10 11v5m4-5v5"/></svg></div>
                <h3 id="delete-photo-title">Hapus foto profil?</h3>
                <p>Foto profil akan dihapus dari akun Anda dan avatar akan kembali menggunakan inisial nama. Tindakan ini tidak dapat dibatalkan.</p>
                <div class="as-modal-actions">
                    <button type="button" class="as-button-secondary" wire:click="cancelDeleteProfilePhoto">Batal</button>
                    <button type="button" class="as-button" style="background:linear-gradient(115deg,#b91c1c,#ef4444);box-shadow:0 5px 12px rgba(220,38,38,.16)" wire:click="deleteProfilePhoto" wire:loading.attr="disabled" wire:target="deleteProfilePhoto"><span wire:loading.remove wire:target="deleteProfilePhoto">Ya, Hapus Foto</span><span wire:loading wire:target="deleteProfilePhoto">Menghapus...</span></button>
                </div>
            </section>
        </div>
    @endif
</div>
