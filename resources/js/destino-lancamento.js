document.querySelectorAll('[data-destino-lancamento]').forEach((component) => {
    const mode = component.querySelector('[data-destino-select]');
    const lot = component.querySelector('[data-lote-select]');
    const animalFields = component.querySelector('[data-destino-animal]');
    const lotFields = component.querySelector('[data-destino-lote]');
    const summary = component.querySelector('[data-lote-resumo]');

    const refresh = () => {
        const batch = mode.value === 'LOTE';
        animalFields.hidden = batch;
        animalFields.disabled = batch;
        lotFields.hidden = !batch;
        lotFields.disabled = !batch;
        summary.hidden = !batch;
        const count = Number(lot.selectedOptions[0]?.dataset.quantidade ?? 0);
        summary.textContent = lot.value
            ? `Serão gerados ${count} registros individuais para os animais ativos do lote. Informe as quantidades por animal.`
            : 'Selecione um lote. As quantidades informadas serão repetidas para cada animal ativo.';
    };

    mode.addEventListener('change', refresh);
    lot.addEventListener('change', refresh);
    refresh();
});
