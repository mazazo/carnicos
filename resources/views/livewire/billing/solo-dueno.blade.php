<div class="mx-auto max-w-xl">
    <x-ui.page-header title="Plan de la carnicería" />

    @if ($vigente)
        <x-ui.alert tone="green">
            {{ $carniceria->nombre }} tiene el plan al día. Los planes y los pagos los maneja el dueño de la carnicería.
        </x-ui.alert>
    @else
        <x-ui.alert tone="red">
            El plan de {{ $carniceria->nombre }} venció o la cuenta está suspendida, por eso no podés usar el sistema.
            Avisale al dueño de la carnicería para que lo renueve.
        </x-ui.alert>
    @endif
</div>
