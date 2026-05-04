<x-layouts.guest>
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold">Crear cuenta</h1>
        <p class="mt-1 text-sm text-slate-600">Completa tus datos para comenzar a usar el sistema.</p>

        @if ($showWarning)
            <p class="mt-3 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-700">
                Aviso: ya usaste este acceso varias veces desde esta IP. Te quedan {{ $attemptsRemaining }} intentos antes de mostrar el cartel de suscripcion.
            </p>
        @endif

        @if ($isLocked)
            <p class="mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-700">
                Alcanzaste el limite de intentos para esta IP. Intenta nuevamente en {{ ceil($retryAfter / 60) }} minuto(s) o suscribite para continuar.
            </p>
            <p class="mt-2 text-sm text-slate-600">
                <a href="{{ route('register.create-user') }}" class="font-medium text-slate-900 underline">Ver suscripcion</a>
            </p>
        @endif

        <form wire:submit="register" class="mt-5 space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="mb-1 block text-sm font-medium">Nombre</label>
                    <input wire:model="name" type="text" autocomplete="given-name" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Ej: Juan">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">Apellido (opcional)</label>
                    <input wire:model="last_name" type="text" autocomplete="family-name" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Ej: Perez">
                    @error('last_name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input wire:model="email" type="email" autocomplete="email" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="correo@empresa.com">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Numero de movil</label>
                <input wire:model="movil" type="text" inputmode="tel" autocomplete="tel" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Ej: 70012345 o +59170012345">
                @error('movil') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Contrasena</label>
                <input wire:model="password" type="password" autocomplete="new-password" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400" placeholder="Minimo 8, mayuscula, minuscula, numero y simbolo">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Confirmar contrasena</label>
                <input wire:model="password_confirmation" type="password" autocomplete="new-password" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
            </div>

            <button type="submit" @disabled($isLocked) class="w-full rounded-lg bg-slate-900 px-4 py-2 font-medium text-white disabled:cursor-not-allowed disabled:bg-slate-400">Registrarme</button>
        </form>

        <p class="mt-4 text-sm text-slate-600">
            Ya tienes cuenta?
            <a href="{{ route('login') }}" class="font-medium text-slate-900 underline">Iniciar sesion</a>
        </p>
    </div>
</x-layouts.guest>
