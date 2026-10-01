<x-layouts.guest>
    <div class="w-full">
        <div class="mb-6">
            <p class="text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Registro</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-900">Crear cuenta</h1>
            <p class="mt-2 text-sm text-slate-600">Completa tus datos para comenzar a usar el sistema.</p>
        </div>

        @if ($showWarning)
            <p class="mb-4 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-700">
                Aviso: ya usaste este acceso varias veces desde esta IP. Te quedan {{ $attemptsRemaining }} intentos antes de mostrar el cartel de suscripcion.
            </p>
        @endif

        @if ($isLocked)
            <p class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                Alcanzaste el limite de intentos para esta IP. Intenta nuevamente en {{ ceil($retryAfter / 60) }} minuto(s) o suscribite para continuar.
            </p>
            <p class="mb-4 text-sm text-slate-600">
                <a href="{{ route('register.create-user') }}" class="font-medium text-slate-900 underline">Ver suscripcion</a>
            </p>
        @endif

        <form wire:submit="register" class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre de la carniceria</label>
                <input wire:model="carniceria" type="text" autocomplete="organization" @disabled($isLocked) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Ej: Carniceria Don Juan">
                @error('carniceria') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nombre</label>
                    <input wire:model="name" type="text" autocomplete="given-name" @disabled($isLocked) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Ej: Juan">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Apellido (opcional)</label>
                    <input wire:model="last_name" type="text" autocomplete="family-name" @disabled($isLocked) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Ej: Perez">
                    @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                <input wire:model="email" type="email" autocomplete="email" @disabled($isLocked) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="correo@empresa.com">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Numero de movil</label>
                <input wire:model="movil" type="text" inputmode="tel" autocomplete="tel" @disabled($isLocked) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Ej: 70012345 o +59170012345">
                @error('movil') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Contrasena</label>
                <input wire:model="password" type="password" autocomplete="new-password" @disabled($isLocked) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Minimo 8, mayuscula, minuscula, numero y simbolo">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Confirmar contrasena</label>
                <input wire:model="password_confirmation" type="password" autocomplete="new-password" @disabled($isLocked) class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-slate-400 focus:ring-2 focus:ring-slate-200 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
            </div>

            <button type="submit" @disabled($isLocked) class="w-full rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-400">
                Registrarme
            </button>
        </form>

        <p class="mt-5 text-sm text-slate-600">
            Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-medium text-slate-900 underline">Iniciar sesion</a>
        </p>
    </div>
</x-layouts.guest>
