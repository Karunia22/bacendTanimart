<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin TaniMart')</title>
    <script>
        // Terapkan tema sebelum paint agar tidak berkedip.
        if (localStorage.getItem('tanimart-admin-theme') === 'dark' ||
            (!localStorage.getItem('tanimart-admin-theme') &&
             window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- @font-face (Roboto + Material Symbols) dari fonts-manifest.json.
         WAJIB ada: tanpa ini ikon ligature tampil sebagai teks (English). --}}
    {{ app(\Illuminate\Foundation\Vite::class)->fonts() }}
</head>
<body class="h-full bg-background text-on-background">

    <div class="min-h-full flex">

        {{-- ===== Navigation Drawer ===== --}}
        {{-- Desktop: permanen. Mobile: overlay + scrim (toggle via checkbox). --}}
        <input type="checkbox" id="drawer-toggle" class="peer sr-only">

        {{-- Scrim (mobile saja) --}}
        <label for="drawer-toggle"
            class="fixed inset-0 z-30 bg-scrim/40 hidden max-lg:peer-checked:block"
            aria-hidden="true"></label>

        <aside class="fixed lg:static inset-y-0 left-0 z-40 w-64 shrink-0
                      bg-surface-container
                      -translate-x-full lg:translate-x-0
                      peer-checked:translate-x-0
                      transition-transform duration-200 ease-out
                      flex flex-col">

            {{-- Brand --}}
            <div class="h-16 flex items-center gap-3 px-6 shrink-0">
                <span class="material-symbols-outlined filled text-primary text-[28px]" aria-hidden="true">storefront</span>
                <span class="text-lg font-medium text-on-surface">TaniMart Admin</span>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-2 space-y-1">
                @php
                    $menus = [
                        ['route' => 'admin.dashboard', 'icon' => 'dashboard', 'label' => 'Dasbor'],
                        ['route' => 'admin.seller-applications.index', 'icon' => 'how_to_reg', 'label' => 'Pengajuan Penjual'],
                        ['route' => 'admin.users.index', 'icon' => 'people', 'label' => 'Pengguna'],
                        ['route' => 'admin.shops.index', 'icon' => 'store', 'label' => 'Toko'],
                        ['route' => 'admin.products.index', 'icon' => 'inventory_2', 'label' => 'Produk'],
                        ['route' => 'admin.orders.index', 'icon' => 'receipt_long', 'label' => 'Pesanan'],
                        ['route' => 'admin.analytics.index', 'icon' => 'monitoring', 'label' => 'Analitik'],
                    ];
                @endphp

                @foreach ($menus as $menu)
                    <a href="{{ route($menu['route']) }}"
                        class="md-nav-item {{ request()->routeIs($menu['route']) ? 'active' : '' }}">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">{{ $menu['icon'] }}</span>
                        <span>{{ $menu['label'] }}</span>
                    </a>
                @endforeach

                <form method="POST" action="{{ route('admin.logout') }}" class="pt-2">
                    @csrf
                    <button type="submit" class="md-nav-item w-full text-left">
                        <span class="material-symbols-outlined text-[20px]" aria-hidden="true">logout</span>
                        <span>Keluar</span>
                    </button>
                </form>
            </nav>

            <div class="px-6 py-4 text-xs text-on-surface-variant shrink-0">
                {{ auth()->user()?->name ?? 'Admin' }}
            </div>
        </aside>

        {{-- ===== Konten ===== --}}
        <div class="flex-1 min-w-0 flex flex-col">

            {{-- Top app bar --}}
            <header class="h-16 sticky top-0 z-20 bg-surface-container
                           flex items-center gap-2 px-4 shadow-elev-1">
                <label for="drawer-toggle"
                    class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-full
                           text-on-surface-variant hover:bg-on-surface/8 cursor-pointer"
                    aria-label="Buka menu">
                    <span class="material-symbols-outlined text-[24px]" aria-hidden="true">menu</span>
                </label>

                <h1 class="text-lg font-medium text-on-surface truncate">
                    @yield('title', 'Admin TaniMart')
                </h1>

                <div class="flex-1"></div>

                {{-- Toggle tema gelap/terang --}}
                <button type="button" onclick="TaniMartTheme.toggle()"
                    class="inline-flex items-center justify-center w-10 h-10 rounded-full
                           text-on-surface-variant hover:bg-on-surface/8 transition-colors duration-150"
                    aria-label="Ganti tema">
                    <span class="material-symbols-outlined text-[22px] dark:hidden" aria-hidden="true">dark_mode</span>
                    <span class="material-symbols-outlined text-[22px] hidden dark:inline" aria-hidden="true">light_mode</span>
                </button>
            </header>

            {{-- Isi halaman --}}
            <main class="flex-1 p-4 lg:p-6 bg-surface-container-low">
                @yield('content')
            </main>
        </div>
    </div>

    {{-- Snackbar flash sesi (sukses dari approve/reject, error validasi) --}}
    @if (session('snackbar_success') || session('snackbar_error'))
        @php
            $snackSuccess = session('snackbar_success');
            $snackError = session('snackbar_error');
        @endphp
        <div data-snackbar role="status"
            class="fixed bottom-4 left-1/2 -translate-x-1/2 z-50 min-w-80 max-w-md
                   rounded-lg shadow-elev-3 px-4 py-3 text-sm flex items-start gap-2
                   {{ $snackError ? 'bg-error-container text-on-error-container'
                                  : 'bg-inverse-surface text-inverse-on-surface' }}"
            hidden>
            <span class="material-symbols-outlined text-[18px] mt-0.5 shrink-0" aria-hidden="true">
                {{ $snackError ? 'error' : 'check_circle' }}
            </span>
            <span>{{ $snackError ?: $snackSuccess }}</span>
        </div>
    @endif

    <script>
        // Tampilkan snackbar sesi (flash) otomatis, sembunyikan setelah 4 detik.
        document.querySelectorAll('[data-snackbar]').forEach(function (el) {
            el.hidden = false;
            setTimeout(function () { el.hidden = true; }, 4000);
        });
    </script>

    @stack('scripts')
</body>
</html>
