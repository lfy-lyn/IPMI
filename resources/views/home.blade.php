<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>IPMI - Ikatan Pelajar Masjid Indonesia</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex flex-col">
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex justify-between items-center">
                <h1 class="text-3xl font-bold text-gray-800">IPMI</h1>
                <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-800">Login</a>
            </div>
        </header>

        <main class="flex-grow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="text-center">
                    <h2 class="text-4xl font-bold text-gray-900 mb-4">
                        Ikatan Pelajar Masjid Indonesia
                    </h2>
                    <p class="text-xl text-gray-600 mb-8">
                        Sistem Informasi Organisasi IPMI
                    </p>
                    <div class="space-x-4">
                        <a href="{{ route('login') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded">
                            Login Anggota
                        </a>
                    </div>
                </div>

                <div class="mt-16 grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Kaligrafi</h3>
                        <p class="text-gray-600">Seni kaligrafi Islam</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">MTQ</h3>
                        <p class="text-gray-600">Musabaqah Tilawatil Quran</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Dakwah</h3>
                        <p class="text-gray-600">Dakwah dan kajian Islam</p>
                    </div>
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Hadroh</h3>
                        <p class="text-gray-600">Seni hadroh dan rebana</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-white shadow mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-gray-600">&copy; {{ date('Y') }} IPMI. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>