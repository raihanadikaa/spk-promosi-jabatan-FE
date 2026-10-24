<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — SPK Promosi Jabatan</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-600 to-indigo-800">

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">SPK Promosi Jabatan</h1>
            <p class="text-sm text-gray-500 mt-1">Silakan masuk untuk melanjutkan</p>
        </div>

        {{-- Alert sukses --}}
        @if (session('success'))
            <div class="mb-4 p-3 rounded-lg bg-green-100 text-green-700 text-sm">
                {{ session('success') }}
            </div>
        @endif

        {{-- Error umum --}}
        @if ($errors->any())
            <div class="mb-4 p-3 rounded-lg bg-red-100 text-red-700 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}" class="space-y-4">
            @csrf

            {{-- Email --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    type="email"
                    name="email"
                    id="email"
                    value="{{ old('email') }}"
                    placeholder="nama@perusahaan.com"
                    required
                    autofocus
                    class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('email') border-red-500 @enderror"
                >
                @error('email')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Password --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <div class="relative">
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="••••••••"
                        required
                        minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror"
                    >
                    <button type="button" onclick="togglePassword()"
                            class="absolute right-3 top-2 text-gray-500 text-sm">
                        <span id="toggleText">Lihat</span>
                    </button>
                </div>
                @error('password')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Remember --}}
            <div class="flex items-center">
                <input type="checkbox" name="remember" id="remember"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="remember" class="ml-2 text-sm text-gray-600">Ingat saya</label>
            </div>

            {{-- Submit --}}
            <button type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-lg transition">
                Login
            </button>
        </form>

        <p class="text-center text-xs text-gray-400 mt-6">
            © {{ date('Y') }} SPK Promosi Jabatan
        </p>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const text  = document.getElementById('toggleText');
            if (input.type === 'password') {
                input.type = 'text';
                text.textContent = 'Sembunyi';
            } else {
                input.type = 'password';
                text.textContent = 'Lihat';
            }
        }
    </script>
</body>
</html>