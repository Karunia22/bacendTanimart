<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - TaniMart</title>
    <script>
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
<body class="h-full bg-background text-on-background flex items-center justify-center p-4">

    <div class="w-full max-w-sm bg-surface-container-high rounded-xl shadow-elev-3 p-8 flex flex-col gap-5">

        <div class="flex flex-col items-center gap-2">
            <span class="material-symbols-outlined filled text-primary text-[48px]" aria-hidden="true">agriculture</span>
            <h1 class="text-2xl font-normal text-on-surface">Login Admin</h1>
            <p class="text-sm text-on-surface-variant">TaniMart — Panel Pengelolaan</p>
        </div>

        @if ($errors->any())
            <div class="rounded-lg bg-error-container text-on-error-container px-4 py-3 text-sm"
                 role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="flex flex-col gap-4">
            @csrf

            <div class="flex flex-col gap-1">
                <label for="email" class="text-sm font-medium text-on-surface-variant">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                    class="h-14 px-4 rounded-t-lg rounded-b-sm bg-surface-container-highest border-b-2
                           border-outline text-on-surface placeholder:text-on-surface-variant/60
                           focus:border-primary focus:outline-none transition-colors duration-150">
            </div>

            <div class="flex flex-col gap-1">
                <label for="password" class="text-sm font-medium text-on-surface-variant">Password</label>
                <input id="password" name="password" type="password" required
                    class="h-14 px-4 rounded-t-lg rounded-b-sm bg-surface-container-highest border-b-2
                           border-outline text-on-surface placeholder:text-on-surface-variant/60
                           focus:border-primary focus:outline-none transition-colors duration-150">
            </div>

            <label class="flex items-center gap-2 text-sm text-on-surface-variant cursor-pointer">
                <input type="checkbox" name="remember" class="w-4 h-4 accent-primary">
                Ingat saya
            </label>

            <button type="submit"
                class="mt-2 h-10 rounded-full bg-primary text-on-primary text-sm font-medium
                       hover:shadow-elev-1 transition-shadow md-ripple">
                Masuk
            </button>
        </form>
    </div>

</body>
</html>
