<x-layouts.guest>
    <div class="flex h-full flex-col justify-center">
        <h1 class="text-2xl font-bold tracking-tight text-stone-900">Iniciar sesión</h1>
        <p class="mt-1 text-sm text-stone-500">Entrá con tu email y contraseña.</p>

        @if ($showWarning)
            <x-ui.alert tone="amber" class="mt-4">
                Ya usaste este acceso varias veces desde esta conexión. Te quedan {{ $attemptsRemaining }} intentos antes de que se bloquee por un rato.
            </x-ui.alert>
        @endif

        @if ($isLocked)
            <x-ui.alert tone="red" class="mt-4">
                Alcanzaste el límite de intentos. Probá de nuevo en {{ ceil($retryAfter / 60) }} minuto(s) o
                <a href="{{ route('register.create-user') }}" class="font-semibold underline">creá tu cuenta</a>.
            </x-ui.alert>
        @endif

        <form wire:submit="login" class="mt-5 space-y-4">
            <div>
                <label for="email" class="mb-1 block text-sm font-medium text-stone-700">Email</label>
                <input id="email" wire:model="email" type="email" autocomplete="email" autofocus @disabled($isLocked)
                       class="w-full rounded-lg border border-stone-300 px-3 py-2.5 disabled:cursor-not-allowed">
                @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label for="password" class="mb-1 block text-sm font-medium text-stone-700">Contraseña</label>
                <input id="password" wire:model="password" type="password" autocomplete="current-password" @disabled($isLocked)
                       class="w-full rounded-lg border border-stone-300 px-3 py-2.5 disabled:cursor-not-allowed">
                @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <label class="flex items-center gap-2 text-sm text-stone-600">
                <input wire:model="remember" type="checkbox" @disabled($isLocked) class="rounded border-stone-300 text-amber-500 focus:ring-amber-500 disabled:cursor-not-allowed">
                Recordarme
            </label>

            <button type="submit" @disabled($isLocked) wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-stone-950 shadow-sm transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="login">Entrar</span>
                <span wire:loading wire:target="login">Entrando…</span>
            </button>
        </form>

        <p class="mt-5 text-sm text-stone-600">
            ¿No tenés cuenta?
            <a href="{{ route('register.create-user') }}" class="font-semibold text-amber-700 hover:underline">Creá tu carnicería</a>
        </p>
    </div>
</x-layouts.guest>
