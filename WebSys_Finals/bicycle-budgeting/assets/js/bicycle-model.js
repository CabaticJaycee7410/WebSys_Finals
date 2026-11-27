
document.addEventListener('DOMContentLoaded', () => {
    const svg = document.getElementById('interactiveBikeSvg');
    if (!svg) return;

    const profiles = {
        'Mountain Bike': { rear: 155, front: 545, radius: 91, tire: 17, seat: [240, 145], head: [390, 145], bb: [285, 255], bar: 'M390 145 L420 102 L470 102 L480 110', stem: 'M390 145 L420 102', frame: 'M155 255 L285 255 L240 145 L155 255 M240 145 L390 145 L545 255 L285 255 M285 255 L390 145', fork: 'M390 145 L545 255', suspension: true, flat: true, treads: true, rearCog: true, disc: true },
        'Road Bike': { rear: 155, front: 545, radius: 84, tire: 6, seat: [232, 112], head: [400, 115], bb: [285, 255], bar: 'M400 115 L425 87 Q452 73 470 91 L455 113 L440 108', stem: 'M400 115 L425 87', frame: 'M155 255 L285 255 L232 112 L155 255 M232 112 L400 115 L545 255 L285 255 M285 255 L400 115', fork: 'M400 115 L545 255', drop: true, disc: false },
        'BMX': { rear: 155, front: 545, radius: 72, tire: 13, seat: [270, 172], head: [405, 178], bb: [300, 255], bar: 'M405 178 L410 105 L455 105 L465 120 L490 120', stem: 'M405 178 L410 105', frame: 'M155 255 L300 255 L270 172 L155 255 M270 172 L405 178 L545 255 L300 255 M300 255 L405 178', fork: 'M405 178 L545 255', flat: true, pegs: true, singleSpeed: true, disc: false },
        'Gravel Bike': { rear: 155, front: 545, radius: 88, tire: 12, seat: [235, 125], head: [395, 128], bb: [285, 255], bar: 'M395 128 L423 91 Q451 78 468 96 L454 122 L438 113', stem: 'M395 128 L423 91', frame: 'M155 255 L285 255 L235 125 L155 255 M235 125 L395 128 L545 255 L285 255 M285 255 L395 128', fork: 'M395 128 L545 255', drop: true, disc: true, treads: true },
        'Hybrid Bike': { rear: 155, front: 545, radius: 88, tire: 10, seat: [238, 130], head: [392, 140], bb: [285, 255], bar: 'M392 140 L420 102 L465 102 L478 112', stem: 'M392 140 L420 102', frame: 'M155 255 L285 255 L238 130 L155 255 M238 130 L392 140 L545 255 L285 255 M285 255 L392 140', fork: 'M392 140 L545 255', flat: true, disc: true },
        'Touring Bike': { rear: 155, front: 545, radius: 88, tire: 11, seat: [238, 130], head: [392, 135], bb: [285, 255], bar: 'M392 135 L420 95 Q448 80 466 98 L452 126 L437 116', stem: 'M392 135 L420 95', frame: 'M155 255 L285 255 L238 130 L155 255 M238 130 L392 135 L545 255 L285 255 M285 255 L392 135', fork: 'M392 135 L545 255', drop: true, rack: true, panniers: true, disc: true },
        'Folding Bike': { rear: 155, front: 545, radius: 64, tire: 8, seat: [242, 150], head: [405, 160], bb: [292, 255], bar: 'M405 160 L423 95 L465 95 L476 105', stem: 'M405 160 L423 95', frame: 'M155 255 L292 255 L337 208 L242 150 L155 255 M337 208 L405 160 L545 255 L292 255 M292 255 L405 160', fork: 'M405 160 L545 255', flat: true, hinge: true, disc: false },
        'Fat Bike': { rear: 155, front: 545, radius: 91, tire: 27, seat: [240, 145], head: [390, 145], bb: [285, 255], bar: 'M390 145 L420 102 L470 102 L480 110', stem: 'M390 145 L420 102', frame: 'M155 255 L285 255 L240 145 L155 255 M240 145 L390 145 L545 255 L285 255 M285 255 L390 145', fork: 'M390 145 L545 255', flat: true, treads: true, disc: true },
        'Track / Fixed Gear': { rear: 155, front: 545, radius: 84, tire: 6, seat: [232, 112], head: [400, 115], bb: [285, 255], bar: 'M400 115 L425 87 Q452 73 470 91 L455 113 L440 108', stem: 'M400 115 L425 87', frame: 'M155 255 L285 255 L232 112 L155 255 M232 112 L400 115 L545 255 L285 255 M285 255 L400 115', fork: 'M400 115 L545 255', drop: true, fixed: true, singleSpeed: true, disc: false },
        'Electric Bike': { rear: 155, front: 545, radius: 88, tire: 14, seat: [240, 140], head: [392, 145], bb: [285, 255], bar: 'M392 145 L420 103 L466 103 L478 112', stem: 'M392 145 L420 103', frame: 'M155 255 L285 255 L240 140 L155 255 M240 140 L392 145 L545 255 L285 255 M285 255 L392 145', fork: 'M392 145 L545 255', flat: true, battery: true, motor: true, display: true, disc: true },
        'Cargo Bike': { rear: 155, front: 545, radius: 84, tire: 14, seat: [240, 140], head: [395, 145], bb: [285, 255], bar: 'M395 145 L425 103 L470 103 L482 112', stem: 'M395 145 L425 103', frame: 'M155 255 L285 255 L240 140 L155 255 M240 140 L395 145 L545 255 L285 255 M285 255 L395 145 M395 145 L500 145', fork: 'M395 145 L545 255', flat: true, cargo: true, disc: true },
        'Custom Build': { rear: 155, front: 545, radius: 88, tire: 11, seat: [235, 128], head: [395, 128], bb: [285, 255], bar: 'M395 128 L425 88 L470 88 L480 98', stem: 'M395 128 L425 88', frame: 'M155 255 L285 255 L235 128 L155 255 M235 128 L395 128 L545 255 L285 255 M285 255 L395 128', fork: 'M395 128 L545 255', flat: true, disc: true }
    };

    const line = (d, cls = 'bike-metal') => `<path d="${d}" class="${cls}"/>`;
    const circle = (cx, cy, r, cls) => `<circle cx="${cx}" cy="${cy}" r="${r}" class="${cls}"/>`;

    function render(type) {
        const p = profiles[type] || profiles['Custom Build'];
        const rear = p.rear;
        const front = p.front;
        const radius = p.radius;
        const inner = Math.max(38, radius - p.tire / 2 - 7);
        const bb = p.bb;
        const seat = p.seat;
        const head = p.head;
        const markerNodes = [...svg.querySelectorAll('.bike-part-marker')];

        const spokeSet = (x, y, r) => {
            let paths = '';
            for (let i = 0; i < 12; i++) {
                const a = i * Math.PI / 6;
                const ex = x + Math.cos(a) * r;
                const ey = y + Math.sin(a) * r;
                paths += `<path d="M${x} ${y} L${ex.toFixed(1)} ${ey.toFixed(1)}"/>`;
            }
            return paths;
        };

        const treadSet = (x, y, r) => {
            let paths = '';
            if (!p.treads && p.tire < 13) return paths;
            for (let i = 0; i < 36; i++) {
                const a = i * Math.PI / 18;
                const x1 = x + Math.cos(a) * (r - p.tire * .45);
                const y1 = y + Math.sin(a) * (r - p.tire * .45);
                const x2 = x + Math.cos(a) * (r + p.tire * .45);
                const y2 = y + Math.sin(a) * (r + p.tire * .45);
                paths += `<path d="M${x1.toFixed(1)} ${y1.toFixed(1)} L${x2.toFixed(1)} ${y2.toFixed(1)}"/>`;
            }
            return paths;
        };

        const wheel = (x, side) => `<g data-component="${side}_tire"><circle cx="${x}" cy="255" r="${radius}" class="model-tire" style="stroke-width:${p.tire}px"/><g class="model-treads">${treadSet(x,255,radius)}</g></g><g data-component="wheelset"><circle cx="${x}" cy="255" r="${inner}" class="model-rim"/><g class="model-spokes">${spokeSet(x,255,inner)}</g><circle cx="${x}" cy="255" r="7" class="model-hub"/></g>`;

        let html = `<defs><linearGradient id="modelFramePaint" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#43ddad"/><stop offset="1" stop-color="#087a59"/></linearGradient></defs><style>
        .model-tire{fill:none;stroke:#101d2b;stroke-linecap:round}.model-treads path{stroke:#5b7080;stroke-width:2.1;stroke-linecap:round}.model-rim{fill:none;stroke:#b3c8d5;stroke-width:3}.model-spokes path{stroke:#829aaa;stroke-width:1.5}.model-hub{fill:#d5e5ed;stroke:#203c50;stroke-width:2}.bike-frame-line{fill:none;stroke:url(#modelFramePaint);stroke-width:11;stroke-linecap:round;stroke-linejoin:round}.bike-fork-line{fill:none;stroke:#d1e3eb;stroke-width:12;stroke-linecap:round}.bike-stem-line{fill:none;stroke:#d1e3eb;stroke-width:9;stroke-linecap:round}.bike-handlebar-line{fill:none;stroke:#b8cfdb;stroke-width:9;stroke-linecap:round;stroke-linejoin:round}.bike-detail-line{fill:none;stroke:#d5e5ed;stroke-width:6;stroke-linecap:round;stroke-linejoin:round}.bike-seatpost-line{fill:none;stroke:#d1e3eb;stroke-width:8;stroke-linecap:round}.bike-saddle-line{fill:none;stroke:#152738;stroke-width:14;stroke-linecap:round}.bike-chain-line{fill:none;stroke:#aabdc8;stroke-width:3}.bike-crank-ring{fill:none;stroke:#d1e3eb;stroke-width:5}.bike-crank-arm{stroke:#d1e3eb;stroke-width:7;stroke-linecap:round}.bike-hinge{fill:#f2bd55;stroke:#193c50;stroke-width:4}.model-disc{fill:none;stroke:#aabdc8;stroke-width:3}.model-rack{fill:none;stroke:#d6e5eb;stroke-width:6;stroke-linecap:round;stroke-linejoin:round}.model-bag{fill:#28576c;stroke:#e8b251;stroke-width:3}.model-box{fill:#9b633d;stroke:#f0bd6a;stroke-width:4;stroke-linejoin:round}.model-battery{fill:#244d68;stroke:#45d5e6;stroke-width:4}.model-motor{fill:#244d68;stroke:#45d5e6;stroke-width:5}.model-fender{fill:none;stroke:#637f91;stroke-width:7;stroke-linecap:round}.model-peg{stroke:#e8b251;stroke-width:8;stroke-linecap:round}.model-shock{fill:none;stroke:#e8b251;stroke-width:6;stroke-linecap:round}.model-extra{fill:none;stroke:#e8b251;stroke-width:4;stroke-linecap:round;stroke-linejoin:round}.bike-part-marker{cursor:pointer}.bike-marker-circle{fill:#102d47;stroke:#08b9d0;stroke-width:3}.bike-marker-text{fill:#fff;font-size:12px;font-weight:900;text-anchor:middle}.bike-marker-warning{display:none;fill:#e8a027}.bike-marker-warning-text{display:none;fill:#fff;font-size:10px;text-anchor:middle}.bike-part-marker.has-fit-warning .bike-marker-warning,.bike-part-marker.has-fit-warning .bike-marker-warning-text{display:block}
        </style>`;

        html += wheel(rear, 'rear') + wheel(front, 'front');
        html += `<g data-component="frame" class="bike-component-drawing">${line(p.frame, 'bike-frame-line')}${circle(bb[0],bb[1],10,'model-hub')}${circle(seat[0],seat[1],8,'model-hub')}${circle(head[0],head[1],9,'model-hub')}</g>`;
        html += `<g data-component="fork" class="bike-component-drawing">${line(p.fork,'bike-fork-line')}${p.suspension ? line(`M${head[0]+12} ${head[1]+18} L${front-24} 236`,'model-shock') : ''}</g>`;
        html += `<g data-component="headset" class="bike-component-drawing">${circle(head[0],head[1],13,'model-hub')}</g>`;
        html += `<g data-component="stem" class="bike-component-drawing">${line(p.stem,'bike-stem-line')}</g>`;
        html += `<g data-component="handlebar" class="bike-component-drawing">${line(p.bar,'bike-handlebar-line')}</g>`;
        html += `<g data-component="grips" class="bike-component-drawing">${line(`M${p.bar.match(/L([0-9.]+) ([0-9.]+)$/)?.[1] || 470} ${p.bar.match(/L([0-9.]+) ([0-9.]+)$/)?.[2] || 102} l10 2`,'bike-detail-line')}</g>`;
        html += `<g data-component="seatpost" class="bike-component-drawing">${line(`M${seat[0]} ${seat[1]} L${seat[0]-5} ${seat[1]-28}`,'bike-seatpost-line')}</g>`;
        html += `<g data-component="saddle" class="bike-component-drawing">${line(`M${seat[0]-28} ${seat[1]-29} Q${seat[0]} ${seat[1]-37} ${seat[0]+30} ${seat[1]-27}`,'bike-saddle-line')}</g>`;
        html += `<g data-component="bottom_bracket" class="bike-component-drawing">${circle(bb[0],bb[1],12,'model-hub')}</g>`;
        html += `<g data-component="crankset" class="bike-component-drawing">${circle(bb[0],bb[1],20,'bike-crank-ring')}${line(`M${bb[0]} ${bb[1]} L${bb[0]+31} ${bb[1]+30}`,'bike-crank-arm')}</g>`;
        html += `<g data-component="pedals" class="bike-component-drawing">${line(`M${bb[0]+25} ${bb[1]+30} h28`,'bike-detail-line')}</g>`;
        html += `<g data-component="chain" class="bike-component-drawing">${line(`M${rear+4} 246 L${bb[0]-18} 246 M${rear+4} 264 L${bb[0]-18} 264`,'bike-chain-line')}</g>`;
        html += `<g data-component="cassette" class="bike-component-drawing">${circle(rear,255,15,'model-disc')}</g>`;
        html += `<g data-component="shifter" class="bike-component-drawing">${circle(452,103,5,'model-hub')}</g>`;
        html += `<g data-component="front_derailleur" class="bike-component-drawing">${p.singleSpeed ? '' : line(`M${bb[0]+34} ${bb[1]-8} l18 -12`,'model-extra')}</g>`;
        html += `<g data-component="rear_derailleur" class="bike-component-drawing">${p.singleSpeed ? '' : line(`M${rear+10} 266 l18 22 l-8 10`,'model-extra')}</g>`;
        html += `<g data-component="front_brake" class="bike-component-drawing">${p.disc ? circle(front,255,24,'model-disc') : line(`M${front-15} ${255-radius+15} q15 -14 30 0`,'model-extra')}</g>`;
        html += `<g data-component="rear_brake" class="bike-component-drawing">${p.disc ? circle(rear,255,24,'model-disc') : line(`M${rear-15} ${255-radius+15} q15 -14 30 0`,'model-extra')}</g>`;

        if (p.hinge) html += `<g data-component="frame" class="bike-component-drawing">${circle(337,208,13,'bike-hinge')}${line('M329 200 L345 216','model-extra')}</g>`;
        if (p.rack || p.panniers) html += `<g data-component="cargo_rack" class="bike-component-drawing">${line(`M${rear-54} 145 H${rear+62} L${rear+68} 155 M${rear-38} 145 V186 M${rear+48} 145 V186`,'model-rack')}${p.panniers ? '<rect x="112" y="151" width="42" height="52" rx="5" class="model-bag"/><rect x="169" y="151" width="42" height="52" rx="5" class="model-bag"/>' : ''}</g>`;
        if (p.cargo) html += `<g data-component="cargo_rack" class="bike-component-drawing"><path d="M410 92 H535 L555 160 H425 Z" class="model-box"/><path d="M425 107 H531 M430 145 H545" class="model-extra"/><path d="M430 160 V185 M535 160 V185" class="model-rack"/></g>`;
        if (p.battery) html += `<g data-component="battery" class="bike-component-drawing"><path d="M${bb[0]-7} ${bb[1]-93} L${bb[0]+25} ${bb[1]-86} L${bb[0]+11} ${bb[1]-28} L${bb[0]-15} ${bb[1]-35} Z" class="model-battery"/><path d="M${bb[0]+2} ${bb[1]-79} l10 13 l-9 10 l10 8" class="model-extra"/></g>`;
        if (p.motor) html += `<g data-component="motor" class="bike-component-drawing">${circle(bb[0],bb[1],25,'model-motor')}${circle(bb[0],bb[1],9,'model-hub')}</g>`;
        if (p.display) html += `<g data-component="controller" class="bike-component-drawing">${line(`M${head[0]+18} ${head[1]-29} l16 -16`,'model-extra')}${circle(head[0]+35,head[1]-48,6,'model-hub')}</g>`;
        if (p.pegs) html += `<g>${line(`M${rear-28} 255 h-32 M${front+28} 255 h32`,'model-peg')}</g>`;
        if (p.fixed) html += `<g>${circle(rear,255,10,'model-disc')}${line(`M${rear} 255 L${bb[0]} 255`,'model-extra')}</g>`;
        if (p.flat) html += `<g>${line(`M${head[0]+20} ${head[1]-28} h48`,'model-extra')}</g>`;
        if (p.drop) html += `<g>${line(`M${head[0]+20} ${head[1]-22} q28 -42 62 -22 l-10 28 l-22 8`,'model-extra')}</g>`;
        if (p.rack || p.panniers || p.cargo) html += `<g data-component="cargo_rack" class="bike-component-drawing"></g>`;
        if (p.fender) html += `<g>${line(`M${rear-65} 195 Q${rear} 140 ${rear+65} 195 M${front-65} 195 Q${front} 140 ${front+65} 195`,'model-fender')}</g>`;

        svg.innerHTML = html;
        markerNodes.forEach(node => svg.appendChild(node));

        const markerPositions = {
            frame: [(seat[0] + head[0]) / 2, (seat[1] + head[1]) / 2 + 34],
            fork: [head[0] + (front - head[0]) * .68, head[1] + (255 - head[1]) * .68 + 15],
            handlebar: p.drop ? [head[0] + 62, head[1] - 24] : [head[0] + 48, head[1] - 58],
            stem: [head[0] + 20, head[1] - 35],
            headset: [head[0] - 19, head[1] + 24],
            grips: p.drop ? [head[0] + 82, head[1] + 17] : [head[0] + 88, head[1] - 23],
            saddle: [seat[0] - 8, seat[1] - 55],
            seatpost: [seat[0] + 22, seat[1] - 20],
            wheelset: [front + radius * .53, 255 + radius * .73],
            front_tire: [front + radius * .70, 255 - radius * .68],
            rear_tire: [rear - radius * .70, 255 - radius * .68],
            crankset: [bb[0] + 43, bb[1] + 34],
            bottom_bracket: [bb[0] - 28, bb[1] - 12],
            pedals: [bb[0] + 76, bb[1] + 58],
            chain: [(rear + bb[0]) / 2, bb[1] + 30],
            cassette: [rear + 24, 255 + 20],
            shifter: p.drop ? [head[0] + 45, head[1] + 30] : [head[0] + 32, head[1] - 12],
            front_derailleur: [bb[0] + 50, bb[1] - 37],
            rear_derailleur: [rear + 17, 255 + radius * .50],
            front_brake: [front - radius * .83, 255 - radius * .76],
            rear_brake: [rear + radius * .83, 255 - radius * .76],
            motor: [bb[0] + 20, bb[1] - 67],
            battery: [bb[0] + 10, bb[1] - 108],
            controller: [head[0] + 72, head[1] - 70],
            cargo_rack: p.cargo ? [505, 80] : [rear + 43, 116]
        };

        markerNodes.forEach(node => {
            const key = node.dataset.component;
            const point = markerPositions[key];
            if (!point) return;
            const x = Math.max(18, Math.min(682, point[0]));
            const y = Math.max(20, Math.min(370, point[1]));
            node.setAttribute('transform', `translate(${x} ${y})`);
        });

        const config = window.BICYCLE_BUILDER || {};
        const allowedParts = new Set((config.bikeComponentMap && config.bikeComponentMap[type]) || Object.keys(config.components || {}));
        const selectedParts = (config.initial && config.initial.parts) || {};

        Array.from(svg.children).forEach(node => {
            const key = node.dataset && node.dataset.component;
            if (!key) return;
            node.hidden = !allowedParts.has(key);
            node.classList.toggle('is-selected', Boolean(selectedParts[key]));
        });

        svg.dataset.bikeType = type;
        svg.setAttribute('aria-label', `${type} bicycle 2D model`);
    }

    function selectedType() {
        const selected = document.querySelector('.bike-type-card.selected');
        if (selected) return selected.dataset.bikeType;
        const initial = window.BICYCLE_BUILDER && window.BICYCLE_BUILDER.initial;
        if (initial && initial.bikeType) return initial.bikeType;
        const saved = document.getElementById('savedBicycle');
        if (saved && saved.selectedOptions && saved.selectedOptions[0]) return saved.selectedOptions[0].dataset.type || '';
        return 'Custom Build';
    }

    function update() {
        const type = selectedType();
        if (type) render(type);
    }

    document.addEventListener('click', event => {
        if (event.target.closest('.bike-type-card')) window.requestAnimationFrame(update);
    });

    const saved = document.getElementById('savedBicycle');
    if (saved) saved.addEventListener('change', () => window.requestAnimationFrame(update));

    const grid = document.getElementById('bikeTypeGrid');
    if (grid && window.MutationObserver) {
        const observer = new MutationObserver(() => window.requestAnimationFrame(update));
        observer.observe(grid, { subtree: true, attributes: true, attributeFilter: ['class'] });
    }

    update();
});
