document.querySelectorAll('[data-refeicao-form]').forEach((form) => {
    const container = form.querySelector('[data-refeicao-items]');
    const add = form.querySelector('[data-add-refeicao-item]');
    const status = form.querySelector('[data-refeicao-status]');
    const refresh = () => {
        const rows = [...container.querySelectorAll('[data-refeicao-item]')];
        rows.forEach((row, index) => {
            const food = row.querySelector('[data-item-alimento]');
            const quantity = row.querySelector('[data-item-quantidade]');
            food.name = `itens[${index}][alimento_id]`;
            food.id = `alimento-${index}`;
            quantity.name = `itens[${index}][quantidade]`;
            quantity.id = `quantidade-${index}`;
            row.querySelectorAll('label')[0].htmlFor = food.id;
            row.querySelectorAll('label')[1].htmlFor = quantity.id;
            row.querySelector('[data-remove-refeicao-item]').disabled = rows.length === 1;
        });
        add.disabled = rows.length >= 20;
        status.textContent = `${rows.length} de 20 alimentos incluídos.`;
    };
    add.addEventListener('click', () => {
        if (container.children.length >= 20) return;
        const fragment = form.querySelector('[data-refeicao-template]').content.cloneNode(true);
        container.append(fragment);
        refresh();
        container.lastElementChild.querySelector('select').focus();
    });
    container.addEventListener('click', (event) => {
        if (event.target.closest('[data-remove-refeicao-item]') && container.children.length > 1) {
            event.target.closest('[data-refeicao-item]').remove();
            refresh();
        }
    });
    refresh();
});
