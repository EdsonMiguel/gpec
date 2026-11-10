const axisFormat = new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 1 });
const weightFormat = new Intl.NumberFormat('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function svgElement(tag, attributes) {
    const element = document.createElementNS('http://www.w3.org/2000/svg', tag);
    for (const [name, value] of Object.entries(attributes)) {
        element.setAttribute(name, value);
    }
    return element;
}

function initializeWeightChart(figure) {
    const readings = JSON.parse(figure.dataset.weightChart);
    const plot = figure.querySelector('[data-weight-plot]');
    const selection = figure.querySelector('[data-weight-selection]');
    const first = readings[0];
    const last = readings.at(-1);
    const weights = readings.map(({ peso }) => peso);
    const minimum = Math.min(...weights);
    const maximum = Math.max(...weights);
    const padding = Math.max(1, (maximum - minimum) * 0.1);
    const lower = Math.max(0, Math.floor(minimum - padding));
    const upper = Math.ceil(maximum + padding);
    const duration = Math.max(1, last.instante - first.instante);
    const sameDay = first.data.slice(0, 10) === last.data.slice(0, 10);
    const svg = svgElement('svg', { width: '100%', height: '100%', 'aria-hidden': 'true' });
    const marker = svgElement('circle', { r: 5, fill: '#047857', stroke: 'white', 'stroke-width': 2 });
    let active = readings.length - 1;
    let positions = [];
    plot.append(svg);

    function select(index) {
        active = index;
        const reading = readings[index];
        const { x, y } = positions[index];
        marker.setAttribute('cx', x);
        marker.setAttribute('cy', y);
        selection.textContent = `${reading.data} · ${weightFormat.format(reading.peso)} kg`;
    }

    function render() {
        const { clientWidth: width, clientHeight: height } = plot;
        if (width === 0 || height === 0) return;

        const left = 49;
        const right = width - 8;
        const top = 10;
        const bottom = height - 22;
        const yFor = (weight) => bottom - (weight - lower) / (upper - lower) * (bottom - top);
        positions = readings.map((reading) => ({
            x: left + (reading.instante - first.instante) / duration * (right - left),
            y: yFor(reading.peso),
        }));
        svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
        svg.replaceChildren();

        for (const value of [upper, (upper + lower) / 2, lower]) {
            const y = yFor(value);
            svg.append(svgElement('line', { x1: left, x2: right, y1: y, y2: y, stroke: '#e7e5e4' }));
            const label = svgElement('text', { x: left - 7, y: y + 3, 'text-anchor': 'end', 'font-size': 10, fill: '#57534e' });
            label.textContent = axisFormat.format(value);
            svg.append(label);
        }

        const points = positions.map(({ x, y }) => `${x},${y}`).join(' ');
        svg.append(
            svgElement('polygon', { points: `${left},${bottom} ${points} ${right},${bottom}`, fill: '#d1fae5', 'fill-opacity': 0.65 }),
            svgElement('polyline', { points, fill: 'none', stroke: '#047857', 'stroke-width': 2, 'stroke-linejoin': 'round' }),
        );
        for (const { x, y } of positions) {
            svg.append(svgElement('circle', { cx: x, cy: y, r: 2.5, fill: '#047857' }));
        }
        for (const [reading, x, anchor] of [[first, left, 'start'], [last, right, 'end']]) {
            const label = svgElement('text', { x, y: height - 5, 'text-anchor': anchor, 'font-size': 10, fill: '#57534e' });
            label.textContent = sameDay ? reading.data.slice(11) : reading.data.slice(0, 10);
            svg.append(label);
        }
        svg.append(marker);
        select(active);
    }

    function selectByPointer(event) {
        if (positions.length === 0) return;

        const x = event.clientX - plot.getBoundingClientRect().left;
        const index = positions.reduce((closest, point, candidate) =>
            Math.abs(point.x - x) < Math.abs(positions[closest].x - x) ? candidate : closest, 0);
        if (index !== active) select(index);
    }

    plot.addEventListener('pointermove', (event) => {
        if (event.pointerType === 'mouse') selectByPointer(event);
    });
    plot.addEventListener('click', selectByPointer);
    plot.addEventListener('keydown', (event) => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        const next = active + (event.key === 'ArrowLeft' ? -1 : 1);
        if (positions[next]) select(next);
    });
    new ResizeObserver(render).observe(plot);
    render();
}

document.querySelectorAll('[data-weight-chart]').forEach(initializeWeightChart);
