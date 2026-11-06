<?php

namespace App\Livewire;

use App\Concerns\ProfileValidationRules;
use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;

class AccountSettings extends Component {
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

        $this->notify('success', 'Profil diperbarui', 'Nama dan alamat email berhasil disimpan.');
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
            $this->notify('error', 'Upload gagal', 'Foto profil tidak berhasil disimpan. Silakan coba lagi.');
            return;
        }

        $user->forceFill(['profile_photo_path' => $newPath])->save();

        if ($oldPath && $oldPath !== $newPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->reset('profilePhoto');
        unset($this->profilePhotoUrl);

        $this->notify('success', 'Foto profil diperbarui', 'Foto profil baru sudah aktif di akun Anda.');
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
            $this->notify('info', 'Belum ada foto', 'Akun ini belum memiliki foto profil tersimpan.');
            return;
        }

        $user->forceFill(['profile_photo_path' => null])->save();
        Storage::disk('public')->delete($path);

        $this->showDeletePhotoConfirm = false;
        unset($this->profilePhotoUrl);

        $this->notify('success', 'Foto profil dihapus', 'Avatar akun sekarang menggunakan inisial nama.');
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

        $this->notify('success', 'Password diperbarui', 'Password akun berhasil diganti.');
    }

    private function notify(string $type, string $title, string $message): void
    {
        $this->dispatch('toast', type: $type, title: $title, message: $message, duration: 4500);
    }

    public function resendVerificationNotification(): void
    {
        $user = Auth::user();
        if (! $user instanceof MustVerifyEmail || $user->hasVerifiedEmail()) {
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
        $this->notify('success', 'Email dikirim', 'Tautan verifikasi sudah dikirim ulang.');
    }
}
