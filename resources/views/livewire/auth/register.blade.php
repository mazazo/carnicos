@php
    $campo = 'w-full rounded-lg border border-stone-300 px-3 py-2 text-sm disabled:cursor-not-allowed';
    $etiqueta = 'mb-1 block text-sm font-medium text-stone-700';
@endphp
<x-layouts.guest>
    <div class="w-full">
        <div class="mb-4">
            <h1 class="text-2xl font-bold tracking-tight text-stone-900">Creá tu carnicería</h1>
            <p class="mt-1 text-sm text-stone-500">Completá tus datos y empezá la prueba gratis.</p>
        </div>

        @if ($showWarning)
            <x-ui.alert tone="amber" class="mb-3">
                Ya usaste este acceso varias veces desde esta conexión. Te quedan {{ $attemptsRemaining }} intentos antes de que se bloquee por un rato.
            </x-ui.alert>
        @endif

        @if ($isLocked)
            <x-ui.alert tone="red" class="mb-3">
                Alcanzaste el límite de intentos. Probá de nuevo en {{ ceil($retryAfter / 60) }} minuto(s).
            </x-ui.alert>
        @endif

        <form wire:submit="register" class="space-y-3">
            <div>
                <label for="carniceria" class="{{ $etiqueta }}">Nombre de la carnicería</label>
                <input id="carniceria" wire:model="carniceria" type="text" autocomplete="organization" @disabled($isLocked) class="{{ $campo }}" placeholder="Ej: Carnicería Don Juan">
                @error('carniceria')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="name" class="{{ $etiqueta }}">Nombre</label>
                    <input id="name" wire:model="name" type="text" autocomplete="given-name" @disabled($isLocked) class="{{ $campo }}" placeholder="Ej: Juan">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="last_name" class="{{ $etiqueta }}">Apellido <span class="font-normal text-stone-400">(opcional)</span></label>
                    <input id="last_name" wire:model="last_name" type="text" autocomplete="family-name" @disabled($isLocked) class="{{ $campo }}" placeholder="Ej: Pérez">
                    @error('last_name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="email" class="{{ $etiqueta }}">Email</label>
                    <input id="email" wire:model="email" type="email" autocomplete="email" @disabled($isLocked) class="{{ $campo }}" placeholder="correo@empresa.com">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="movil" class="{{ $etiqueta }}">Número de móvil</label>
                    <input id="movil" wire:model="movil" type="text" inputmode="tel" autocomplete="tel" @disabled($isLocked) class="{{ $campo }}" placeholder="Ej: 1155554444">
                    @error('movil')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="password" class="{{ $etiqueta }}">Contraseña</label>
                    <input id="password" wire:model="password" type="password" autocomplete="new-password" @disabled($isLocked) class="{{ $campo }}">
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="{{ $etiqueta }}">Confirmar contraseña</label>
                    <input id="password_confirmation" wire:model="password_confirmation" type="password" autocomplete="new-password" @disabled($isLocked) class="{{ $campo }}">
                </div>
            </div>
            <p class="text-xs text-stone-400">Mínimo 8 caracteres, con mayúscula, minúscula, número y símbolo.</p>

            <button type="submit" @disabled($isLocked) wire:loading.attr="disabled"
                    class="w-full rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-stone-950 shadow-sm transition hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50">
                <span wire:loading.remove wire:target="register">Crear cuenta</span>
                <span wire:loading wire:target="register">Creando…</span>
            </button>
        </form>

        <p class="mt-4 text-sm text-stone-600">
            ¿Ya tenés cuenta?
            <a href="{{ route('login') }}" class="font-semibold text-amber-700 hover:underline">Iniciar sesión</a>
        </p>
    </div>
</x-layouts.guest>
