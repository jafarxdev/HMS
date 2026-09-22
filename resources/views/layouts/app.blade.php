<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Hospital Management System' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-800">
    <div class="min-h-screen lg:grid lg:grid-cols-[16rem_1fr]">
        <aside class="bg-slate-900 p-6 text-slate-200">
            <a href="{{ route('dashboard') }}" class="text-xl font-bold tracking-tight text-white">HMS<span class="text-teal-400"> / Care</span></a>
            <p class="mt-2 text-xs text-slate-400">Hospital Management System</p>
            <nav aria-label="Main navigation" class="mt-8 flex flex-wrap gap-2 lg:flex-col">
                @foreach (['dashboard' => 'Dashboard', 'departments' => 'Departments', 'doctors' => 'Doctors', 'patients' => 'Patients', 'appointments' => 'Appointments', 'medical-records' => 'Medical Records', 'prescriptions' => 'Prescriptions'] as $routeName => $label)
                    @if (Route::has($routeName) && ($routeName === 'dashboard' || auth()->user()->can('manage-'.$routeName)))
                        <a wire:key="nav-{{ $routeName }}" href="{{ route($routeName) }}"
                            @class(['rounded-lg px-4 py-3 text-sm font-medium hover:bg-slate-800', 'bg-teal-700 text-white' => request()->routeIs($routeName)])>{{ $label }}</a>
                    @endif
                @endforeach
            </nav>
        </aside>
        <div class="min-w-0">
            <header class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                <div><p class="text-sm font-semibold">{{ auth()->user()->name }}</p><p class="text-xs capitalize text-slate-500">{{ auth()->user()->role }}</p></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<x-button type="submit" variant="secondary">Sign out</x-button></form>
            </header>
            <main id="main-content" class="mx-auto max-w-7xl p-4 sm:p-8">{{ $slot }}</main>
        </div>
    </div>
    @livewireScripts
</body>
</html>
