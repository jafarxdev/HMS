<section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-sm">
    <p class="text-sm font-semibold text-teal-700">Hospital Management System</p>
    <h1 class="mt-2 text-3xl font-bold">Sign in</h1>
    <p class="mt-2 text-sm text-slate-500">Use your staff account to continue.</p>
    <form wire:submit="login" class="mt-8 grid gap-5">
        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" type="email" wire:model="email" autocomplete="username" required autofocus class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
            @error('email') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" type="password" wire:model="password" autocomplete="current-password" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2">
            @error('password') <p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
        </div>
        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-teal-700 px-4 py-3 font-semibold text-white disabled:opacity-50">
            <span wire:loading.remove>Sign in</span><span wire:loading>Signing in…</span>
        </button>
    </form>
</section>
