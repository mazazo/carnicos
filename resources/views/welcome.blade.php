<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Carnicos SaaS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media (max-width: 767.98px) {
            .subtotal-col {
                display: none !important;
            }

            .active-col {
                display: none !important;
            }

            .mobile-subtotal {
                display: block !important;
            }
        }

        @media (min-width: 768px) {
            .subtotal-col {
                display: table-cell !important;
            }

            .active-col {
                display: table-cell !important;
            }

            .mobile-subtotal {
                display: none !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="min-h-screen w-full p-3 sm:p-6 lg:p-8">
        <section class="mx-auto flex min-h-[calc(100vh-1.5rem)] w-full flex-col rounded-2xl bg-white p-4 shadow-sm sm:min-h-[calc(100vh-3rem)] sm:p-8 lg:p-10">
            <header class="flex flex-row items-start justify-between gap-6">
                <div class="max-w-2xl">
                    <p class="text-sm font-medium uppercase tracking-wide text-slate-500">Nashi products</p>
                    <h1 class="mt-1 text-2xl font-bold sm:text-3xl lg:text-4xl">Gestion y analisis de rendimientos</h1>
                    <p class="mt-3 text-slate-600">
                        Herramienta para facilitarte los calculos de tus cortes.
                    </p>
                </div>

                <div class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center sm:gap-2">
                    @auth
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('dashboard.enterprise') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-700 transition-colors">Dashboard Enterprise</a>
                            <a href="{{ route('dashboard.pro') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">Dashboard Pro</a>
                        @else
                            <a href="{{ route('animals.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">Animales</a>
                        @endif
                    @else
                        <a href="{{ route('register.create-user') }}" class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-slate-100 hover:bg-slate-700 transition-colors">Crear usuario</a>
                        <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">Ingresar</a>
                    @endauth
                </div>
            </header>

            <p class="mt-4 text-sm text-slate-500">
                Convertí tus datos en decisiones: centralizá tus cortes y preparados, medí rendimientos con precisión
                <a href="{{ route('register.create-user') }}" class="font-medium text-slate-800 underline underline-offset-2 hover:text-slate-600">Suscribite</a>
                e identificá oportunidades de mejora para proteger y hacer crecer tu rentabilidad.
            </p>

            <div id="category-buttons" class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <button
                    type="button"
                    data-category="vacuno"
                    class="category-btn rounded-xl border border-slate-900 bg-slate-900 p-5 text-left text-white transition"
                >
                    <p class="text-sm font-semibold">Promedio vacuno</p>
                    <p class="mt-1 text-sm text-slate-200">Pesos de referencia 90, 100, 110, 118, 120 y 130 kg.</p>
                </button>
                <button
                    type="button"
                    data-category="avicola"
                    class="category-btn rounded-xl border border-slate-200 bg-white p-5 text-left text-slate-900 transition hover:border-slate-300"
                >
                    <p class="text-sm font-semibold">Promedio avicola</p>
                    <p class="mt-1 text-sm text-slate-600">Promedios orientativos para pollo y derivados.</p>
                </button>
                <button
                    type="button"
                    data-category="porcino"
                    class="category-btn rounded-xl border border-slate-200 bg-white p-5 text-left text-slate-900 transition hover:border-slate-300"
                >
                    <p class="text-sm font-semibold">Promedio porcino</p>
                    <p class="mt-1 text-sm text-slate-600">Promedios orientativos para cortes de cerdo.</p>
                </button>
            </div>

            <section class="relative mt-8 overflow-hidden rounded-xl border border-slate-200">
                <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3 sm:overflow-x-auto sm:pb-1">
                        <div class="sm:min-w-[220px] sm:shrink-0">
                            <p id="list-title" class="text-sm font-semibold">Listado de cortes vacunos</p>
                            <p class="text-xs text-slate-600">Pesajes promedios por corte (kg)</p>
                        </div>

                        <div class="flex flex-col gap-2 sm:mx-auto sm:flex-row sm:shrink-0 sm:items-center sm:gap-3">
                            <label class="flex items-center justify-between gap-1.5 text-xs sm:justify-start">
                                <span id="weight-label" class="text-slate-500 whitespace-nowrap">Peso</span>
                                <select id="weight-select" class="rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs"></select>
                            </label>

                            <label class="flex items-center justify-between gap-1.5 text-xs sm:justify-start">
                                <span id="price-label" class="text-slate-500 whitespace-nowrap">Precio/kg</span>
                                <input
                                    id="animal-price-input"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    value="0"
                                    class="w-24 rounded-md border border-slate-300 bg-white px-2 py-1.5 text-xs"
                                >
                            </label>
                        </div>

                        <div class="flex flex-col gap-2 sm:ml-auto sm:flex-row sm:shrink-0 sm:items-center sm:gap-2">
                            <div class="flex items-center justify-between rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs sm:gap-1.5">
                                <span id="top-animal-total-label" class="text-slate-500">Total de media:</span>
                                <span id="top-animal-total" class="inline-block min-w-[8.5ch] text-right font-semibold tabular-nums text-slate-900">$ 0.00</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs sm:gap-1.5">
                                <span class="text-slate-500">Total cortes:</span>
                                <span id="top-cuts-total" class="inline-block min-w-[8.5ch] text-right font-semibold tabular-nums text-slate-900">$ 0.00</span>
                            </div>
                            <div class="flex items-center justify-between rounded-md border border-slate-200 bg-white px-2 py-1.5 text-xs sm:gap-1.5">
                                <span class="text-slate-500">Ganancia:</span>
                                <span id="top-profit-percent" class="inline-block min-w-[7ch] text-right font-semibold tabular-nums text-slate-900">0.00%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-auto">
                    <table class="w-full table-fixed text-sm">
                        <thead class="sticky top-0 bg-white">
                            <tr class="border-b border-slate-200 text-left">
                                <th class="w-[34%] px-4 py-3 font-semibold">Corte</th>
                                <th class="w-[18%] px-4 py-3 font-semibold text-right">Peso promedio</th>
                                <th class="w-[20%] px-4 py-3 font-semibold text-right">Precio por kg</th>
                                <th class="w-[20%] subtotal-col px-4 py-3 font-semibold text-right">Subtotal</th>
                                @auth
                                    @if(Auth::user()->isAdmin())
                                        <th class="active-col w-[10%] px-4 py-3 text-base font-semibold text-center">Activo</th>
                                    @endif
                                @endauth
                            </tr>
                        </thead>
                        <tbody id="cuts-table-body"></tbody>
                    </table>
                </div>

                <div class="border-t border-slate-200 bg-slate-50 p-4">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-5">
                        <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                            <span class="text-slate-500">Total kg:</span>
                            <span id="bottom-total-kg" class="ml-2 inline-block min-w-[6ch] text-right font-semibold tabular-nums text-slate-900">0.00 kg</span>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                            <span class="text-slate-500">Total kg cortes:</span>
                            <span id="bottom-total-cuts-kg" class="ml-2 inline-block min-w-[6ch] text-right font-semibold tabular-nums text-slate-900">0.00 kg</span>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                            <span id="bottom-animal-total-label" class="text-slate-500">Total de media:</span>
                            <span id="bottom-animal-total" class="ml-2 inline-block min-w-[8.5ch] text-right font-semibold tabular-nums text-slate-900">$ 0.00</span>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                            <span class="text-slate-500">Total cortes:</span>
                            <span id="bottom-cuts-total" class="ml-2 inline-block min-w-[8.5ch] text-right font-semibold tabular-nums text-slate-900">$ 0.00</span>
                        </div>
                        <div class="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm">
                            <span class="text-slate-500">Ganancia:</span>
                            <span id="bottom-profit-percent" class="ml-2 inline-block min-w-[7ch] text-right font-semibold tabular-nums text-slate-900">0.00%</span>
                        </div>
                    </div>
                </div>
            </section>

            <div class="mt-auto pt-8">
                @auth
                    @if(!Auth::user()->isAdmin())
                        <a href="{{ route('dashboard') }}" class="inline-flex rounded-lg bg-slate-900 px-4 py-2 text-white hover:bg-slate-700 transition-colors">Ir al dashboard</a>
                    @endif
                @endauth

                <p class="mt-6 text-center text-xs font-medium uppercase tracking-wide text-slate-400">
                    Tus datos, tus cortes, tus decisiones: más control, mejor rendimiento y mayor rentabilidad.
                </p>

                <div class="mt-6 w-full border-t border-slate-100 pt-6">
                    <div class="flex w-full items-center justify-between gap-2 pb-1 text-xs text-slate-500">
                        <p class="shrink-0 text-sm font-semibold text-slate-700 whitespace-nowrap">Nashi Dev</p>
                        <a href="https://wa.me/5491100000000" target="_blank" rel="noopener" class="flex shrink-0 items-center gap-1 whitespace-nowrap hover:text-slate-800">
                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.118 1.528 5.845L0 24l6.335-1.51A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.817 9.817 0 01-5.002-1.366l-.36-.214-3.732.889.936-3.617-.235-.371A9.818 9.818 0 012.182 12C2.182 6.575 6.575 2.182 12 2.182S21.818 6.575 21.818 12 17.425 21.818 12 21.818z"/></svg>
                            +54 9 11 0000-0000
                        </a>
                        <a href="mailto:contacto@nashidev.com" class="flex shrink-0 items-center gap-1 whitespace-nowrap hover:text-slate-800">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            contacto@nashidev.com
                        </a>
                    </div>
                    <p class="mt-3 text-center text-xs text-slate-400">&copy; {{ date('Y') }} Nashi Dev. Todos los derechos reservados.</p>
                </div>
            </div>
        </section>
    </main>

    @guest
        <div class="fixed bottom-4 left-1/2 z-50 w-[calc(100%-1rem)] max-w-2xl -translate-x-1/2 rounded-xl border border-slate-300 bg-white/95 p-4 shadow-lg backdrop-blur">
            <div class="flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold text-slate-800">Muestrario interactivo habilitado</p>
                    <p class="text-xs text-slate-600">Probá los cálculos en vivo y creá tu usuario para guardar tu trabajo.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('register.create-user') }}" class="rounded-lg bg-slate-900 px-4 py-2 text-xs font-semibold text-white hover:bg-slate-700">Crear usuario</a>
                    <a href="{{ route('login') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">Ingresar</a>
                </div>
            </div>
        </div>
    @endguest

    <script>
        const isAdminUser = @json(auth()->check() && auth()->user()->isAdmin());
        const cutVisibility = @json($cutVisibility ?? []);
        const updateCutVisibilityUrl = @json(route('admin.cuts.visibility'));
        const csrfToken = @json(csrf_token());

        const hardcodedCategoryBase = {
            vacuno: {
                key: 'vacuno',
                name: 'Vacuno',
                title: 'Listado de cortes vacunos',
                weights: [90, 100, 110, 118, 120, 130],
                defaultWeight: 118,
                baseWeight: 118,
                cuts: [
                    { name: 'Asado', avgKg: 12.5 },
                    { name: 'Bife ancho', avgKg: 7.1 },
                    { name: 'Bife angosto', avgKg: 6.2 },
                    { name: 'Bola lomo', avgKg: 4.1 },
                    { name: 'Colita cuadril', avgKg: 2.1 },
                    { name: 'Cuadrada', avgKg: 4.9 },
                    { name: 'Cuadril', avgKg: 5.8 },
                    { name: 'Entrana', avgKg: 1.1 },
                    { name: 'Espinazo', avgKg: 3.6 },
                    { name: 'Falda', avgKg: 3.7 },
                    { name: 'Grasas', avgKg: 2.7 },
                    { name: 'Lomo', avgKg: 2.4 },
                    { name: 'Matambre', avgKg: 2.3 },
                    { name: 'Nalga', avgKg: 6.8 },
                    { name: 'Osobuco', avgKg: 3.2 },
                    { name: 'Paleta', avgKg: 6.4 },
                    { name: 'Palomita', avgKg: 2.5 },
                    { name: 'Peceto', avgKg: 2.0 },
                    { name: 'Picada', avgKg: 4.0 },
                    { name: 'Roast beef', avgKg: 4.7 },
                    { name: 'Tapa asado', avgKg: 2.8 },
                    { name: 'Tapa nalga', avgKg: 3.3 },
                    { name: 'Tortugita', avgKg: 2.2 },
                    { name: 'Vacio', avgKg: 4.9 }
                ]
            },
            avicola: {
                key: 'avicola',
                name: 'Avicola',
                title: 'Listado de cortes avicolas',
                weights: [20],
                defaultWeight: 20,
                baseWeight: 20,
                cuts: [
                    { name: 'Pata y muslo', avgKg: 7.54 },
                    { name: 'Pechuga', avgKg: 7.16 },
                    { name: 'Ala', avgKg: 2.39 },
                    { name: 'Menudo', avgKg: 1.27 },
                    { name: 'Suprema', avgKg: 5.80 }
                ]
            },
            porcino: {
                key: 'porcino',
                name: 'Porcino',
                title: 'Listado de cortes porcinos',
                weights: [70, 80, 90, 100, 110],
                defaultWeight: null,
                baseWeight: null,
                ingresoSamples: [
                    { unidadesCapones: 13, totalKgIngreso: 937.0 },
                    { unidadesCapones: 13, totalKgIngreso: 1260.0 },
                    { unidadesCapones: 13, totalKgIngreso: 907.0 },
                    { unidadesCapones: 13, totalKgIngreso: 1153.0 },
                    { unidadesCapones: 13, totalKgIngreso: 1038.0 },
                    { unidadesCapones: 30, totalKgIngreso: 1997.0 },
                    { unidadesCapones: 20, totalKgIngreso: 2064.0 },
                    { unidadesCapones: 20, totalKgIngreso: 1693.0 },
                    { unidadesCapones: 20, totalKgIngreso: 1700.0 },
                    { unidadesCapones: 21, totalKgIngreso: 1682.0 },
                    { unidadesCapones: 20, totalKgIngreso: 1996.0 },
                    { unidadesCapones: 20, totalKgIngreso: 1634.0 },
                    { unidadesCapones: 20, totalKgIngreso: 1587.0 },
                    { unidadesCapones: 20, totalKgIngreso: 1951.0 },
                    { unidadesCapones: 20, totalKgIngreso: 1582.0 },
                    { unidadesCapones: 40, totalKgIngreso: 3721.0 },
                    { unidadesCapones: 35, totalKgIngreso: 2649.0 },
                    { unidadesCapones: 35, totalKgIngreso: 3218.0 }
                ],
                cuts: [
                    { name: 'Bondiola', avgKg: 2.32 },
                    { name: 'Carre', avgKg: 5.65 },
                    { name: 'Jamon', avgKg: 11.38 },
                    { name: 'Paleta', avgKg: 5.18 },
                    { name: 'Papada/recorte', avgKg: 4.26 },
                    { name: 'Pechito', avgKg: 6.81 }
                ]
            }
        };

        function buildHardcodedCategoryData() {
            const data = {};

            const resolveActive = (categoryKey, cutName) => {
                const byCategory = cutVisibility?.[categoryKey] ?? {};
                if (Object.prototype.hasOwnProperty.call(byCategory, cutName)) {
                    return Boolean(byCategory[cutName]);
                }

                return true;
            };

            const calculateAverageIngresoWeight = (samples = []) => {
                const totals = samples.reduce((acc, sample) => {
                    acc.unidades += Number(sample.unidadesCapones) || 0;
                    acc.kg += Number(sample.totalKgIngreso) || 0;
                    return acc;
                }, { unidades: 0, kg: 0 });

                if (totals.unidades <= 0) {
                    return null;
                }

                return totals.kg / totals.unidades;
            };

            const resolveDefaultWeight = (weights, defaultWeight, baseWeight) => {
                if (defaultWeight !== null && defaultWeight !== undefined) {
                    return defaultWeight;
                }

                if (!Array.isArray(weights) || weights.length === 0) {
                    return Number(baseWeight.toFixed(2));
                }

                return weights.reduce((closest, candidate) => {
                    const candidateDistance = Math.abs(candidate - baseWeight);
                    const closestDistance = Math.abs(closest - baseWeight);
                    return candidateDistance < closestDistance ? candidate : closest;
                }, weights[0]);
            };

            Object.values(hardcodedCategoryBase).forEach((category) => {
                const cutsByWeight = {};
                const ingresoBaseWeight = calculateAverageIngresoWeight(category.ingresoSamples);
                const resolvedBaseWeight = Number((ingresoBaseWeight ?? category.baseWeight ?? 1).toFixed(2));
                const resolvedDefaultWeight = resolveDefaultWeight(category.weights, category.defaultWeight, resolvedBaseWeight);

                category.weights.forEach((weight) => {
                    const ratio = weight / resolvedBaseWeight;
                    cutsByWeight[weight] = category.cuts.map((cut) => ({
                        name: cut.name,
                        avgKg: Number((cut.avgKg * ratio).toFixed(3)),
                        active: resolveActive(category.key, cut.name),
                    }));
                });

                data[category.key] = {
                    key: category.key,
                    name: category.name,
                    title: category.title,
                    weights: category.weights,
                    defaultWeight: resolvedDefaultWeight,
                    cutsByWeight,
                };
            });

            return data;
        }

        const categoryData = buildHardcodedCategoryData();

        const buttons = document.querySelectorAll('.category-btn');
        const titleEl = document.getElementById('list-title');
        const weightSelectEl = document.getElementById('weight-select');
        const animalPriceInputEl = document.getElementById('animal-price-input');
        const topAnimalTotalEl = document.getElementById('top-animal-total');
        const bottomAnimalTotalEl = document.getElementById('bottom-animal-total');
        const topCutsTotalEl = document.getElementById('top-cuts-total');
        const bottomCutsTotalEl = document.getElementById('bottom-cuts-total');
        const topProfitPercentEl = document.getElementById('top-profit-percent');
        const bottomProfitPercentEl = document.getElementById('bottom-profit-percent');
        const tableBodyEl = document.getElementById('cuts-table-body');
        const bottomTotalKgEl = document.getElementById('bottom-total-kg');
        const bottomTotalCutsKgEl = document.getElementById('bottom-total-cuts-kg');
        const weightLabelEl = document.getElementById('weight-label');
        const priceLabelEl = document.getElementById('price-label');
        let activeCategory = 'vacuno';
        let currentCutsTotal = 0;
        let currentCutsKg = 0;
        const cutPrices = {};


        function formatKg(value) {
            return `${value.toFixed(2)} kg`;
        }

        function formatMoney(value) {
            return `$ ${value.toFixed(2)}`;
        }

        function formatPercent(value) {
            return `${value.toFixed(2)}%`;
        }

        function bindZeroClearBehavior(inputEl) {
            if (!inputEl) {
                return;
            }

            inputEl.addEventListener('focus', () => {
                const normalizedValue = String(inputEl.value ?? '').trim();

                if (normalizedValue !== '' && Number(normalizedValue) === 0) {
                    inputEl.value = '';
                }
            });

            inputEl.addEventListener('blur', () => {
                const normalizedValue = String(inputEl.value ?? '').trim();

                if (normalizedValue === '') {
                    inputEl.value = '0';
                    inputEl.dispatchEvent(new Event('input', { bubbles: true }));
                }
            });
        }

        const topAnimalTotalLabelEl = document.getElementById('top-animal-total-label');
        const bottomAnimalTotalLabelEl = document.getElementById('bottom-animal-total-label');

        function getAnimalTotalFromInputs() {
            const pricePerKg = Number(animalPriceInputEl.value) || 0;
            if (activeCategory === 'avicola') {
                return pricePerKg;
            }
            const selectedWeight = Number(weightSelectEl.value);
            return selectedWeight * pricePerKg;
        }

        function updateSummaries() {
            const animalTotal = getAnimalTotalFromInputs();
            const selectedWeight = Number(weightSelectEl.value);
            const profitPercent = animalTotal > 0 ? ((currentCutsTotal - animalTotal) / animalTotal) * 100 : 0;

            topAnimalTotalEl.textContent = formatMoney(animalTotal);
            bottomAnimalTotalEl.textContent = formatMoney(animalTotal);
            topCutsTotalEl.textContent = formatMoney(currentCutsTotal);
            bottomCutsTotalEl.textContent = formatMoney(currentCutsTotal);
            topProfitPercentEl.textContent = formatPercent(profitPercent);
            bottomProfitPercentEl.textContent = formatPercent(profitPercent);
            bottomTotalKgEl.textContent = formatKg(selectedWeight);
            bottomTotalCutsKgEl.textContent = formatKg(currentCutsKg);
        }

        function updateCutsTotalFromDom() {
            let total = 0;
            tableBodyEl.querySelectorAll('[data-subtotal-value]').forEach((cell) => {
                const row = cell.closest('tr');
                if (row && row.dataset.cutActive === '0') return;
                total += Number(cell.dataset.subtotalValue || 0);
            });
            currentCutsTotal = total;
            updateSummaries();
        }

        function renderWeights(categoryKey) {
            const category = categoryData[categoryKey];
            weightSelectEl.innerHTML = '';

            (category?.weights ?? []).forEach((weight) => {
                const option = document.createElement('option');
                option.value = String(weight);
                option.textContent = `${weight} kg`;
                if (weight === category.defaultWeight) {
                    option.selected = true;
                }
                weightSelectEl.appendChild(option);
            });
        }

        function renderTable(categoryKey, weight) {
            const category = categoryData[categoryKey];
            const cuts = category?.cutsByWeight?.[weight] ?? [];
            const visibleCuts = isAdminUser ? cuts : cuts.filter((cut) => cut.active !== false);
            tableBodyEl.innerHTML = '';
            let totalCuts = 0;

            if (!cutPrices[categoryKey]) {
                cutPrices[categoryKey] = {};
            }

            const buildCutKey = (cutName) => `${weight}::${cutName}`;

            const setCutActiveState = (targetCategoryKey, cutName, activeState) => {
                Object.values(categoryData[targetCategoryKey].cutsByWeight).forEach((cutsList) => {
                    cutsList.forEach((cutItem) => {
                        if (cutItem.name === cutName) {
                            cutItem.active = activeState;
                        }
                    });
                });

                if (!cutVisibility[targetCategoryKey]) {
                    cutVisibility[targetCategoryKey] = {};
                }

                cutVisibility[targetCategoryKey][cutName] = activeState;
            };

            const persistCutActiveState = async (targetCategoryKey, cutName, activeState) => {
                const response = await fetch(updateCutVisibilityUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        category: targetCategoryKey,
                        cut: cutName,
                        active: activeState,
                    }),
                });

                if (!response.ok) {
                    throw new Error('No se pudo guardar el estado del corte.');
                }

                return response.json();
            };

            const applyCutPrice = (cutKey, avgKg, value, sourceInput = null) => {
                const safeValue = Number(value) || 0;
                cutPrices[categoryKey][cutKey] = safeValue;
                const rowSubtotal = avgKg * safeValue;

                document.querySelectorAll(`[data-cut-key="${cutKey}"]`).forEach((el) => {
                    if (el.tagName === 'INPUT') {
                        if (el !== sourceInput) {
                            el.value = String(safeValue);
                        }
                    } else {
                        el.textContent = formatMoney(rowSubtotal);
                        if (el.dataset.subtotalValue !== undefined) {
                            el.dataset.subtotalValue = String(rowSubtotal);
                        }
                    }
                });

                updateCutsTotalFromDom();
            };

            let totalCutsKg = 0;
            visibleCuts.forEach((cut) => {
                const isActive = cut.active !== false;
                const row = document.createElement('tr');
                row.className = 'border-b border-slate-100' + (isActive ? '' : ' opacity-40');
                row.dataset.cutActive = isActive ? '1' : '0';
                const cutKey = buildCutKey(cut.name);
                const currentPrice = Number(cutPrices[categoryKey][cutKey] ?? 0);
                const subtotal = cut.avgKg * currentPrice;
                if (isActive) {
                    totalCuts += subtotal;
                    totalCutsKg += cut.avgKg;
                }

                const cutCell = document.createElement('td');
                cutCell.className = 'px-4 py-3';
                cutCell.textContent = cut.name;

                const weightCell = document.createElement('td');
                weightCell.className = 'px-4 py-3 text-right tabular-nums text-slate-700';
                weightCell.textContent = formatKg(Number(cut.avgKg));

                const priceCell = document.createElement('td');
                priceCell.className = 'px-4 py-3 text-right text-slate-700';
                const priceInput = document.createElement('input');
                priceInput.type = 'number';
                priceInput.min = '0';
                priceInput.step = '0.01';
                priceInput.value = String(currentPrice);
                priceInput.className = 'w-28 rounded-md border border-slate-300 bg-white px-2 py-1 text-right tabular-nums text-sm';
                priceInput.dataset.cutKey = cutKey;
                
                const subtotalCell = document.createElement('td');
                subtotalCell.className = 'subtotal-col px-4 py-3 text-right font-medium tabular-nums text-slate-900';
                subtotalCell.textContent = formatMoney(subtotal);
                subtotalCell.dataset.subtotalValue = String(subtotal);
                subtotalCell.dataset.cutKey = cutKey;

                priceInput.addEventListener('input', (event) => {
                    applyCutPrice(cutKey, cut.avgKg, event.target.value, event.target);
                });
                bindZeroClearBehavior(priceInput);
                priceCell.appendChild(priceInput);

                const mobileSubtotal = document.createElement('div');
                mobileSubtotal.className = 'mobile-subtotal mt-1 text-right text-xs font-medium tabular-nums text-slate-500';
                mobileSubtotal.textContent = formatMoney(subtotal);
                mobileSubtotal.dataset.cutKey = cutKey;
                priceCell.appendChild(mobileSubtotal);

                row.appendChild(cutCell);
                row.appendChild(weightCell);
                row.appendChild(priceCell);
                row.appendChild(subtotalCell);

                if (isAdminUser) {
                    const statusCell = document.createElement('td');
                    statusCell.className = 'active-col px-4 py-3 text-center';

                    const statusToggle = document.createElement('input');
                    statusToggle.type = 'checkbox';
                    statusToggle.className = 'h-5 w-5 cursor-pointer';
                    statusToggle.checked = cut.active !== false;

                    statusToggle.addEventListener('change', async (event) => {
                        const nextState = event.target.checked;

                        setCutActiveState(categoryKey, cut.name, nextState);
                        recalculateAll();

                        try {
                            await persistCutActiveState(categoryKey, cut.name, nextState);
                        } catch (error) {
                            setCutActiveState(categoryKey, cut.name, !nextState);
                            recalculateAll();
                            window.alert('No se pudo guardar el cambio. Intentá nuevamente.');
                        }
                    });

                    statusCell.appendChild(statusToggle);
                    row.appendChild(statusCell);
                }

                tableBodyEl.appendChild(row);
            });

            if (visibleCuts.length === 0) {
                const row = document.createElement('tr');
                const cell = document.createElement('td');
                cell.colSpan = isAdminUser ? 5 : 4;
                cell.className = 'px-4 py-6 text-center text-sm text-slate-500';
                cell.textContent = 'No hay promedios cargados para el peso seleccionado.';
                row.appendChild(cell);
                tableBodyEl.appendChild(row);
            }

            currentCutsTotal = totalCuts;
            currentCutsKg = totalCutsKg;
            updateSummaries();
        }

        function recalculateAll() {
            if (!activeCategory) {
                return;
            }

            const selectedWeight = Number(weightSelectEl.value);
            renderTable(activeCategory, selectedWeight);
        }

        function updateActiveButton(categoryKey) {
            buttons.forEach((button) => {
                const isActive = button.dataset.category === categoryKey;

                if (isActive) {
                    button.classList.remove('border-slate-200', 'bg-white', 'text-slate-900');
                    button.classList.add('border-slate-900', 'bg-slate-900', 'text-white');
                    button.querySelector('p:nth-child(2)')?.classList.remove('text-slate-600');
                    button.querySelector('p:nth-child(2)')?.classList.add('text-slate-200');
                } else {
                    button.classList.remove('border-slate-900', 'bg-slate-900', 'text-white');
                    button.classList.add('border-slate-200', 'bg-white', 'text-slate-900');
                    button.querySelector('p:nth-child(2)')?.classList.remove('text-slate-200');
                    button.querySelector('p:nth-child(2)')?.classList.add('text-slate-600');
                }
            });
        }

        const categoryLabels = {
            avicola: { weight: 'Peso cajón', price: 'Precio cajón', animalTotal: 'Total cajones' },
        };

        function renderCategory(categoryKey) {
            if (!categoryData[categoryKey]) return;
            activeCategory = categoryKey;
            const category = categoryData[categoryKey];
            titleEl.textContent = category.title;
            const labels = categoryLabels[categoryKey] ?? {};
            weightLabelEl.textContent = labels.weight ?? 'Peso';
            priceLabelEl.textContent = labels.price ?? 'Precio/kg';
            const animalTotalLabel = labels.animalTotal ?? 'Total de media';
            if (topAnimalTotalLabelEl) topAnimalTotalLabelEl.textContent = animalTotalLabel + ':';
            if (bottomAnimalTotalLabelEl) bottomAnimalTotalLabelEl.textContent = animalTotalLabel + ':';
            updateActiveButton(categoryKey);
            renderWeights(categoryKey);
            recalculateAll();
        }

        buttons.forEach((button) => {
            button.addEventListener('click', () => {
                renderCategory(button.dataset.category);
            });
        });

        weightSelectEl.addEventListener('change', () => {
            recalculateAll();
        });

        animalPriceInputEl.addEventListener('input', () => {
            updateSummaries();
        });

        bindZeroClearBehavior(animalPriceInputEl);

        const firstKey = Object.keys(categoryData)[0] ?? activeCategory;
        renderCategory(firstKey);
    </script>
</body>
</html>
