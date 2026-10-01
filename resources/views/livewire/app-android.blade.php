@php
    $pasos = [
        ['icono' => 'download', 'titulo' => 'Tocá "Descargar la app"', 'texto' => 'Hacelo desde el celular Android donde la vas a usar. Se baja un archivo llamado carnico.apk.'],
        ['icono' => 'inbox', 'titulo' => 'Buscá el archivo descargado', 'texto' => 'Abrilo desde la notificación de descarga, o entrá a la app "Archivos" (o "Mis archivos") → carpeta "Descargas" y tocá carnico.apk.'],
        ['icono' => 'warning', 'titulo' => 'Permití instalar desde esta fuente', 'texto' => 'Android te puede avisar que la app viene de una fuente desconocida o externa, porque no se descarga desde Play Store. Tocá "Configuración" y activá "Permitir de esta fuente" para el navegador o la app de archivos que estés usando, y volvé atrás.'],
        ['icono' => 'check', 'titulo' => 'Instalá igualmente', 'texto' => 'Si aparece un aviso de Play Protect diciendo que la app es desconocida, tocá "Más detalles" (o "Más") y después "Instalar igualmente". Es nuestra app de Carnico, es segura.'],
        ['icono' => 'home', 'titulo' => '¡Listo, a disfrutarla!', 'texto' => 'Abrí Carnico, ingresá con el mismo email y contraseña que usás en la web, y ya podés cargar ingresos y despostes desde el celular.'],
    ];
@endphp
<div class="mx-auto max-w-3xl">
    <x-ui.page-header title="App Android" subtitle="Cargá ingresos, despostes y cuarteos desde el celular. Incluida en tu Plan Completo." />

    @if (session('success'))
        <x-ui.alert class="mb-4">{{ session('success') }}</x-ui.alert>
    @endif

    {{-- Descarga --}}
    <div class="mb-6 flex flex-col items-center gap-4 rounded-2xl bg-stone-900 p-6 text-center text-white sm:flex-row sm:text-left">
        <span class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-amber-500 text-3xl shadow-lg shadow-amber-500/20">🔪</span>
        <div class="flex-1">
            <p class="text-lg font-bold">Carnico para Android</p>
            @if ($hayApk)
                <p class="text-sm text-stone-400">Versión del {{ $actualizada->format('d/m/Y') }} · {{ str_replace('.', ',', $tamanoMb) }} MB</p>
            @else
                <p class="text-sm text-amber-400">La descarga todavía no está disponible. Volvé a mirar en un rato.</p>
            @endif
        </div>
        @if ($hayApk)
            <a href="{{ route('app.android.descargar') }}" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-6 py-3 text-base font-bold text-stone-950 shadow-sm transition hover:bg-amber-400">
                <x-ui.icon name="download" class="h-5 w-5" /> Descargar la app
            </a>
        @endif
    </div>

    {{-- Pasos --}}
    <x-ui.card title="Cómo instalarla" subtitle="Son 5 pasos y se hace una sola vez." :padding="false">
        <ol class="divide-y divide-stone-100">
            @foreach ($pasos as $n => $paso)
                <li class="flex gap-4 px-5 py-4">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-sm font-black text-amber-800">{{ $n + 1 }}</span>
                    <div>
                        <p class="flex items-center gap-2 font-semibold text-stone-900">
                            <x-ui.icon :name="$paso['icono']" class="h-4 w-4 text-stone-400" /> {{ $paso['titulo'] }}
                        </p>
                        <p class="mt-0.5 text-sm text-stone-600">{{ $paso['texto'] }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </x-ui.card>

    <x-ui.alert tone="amber" class="mt-4">
        Cuando haya una versión nueva, la bajás desde acá y la instalás encima: no se pierde nada, tus datos están guardados en el sistema.
    </x-ui.alert>

    @if ($esAdmin)
        <x-ui.card title="Publicar una versión nueva" subtitle="Solo lo ve el administrador." class="mt-6">
            <ol class="mb-4 list-decimal space-y-1 pl-5 text-sm text-stone-600">
                <li>Compilá la app: <code class="rounded bg-stone-100 px-1 text-xs">flutter build apk --release --obfuscate --split-debug-info=build/simbolos</code></li>
                <li>Elegí el archivo <code class="rounded bg-stone-100 px-1 text-xs">build\app\outputs\flutter-apk\app-release.apk</code> y tocá Publicar.</li>
            </ol>

            <form method="POST" action="{{ route('app.android.subir') }}" enctype="multipart/form-data"
                  x-data="{ subiendo: false, nombre: '' }" @submit="subiendo = true"
                  class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @csrf
                <label class="flex flex-1 cursor-pointer items-center gap-3 rounded-xl border border-dashed border-stone-300 px-4 py-3 hover:border-amber-400">
                    <x-ui.icon name="inbox" class="h-5 w-5 text-stone-400" />
                    <span class="truncate text-sm text-stone-600" x-text="nombre || 'Elegir archivo .apk…'"></span>
                    <input type="file" name="apk" accept=".apk" required class="sr-only" @change="nombre = $event.target.files[0]?.name || ''">
                </label>
                <button type="submit" :disabled="subiendo || ! nombre" class="inline-flex items-center justify-center gap-2 rounded-lg bg-stone-900 px-4 py-2.5 text-sm font-semibold text-white hover:bg-stone-800 disabled:opacity-50">
                    <x-ui.icon name="download" class="h-4 w-4 rotate-180" />
                    <span x-text="subiendo ? 'Subiendo… (puede tardar)' : 'Publicar'">Publicar</span>
                </button>
            </form>
            @error('apk')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror

            <p class="mt-3 text-xs text-stone-500">
                La versión que está publicada ahora queda guardada como respaldo. El servidor acepta archivos de hasta
                <strong>{{ ini_get('upload_max_filesize') }}</strong> (subida) y <strong>{{ ini_get('post_max_size') }}</strong> (formulario);
                si el APK pesa más, hay que subir esos valores en el PHP del servidor o copiarlo a mano en
                <span class="break-all font-mono">{{ $ruta }}</span>.
            </p>
        </x-ui.card>
    @endif
</div>
