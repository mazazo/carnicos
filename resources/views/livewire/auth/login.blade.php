<x-layouts.guest>
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold">Iniciar sesion</h1>

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

        <form wire:submit="login" class="mt-5 space-y-4">
            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input wire:model="email" type="email" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1 block text-sm font-medium">Contrasena</label>
                <input wire:model="password" type="password" @disabled($isLocked) class="w-full rounded-lg border border-slate-300 px-3 py-2 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400">
                @error('password') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input wire:model="remember" type="checkbox" @disabled($isLocked) class="rounded border-slate-300 disabled:cursor-not-allowed">
                Recordarme
            </label>

            <button type="submit" @disabled($isLocked) class="w-full rounded-lg bg-slate-900 px-4 py-2 font-medium text-white disabled:cursor-not-allowed disabled:bg-slate-400">Entrar</button>
        </form>

        <p class="mt-4 text-sm text-slate-600">
            No tienes cuenta?
            <a href="{{ route('register.create-user') }}" class="font-medium text-slate-900 underline">Crear usuario</a>
        </p>
    </div>
</x-layouts.guest>
