@props([
    'action',
    'message' => "Confermi l'eliminazione? L'operazione non è reversibile.",
    'label' => 'Elimina',
])

<form method="POST" action="{{ $action }}" class="d-inline" data-confirm="{{ $message }}">
    @csrf
    @method('DELETE')
    <button type="submit" {{ $attributes->merge(['class' => 'btn btn-sm btn-outline-danger']) }}>
        <i class="bi bi-trash"></i> {{ $label }}
    </button>
</form>
