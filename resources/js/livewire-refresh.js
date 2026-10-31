// $refresh lives on a component's $wire proxy, not on the component object
// that Livewire.all() returns (QUESTIFY-APP-F).
export function refreshAllComponents(livewire) {
    (livewire?.all() ?? []).forEach((component) => component.$wire?.$refresh());
}
