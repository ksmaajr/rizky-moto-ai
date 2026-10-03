<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

new
#[Layout('layouts::auth')]
class extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;


    /*
    |--------------------------------------------------------------------------
    | LOGIN
    |--------------------------------------------------------------------------
    */

    public function login(): void
    {
        try {
            $this->validate([
                'email' => [
                    'required',
                    'email',
                ],

                'password' => [
                    'required',
                    'string',
                ],
            ], [
                'email.required' => 'Email tidak boleh kosong!',
                'email.email' => 'Masukkan alamat email yang valid.',
                'password.required' => 'Password tidak boleh kosong!.',
            ]);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors();

            $message = $errors->first();

            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Warning',
                message: $message
            );

            throw $e;
        }

        

        try {
            $this->ensureIsNotRateLimited();
        } catch (ValidationException $e) {
            $message = $e->validator->errors()->first();

            $this->dispatch(
                'toast',
                type: 'warning',
                title: 'Terlalu banyak percobaan',
                message: $message
            );

            throw $e;
        }

        if (! Auth::attempt([
            'email' => $this->email,
            'password' => $this->password,
        ], $this->remember)) {

            RateLimiter::hit(
                $this->throttleKey()
            );

            $this->dispatch(
                'toast',
                type: 'error',
                title: 'Login Failed',
                message: 'Email atau password yang kamu masukkan salah.'
            );

            throw ValidationException::withMessages([
                'email' => 'Email atau password yang kamu masukkan salah.',
            ]);
        }

        RateLimiter::clear(
            $this->throttleKey()
        );

        request()
            ->session()
            ->regenerate();

        $this->dispatch(
            'toast',
            type: 'success',
            title: 'Login berhasil',
            message: 'Selamat datang kembali di AI Creative Tools.'
        );

        $this->redirect(
            route('dashboard'),
            navigate: true
        );
    }


    /*
    |--------------------------------------------------------------------------
    | RATE LIMIT
    |--------------------------------------------------------------------------
    */

    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts(
            $this->throttleKey(),
            5
        )) {
            return;
        }


        $seconds = RateLimiter::availableIn(
            $this->throttleKey()
        );


        throw ValidationException::withMessages([
            'email' =>
                "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | THROTTLE KEY
    |--------------------------------------------------------------------------
    */

    protected function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->email)
            . '|'
            . request()->ip()
        );
    }
};

?>


<div class="min-h-dvh bg-white">
    
    
    <div
        x-data="{
            show: false,
            type: 'error',
            title: '',
            message: '',
            duration: 2000,
            timeout: null,

            open(type, title, message) {
                clearTimeout(this.timeout);

                this.type = type;
                this.title = title;
                this.message = message;

                this.show = false;

                requestAnimationFrame(() => {
                    this.show = true;

                    this.timeout = setTimeout(() => {
                        this.close();
                    }, this.duration);
                });
            },

            close() {
                this.show = false;

                clearTimeout(this.timeout);
            }
        }"
        x-on:toast.window="
            open(
                $event.detail.type,
                $event.detail.title,
                $event.detail.message
            )
        "
        class="pointer-events-none fixed inset-x-4 top-5 z-[9999] flex justify-center sm:left-auto sm:right-6 sm:inset-x-auto sm:w-[400px]"
    >
        <div
            x-show="show"
            x-cloak
            x-transition:enter="toast-enter"
            x-transition:enter-start="opacity-0 translate-x-8 translate-y-[-8px] scale-[0.94]"
            x-transition:enter-end="opacity-100 translate-x-0 translate-y-0 scale-100"
            x-transition:leave="toast-leave"
            x-transition:leave-start="opacity-100 translate-x-0 scale-100"
            x-transition:leave-end="opacity-0 translate-x-8 scale-[0.96]"
            class="pointer-events-auto relative w-full overflow-hidden rounded-[20px] border border-white/80 bg-white/95 shadow-[0_24px_70px_rgba(0,0,0,0.16)] backdrop-blur-2xl"
        >

            
            <div
                class="pointer-events-none absolute inset-x-0 top-0 h-px"
                :class="{
                    'bg-gradient-to-r from-transparent via-red-400 to-transparent': type === 'error',
                    'bg-gradient-to-r from-transparent via-amber-400 to-transparent': type === 'warning',
                    'bg-gradient-to-r from-transparent via-emerald-400 to-transparent': type === 'success'
                }"
            ></div>

            
            <div
                class="toast-shine pointer-events-none absolute inset-y-0 -left-[120%] w-[70%] skew-x-[-20deg] bg-gradient-to-r from-transparent via-white/70 to-transparent"
            ></div>

            <div class="relative flex items-start gap-3.5 p-4">

                
                <div class="relative shrink-0">

                    
                    <div
                        class="absolute inset-0 rounded-[14px] animate-ping opacity-20"
                        :class="{
                            'bg-red-500': type === 'error',
                            'bg-amber-500': type === 'warning',
                            'bg-emerald-500': type === 'success'
                        }"
                    ></div>

                    
                    <div
                        class="relative flex h-11 w-11 items-center justify-center rounded-[14px] border"
                        :class="{
                            'border-red-100 bg-red-50 text-red-500': type === 'error',
                            'border-amber-100 bg-amber-50 text-amber-500': type === 'warning',
                            'border-emerald-100 bg-emerald-50 text-emerald-500': type === 'success'
                        }"
                    >

                        
                        <svg
                            x-show="type === 'error'"
                            class="h-5 w-5 toast-icon-pop"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M12 8V12"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />

                            <circle
                                cx="12"
                                cy="16"
                                r="1"
                                fill="currentColor"
                            />

                            <path
                                d="M10.3 4.6L2.8 17.2C2.1 18.4 3 20 4.4 20H19.6C21 20 21.9 18.4 21.2 17.2L13.7 4.6C13 3.4 11 3.4 10.3 4.6Z"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linejoin="round"
                            />
                        </svg>

                        
                        <svg
                            x-show="type === 'warning'"
                            class="h-5 w-5 toast-icon-pop"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M12 8V12"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                            />

                            <circle
                                cx="12"
                                cy="16"
                                r="1"
                                fill="currentColor"
                            />

                            <circle
                                cx="12"
                                cy="12"
                                r="9"
                                stroke="currentColor"
                                stroke-width="1.7"
                            />
                        </svg>

                        
                        <svg
                            x-show="type === 'success'"
                            class="h-5 w-5 toast-icon-pop"
                            viewBox="0 0 24 24"
                            fill="none"
                        >
                            <path
                                d="M6.5 12.5L10 16L17.5 8.5"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                    </div>
                </div>


                
                <div class="min-w-0 flex-1 pt-0.5">

                    <div class="flex items-center gap-2">

                        <p
                            x-text="title"
                            class="truncate text-[13px] font-bold tracking-[-0.01em] text-zinc-900"
                        ></p>

                        
                        <span
                            class="relative flex h-1.5 w-1.5 shrink-0"
                        >
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full opacity-60"
                                :class="{
                                    'bg-red-500': type === 'error',
                                    'bg-amber-500': type === 'warning',
                                    'bg-emerald-500': type === 'success'
                                }"
                            ></span>

                            <span
                                class="relative inline-flex h-1.5 w-1.5 rounded-full"
                                :class="{
                                    'bg-red-500': type === 'error',
                                    'bg-amber-500': type === 'warning',
                                    'bg-emerald-500': type === 'success'
                                }"
                            ></span>
                        </span>

                    </div>

                    <p
                        x-text="message"
                        class="mt-1 text-[12px] leading-[1.55] text-zinc-500"
                    ></p>

                </div>


                
                <button
                    type="button"
                    @click="close()"
                    class="group flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition-all duration-200 hover:bg-zinc-100 hover:text-zinc-700 active:scale-90"
                    aria-label="Tutup"
                >
                    <svg
                        class="h-4 w-4 transition-transform duration-200 group-hover:rotate-90"
                        viewBox="0 0 20 20"
                        fill="none"
                    >
                        <path
                            d="M6 6L14 14M14 6L6 14"
                            stroke="currentColor"
                            stroke-width="1.7"
                            stroke-linecap="round"
                        />
                    </svg>
                </button>

            </div>


            
            <div class="relative h-[3px] w-full bg-zinc-100">

                <div
                    x-show="show"
                    class="toast-progress h-full origin-left"
                    :class="{
                        'bg-red-500': type === 'error',
                        'bg-amber-500': type === 'warning',
                        'bg-emerald-500': type === 'success'
                    }"
                ></div>

            </div>

        </div>
    </div>


    

    <div
        class="grid min-h-dvh lg:grid-cols-[51%_49%]"
    >


        

        <section
            class="relative hidden min-h-dvh overflow-hidden bg-[#070707] lg:flex"
        >


            

            <div
                class="absolute inset-0 bg-gradient-to-br from-black via-[#080808] to-[#250607]"
            ></div>


            

            <div
                class="absolute -left-56 -top-56 h-[620px] w-[620px] rounded-full bg-red-600/[0.07] blur-[140px]"
            ></div>


            <div
                class="absolute -bottom-64 right-[-180px] h-[650px] w-[650px] rounded-full bg-red-600/[0.08] blur-[150px]"
            ></div>


            

            <div
                class="absolute left-0 top-0 h-full w-[3px] bg-red-600"
            ></div>


            

            <div
                class="absolute right-[-100px] top-[-110px] h-[300px] w-[300px] rotate-45 border border-red-600/[0.15]"
            ></div>


            <div
                class="absolute bottom-[-220px] left-[30%] h-[460px] w-[460px] rotate-45 border border-red-600/[0.08]"
            ></div>


            

            <div
                class="relative z-10 flex min-h-dvh w-full flex-col px-10 py-9 xl:px-14 xl:py-10 2xl:px-16 2xl:py-12"
            >


                

                <div
                    class="flex items-center"
                >

                    <div
                        class="flex flex-1 items-center translate-y-8 rounded-2xl border border-white/[0.06]"
                    >

                        <img
                            src="<?php echo e(asset('images/logo-rizky-moto-shop.png')); ?>"
                            alt="Rizky Moto Shop"
                            class="block h-auto w-[225px] object-contain object-left xl:w-[455px] 2xl:w-[555px]"
                        >

                    </div>

                </div>


                

                <div
                    class="flex flex-1 items-center"
                >

                    <div
                        class="w-full max-w-[690px]"
                    >


                        

                        <div
                            class="mb-6 flex items-center gap-3"
                        >

                            <span
                                class="relative flex h-2 w-2"
                            >

                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-500 opacity-30"
                                ></span>

                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full bg-red-500"
                                ></span>

                            </span>


                            <span
                                class="text-[10px] font-bold uppercase tracking-[0.34em] text-red-500 xl:text-xs"
                            >
                                AI CREATIVE TOOLS
                            </span>

                        </div>


                        

                        <h1
                            class="max-w-[650px] text-[42px] font-bold leading-[1.02] tracking-[-0.035em] text-white xl:text-[49px] 2xl:text-[54px]"
                        >

                            Satu Platform untuk

                            <span
                                class="mt-1 block text-red-500"
                            >
                                Banyak Kreativitas.
                            </span>

                        </h1>


                        

                        <p
                            class="mt-7 max-w-[560px] text-sm leading-7 text-zinc-400 xl:text-[15px]"
                        >
                            Manfaatkan teknologi AI untuk membuat konten,
                            desain, dan materi promosi produk dengan lebih
                            cepat dan profesional.
                        </p>


                        

                        <div
                            class="mt-10 grid max-w-[650px] grid-cols-3"
                        >


                            

                            <div
                                class="border-l border-red-600 pl-4 pr-5"
                            >

                                <div
                                    class="mb-2 h-1 w-7 rounded-full bg-red-600"
                                ></div>

                                <p
                                    class="text-xs font-bold text-white xl:text-sm"
                                >
                                    Konten Berkualitas
                                </p>

                                <p
                                    class="mt-1 text-[10px] leading-5 text-zinc-500 xl:text-xs"
                                >
                                    Visual profesional
                                </p>

                            </div>


                            

                            <div
                                class="border-l border-red-600 px-5"
                            >

                                <div
                                    class="mb-2 h-1 w-7 rounded-full bg-red-600"
                                ></div>

                                <p
                                    class="text-xs font-bold text-white xl:text-sm"
                                >
                                    Proses Lebih Cepat
                                </p>

                                <p
                                    class="mt-1 text-[10px] leading-5 text-zinc-500 xl:text-xs"
                                >
                                    Dibantu teknologi AI
                                </p>

                            </div>


                            

                            <div
                                class="border-l border-red-600 pl-5"
                            >

                                <div
                                    class="mb-2 h-1 w-7 rounded-full bg-red-600"
                                ></div>

                                <p
                                    class="text-xs font-bold text-white xl:text-sm"
                                >
                                    Mudah Digunakan
                                </p>

                                <p
                                    class="mt-1 text-[10px] leading-5 text-zinc-500 xl:text-xs"
                                >
                                    Satu platform
                                </p>

                            </div>

                        </div>

                    </div>

                </div>


                

                <div
                    class="flex items-center justify-between"
                >

                    <span
                        class="text-[10px] text-zinc-600 xl:text-xs"
                    >
                        Internal Creative Platform
                    </span>


                    <div
                        class="flex items-center gap-2"
                    >

                        <span
                            class="h-1.5 w-1.5 rounded-full bg-red-600"
                        ></span>

                        <span
                            class="text-[10px] text-zinc-600 xl:text-xs"
                        >
                            v1.0
                        </span>

                    </div>

                </div>

            </div>

        </section>



        

        <section
            class="flex min-h-dvh items-center justify-center bg-white px-5 py-7 sm:px-8 sm:py-10 lg:px-10 xl:px-14 2xl:px-20"
        >

            <div
                class="w-full max-w-[425px]"
            >


                

                <div
                    class="mb-8 flex justify-center sm:mb-10 lg:hidden"
                >

                    <div
                        class="relative flex items-center justify-center"
                    >

                        

                        <span
                            class="absolute -left-3 top-1/2 h-5 w-[2px] -translate-y-1/2 rounded-full"
                        ></span>


                        <img
                            src="<?php echo e(asset('images/logo-rizky-moto-shop.png')); ?>"
                            alt="Rizky Moto Shop"
                            class="block h-auto w-[205px] object-contain drop-shadow-[0_8px_12px_rgba(0,0,0,0.12)] sm:w-[225px]"
                        >

                    </div>

                </div>



                

                <div
                    class="mb-7 sm:mb-9"
                >


                    

                    <div
                        class="mb-2.5 flex items-center gap-2"
                    >

                        <span
                            class="h-1.5 w-1.5 rounded-full bg-red-600"
                        ></span>


                        <span
                            class="text-[9px] font-bold uppercase tracking-[0.3em] text-red-600 sm:text-[10px]"
                        >
                            INTERNAL ACCESS
                        </span>

                    </div>


                    

                    <h2
                        class="text-[28px] font-bold leading-tight tracking-[-0.025em] text-zinc-900 sm:text-3xl lg:text-[34px]"
                    >
                        Selamat datang.
                    </h2>


                    

                    <p
                        class="mt-2.5 max-w-[390px] text-[12px] leading-5 text-zinc-500 sm:text-sm sm:leading-6"
                    >
                        Masuk untuk mengakses AI Creative Tools
                        Rizky Moto Shop.
                    </p>

                </div>



                

                <form
                    x-data="loginForm()"
                    @submit.prevent="startLogin"
                    class="space-y-4 sm:space-y-5"
                >


                    

                    <div>

                        <label
                            for="email"
                            class="mb-2 block text-xs font-semibold text-zinc-700 sm:text-sm"
                        >
                            Email
                        </label>


                        <div
                            class="relative"
                        >

                            <input
                                wire:model="email"
                                id="email"
                                type="email"
                                autocomplete="email"
                                placeholder="admin@example.com"
                                autofocus
                                class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 text-xs text-zinc-900 outline-none transition-all duration-200 placeholder:text-zinc-400 hover:border-zinc-300 focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10 sm:h-12 sm:px-4 sm:text-sm"
                            >

                        </div>

                    </div>



                    

                    <div
                        x-data="{ show: false }"
                    >

                        <label
                            for="password"
                            class="mb-2 block text-xs font-semibold text-zinc-700 sm:text-sm"
                        >
                            Password
                        </label>


                        <div
                            class="relative"
                        >

                            <input
                                wire:model="password"
                                id="password"
                                x-bind:type="show ? 'text' : 'password'"
                                autocomplete="current-password"
                                placeholder="Masukkan password"
                                class="h-11 w-full rounded-xl border border-zinc-200 bg-zinc-50 px-3.5 pr-11 text-xs text-zinc-900 outline-none transition-all duration-200 placeholder:text-zinc-400 hover:border-zinc-300 focus:border-red-500 focus:bg-white focus:ring-4 focus:ring-red-500/10 sm:h-12 sm:px-4 sm:pr-12 sm:text-sm"
                            >


                            <button
                                type="button"
                                @click="show = !show"
                                class="absolute right-2.5 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-lg text-zinc-400 transition hover:bg-zinc-100 hover:text-red-600 sm:right-3"
                                aria-label="Tampilkan password"
                            >


                                

                                <svg
                                    x-show="!show"
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="h-4 w-4 sm:h-5 sm:w-5"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M2.036 12.322a1.012 1.012 0 010-.644C3.423 7.51 7.36 5 12 5c4.64 0 8.577 2.51 9.964 6.678.05.15.05.314 0 .644C20.577 16.49 16.64 19 12 19c-4.64 0-8.577-2.51-9.964-6.678z"
                                    />

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                    />

                                </svg>


                                

                                <svg
                                    x-show="show"
                                    x-cloak
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.8"
                                    stroke="currentColor"
                                    class="h-4 w-4 sm:h-5 sm:w-5"
                                >

                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.39 7.254 19 12 19c.856 0 1.68-.11 2.46-.318M6.228 6.228A10.451 10.451 0 0112 5c4.746 0 8.774 2.61 10.066 7a10.47 10.47 0 01-1.743 3.362M6.228 6.228L3 3m3.228 3.228l12.544 12.544"
                                    />

                                </svg>

                            </button>

                        </div>



                    </div>



                    

                    <div
                        class="flex min-h-9 items-center justify-between gap-3"
                    >

                        <label
                            class="flex cursor-pointer items-center gap-2"
                        >

                            <input
                                wire:model="remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-zinc-300 text-red-600 focus:ring-red-500"
                            >

                            <span
                                class="text-xs text-zinc-500 sm:text-sm"
                            >
                                Ingat saya
                            </span>

                        </label>


                        <span
                            class="text-[10px] text-zinc-400 sm:text-xs"
                        >
                            Internal Account
                        </span>

                    </div>



                    

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="login"
                        class="group relative flex h-12 w-full items-center justify-center overflow-hidden rounded-xl bg-red-500 px-5 text-sm font-bold text-white shadow-[0_10px_25px_rgba(239,68,68,0.20)] transition-all duration-300 hover:bg-red-600 hover:shadow-[0_12px_30px_rgba(239,68,68,0.28)] active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-90"
                    >
                        
                        <span
                            wire:loading.remove.delay
                            wire:target="login"
                            class="flex items-center justify-center gap-2"
                        >
                            <span>MASUK</span>

                            <svg
                                class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5"
                                viewBox="0 0 20 20"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <path
                                    d="M4 10H16"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                />
                                <path
                                    d="M11.5 5.5L16 10L11.5 14.5"
                                    stroke="currentColor"
                                    stroke-width="1.8"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>

                        
                        <span
                            wire:loading.delay.flex
                            wire:target="login"
                            class="hidden items-center justify-center gap-2"
                        >
                            <svg
                                class="h-4 w-4 animate-spin"
                                viewBox="0 0 24 24"
                                fill="none"
                                xmlns="http://www.w3.org/2000/svg"
                            >
                                <circle
                                    cx="12"
                                    cy="12"
                                    r="9"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    class="opacity-25"
                                />

                                <path
                                    d="M21 12C21 16.9706 16.9706 21 12 21"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    stroke-linecap="round"
                                />
                            </svg>

                            <span>Memproses...</span>
                        </span>
                    </button>

                </form>



                

                <div
                    class="mt-5 rounded-xl border border-zinc-200 bg-zinc-50/80 p-3.5 sm:mt-7 sm:p-4"
                >

                    <div
                        class="flex items-start gap-3"
                    >


                        

                        <div
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-600 ring-1 ring-red-100"
                        >

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                                class="h-4 w-4"
                            >

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.5 10.5V7.125a4.5 4.5 0 00-9 0V10.5m-.75 0h10.5A1.5 1.5 0 0118.75 12v7.125a1.5 1.5 0 01-1.5 1.5h-10.5a1.5 1.5 0 01-1.5-1.5V12a1.5 1.5 0 011.5-1.5z"
                                />

                            </svg>

                        </div>


                        <div
                            class="min-w-0"
                        >

                            <p
                                class="text-[11px] font-semibold text-zinc-800 sm:text-xs"
                            >
                                Akses terbatas
                            </p>


                            <p
                                class="mt-0.5 text-[9px] leading-4 text-zinc-500 sm:text-[11px] sm:leading-5"
                            >
                                Platform ini digunakan untuk kebutuhan
                                internal Rizky Moto Shop.
                            </p>

                        </div>

                    </div>

                </div>



                

                <div
                    class="mt-5 flex items-center justify-center gap-2 sm:mt-7"
                >

                    <span
                        class="h-1 w-1 rounded-full bg-red-500"
                    ></span>

                    <p
                        class="text-[9px] text-zinc-400 sm:text-xs"
                    >
                        © <?php echo e(date('Y')); ?> Rizky Moto Shop
                    </p>

                </div>


            </div>

        </section>

    </div>

</div><?php /**PATH F:\Website\rizky-tools-ai\resources\views\pages\auth\login.blade.php ENDPATH**/ ?>