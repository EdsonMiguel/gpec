import { itemCents, formatCents } from './financeiro-money';

document.querySelectorAll('[data-fin-form]').forEach((form) => {
    const container = form.querySelector('[data-fin-items]');
    const add = form.querySelector('[data-add-fin-item]');
    const updateTotal = () => {
        let total = 0n;
        for (const row of container.querySelectorAll('[data-transacao-item]')) {
            const value = itemCents(row.querySelector('[data-fin-field=quantidade]').value, row.querySelector('[data-fin-field=valor_unitario]').value);
            if (value === null) {
                form.querySelector('[data-fin-total]').textContent = 'Preencha as quantidades e os preços.';
                return;
            }
            total += value;
        }
        form.querySelector('[data-fin-total]').textContent = formatCents(total);
    };
    const refresh = () => {
        const rows = [...container.querySelectorAll('[data-transacao-item]')];
        rows.forEach((row, index) => {
            const type = row.querySelector('[data-fin-field=tipo_item]').value;
            row.querySelectorAll('[data-fin-field]').forEach((field) => {
                field.name = `itens[${index}][${field.dataset.finField}]`;
                field.id = `fin-${field.dataset.finField}-${index}`;
                field.previousElementSibling.htmlFor = field.id;
            });
            row.querySelectorAll('[data-fin-reference]').forEach((group) => {
                const active = group.dataset.finReference === type;
                group.hidden = !active;
                group.querySelector('select').disabled = !active;
                group.querySelector('select').required = active;
                if (!active) group.querySelector('select').value = '';
            });
            const free = row.querySelector('[data-fin-free]');
            free.hidden = type !== 'OUTRO';
            free.querySelector('select').disabled = type !== 'OUTRO';
            row.querySelector('[data-fin-field=descricao]').required = type === 'OUTRO';
            row.querySelector('[data-remove-fin-item]').disabled = rows.length === 1;
        });
        add.disabled = rows.length >= 30;
        form.querySelector('[data-fin-status]').textContent = `${rows.length} de 30 itens incluídos.`;
        updateTotal();
    };
    add.addEventListener('click', () => {
        if (container.children.length >= 30) return;
        container.append(form.querySelector('[data-fin-template]').content.cloneNode(true));
        refresh();
        container.lastElementChild.querySelector('select').focus();
    });
    container.addEventListener('change', (event) => {
        if (event.target.matches('[data-fin-field=tipo_item]')) refresh();
    });
    container.addEventListener('click', (event) => {
        if (event.target.closest('[data-remove-fin-item]') && container.children.length > 1) {
            event.target.closest('[data-transacao-item]').remove();
            refresh();
        }
    });
    const updatePayment = () => {
        const enabled = form.querySelector('[name=status]').value === 'EFETIVADA';
        const group = form.querySelector('[data-fin-payment]');
        group.hidden = !enabled;
        group.querySelectorAll('input,select').forEach((input) => { input.disabled = !enabled; input.required = enabled; });
    };
    const updateAccount = () => {
        const nature = form.querySelector('[name=tipo]').value === 'COMPRA' ? 'DESPESA' : 'RECEITA';
        const account = form.querySelector('[name=conta_orcamentaria_id]');
        [...account.options].forEach((option) => { if (option.value) option.disabled = option.dataset.natureza !== nature; });
        if (account.selectedOptions[0]?.disabled) account.value = '';
    };
    form.querySelector('[name=status]').addEventListener('change', updatePayment);
    form.querySelector('[name=tipo]').addEventListener('change', updateAccount);
    container.addEventListener('input', updateTotal);
    refresh();
    updatePayment();
    updateAccount();
});
