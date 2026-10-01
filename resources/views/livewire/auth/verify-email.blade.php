<x-layouts.guest>
    <div class="rounded-xl border border-stone-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold">Verifica tu correo</h1>
        <p class="mt-1 text-sm text-stone-600">
            Te enviamos un enlace de verificacion a tu correo electronico. Por favor revisalo y haz clic en el enlace para activar tu cuenta.
        </p>

        @if ($resent)
            <p class="mt-3 rounded-lg border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-700">
                El correo de verificacion fue reenviado. Revisa tu bandeja de entrada (y la carpeta de spam).
            </p>
        @endif

        <div class="mt-5 space-y-3">
            <button
                wire:click="resend"
                wire:loading.attr="disabled"
                class="w-full rounded-lg bg-amber-500 px-4 py-2.5 font-bold text-stone-950 hover:bg-amber-400 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="resend">Reenviar correo de verificacion</span>
                <span wire:loading wire:target="resend">Enviando...</span>
            </button>

            <button
                wire:click="logout"
                class="w-full rounded-lg border border-stone-300 px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
            >
                Cerrar sesión
            </button>
        </div>
    </div>
</x-layouts.guest>
