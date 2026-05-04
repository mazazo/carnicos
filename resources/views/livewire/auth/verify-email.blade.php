<x-layouts.guest>
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h1 class="text-xl font-semibold">Verifica tu correo</h1>
        <p class="mt-1 text-sm text-slate-600">
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
                class="w-full rounded-lg bg-slate-900 px-4 py-2 font-medium text-white disabled:cursor-not-allowed disabled:bg-slate-400"
            >
                <span wire:loading.remove wire:target="resend">Reenviar correo de verificacion</span>
                <span wire:loading wire:target="resend">Enviando...</span>
            </button>

            <button
                wire:click="logout"
                class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                Cerrar sesion
            </button>
        </div>
    </div>
</x-layouts.guest>
