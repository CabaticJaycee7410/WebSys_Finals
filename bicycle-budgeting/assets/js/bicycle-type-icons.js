document.addEventListener('DOMContentLoaded', () => {
    const types = ['Mountain Bike', 'Road Bike', 'BMX', 'Gravel Bike', 'Hybrid Bike', 'Touring Bike', 'Folding Bike', 'Fat Bike', 'Track / Fixed Gear', 'Electric Bike', 'Cargo Bike', 'Custom Build'];
    const profiles = {
        'Mountain Bike': { r: 21, t: 5.5, frame: 'M25 60 L64 60 L48 32 L25 60 M64 60 L96 30 L120 60 L64 60 M48 32 L96 30', bar: 'M96 30 L101 16 L116 16 M101 16 L124 19', fork: 'M96 30 L120 60', extra: '<path d="M100 35 L116 55 M93 34 L110 53" class="accent"/>' },
        'Road Bike': { r: 20, t: 2.8, frame: 'M25 60 L65 60 L49 33 L25 60 M65 60 L96 31 L120 60 L65 60 M49 33 L96 31', bar: 'M96 31 L103 17 Q118 12 116 25 L108 32 L100 29', fork: 'M96 31 L120 60', extra: '<path d="M108 32 L111 40" class="metal"/>' },
        'BMX': { r: 18, t: 5, frame: 'M25 60 L66 60 L53 40 L25 60 M66 60 L88 37 L120 60 L66 60 M53 40 L88 37', bar: 'M88 37 L91 13 L111 13 M91 13 L116 18', fork: 'M88 37 L120 60', extra: '<path d="M14 60 H36 M109 60 H131 M44 28 L61 28" class="accent"/>' },
        'Gravel Bike': { r: 21, t: 4.4, frame: 'M25 60 L65 60 L49 33 L25 60 M65 60 L96 31 L120 60 L65 60 M49 33 L96 31', bar: 'M96 31 L103 17 Q119 13 117 25 L108 32 L101 28', fork: 'M96 31 L120 60', extra: '<path d="M15 48 L18 45 M14 55 L18 53 M127 48 L130 45 M127 55 L131 53" class="accent"/>' },
        'Hybrid Bike': { r: 21, t: 4.3, frame: 'M25 60 L65 60 L49 34 L25 60 M65 60 L95 32 L120 60 L65 60 M49 34 L95 32', bar: 'M95 32 L101 17 L119 17 M101 17 L124 20', fork: 'M95 32 L120 60', extra: '<path d="M42 23 L56 23" class="metal"/>' },
        'Touring Bike': { r: 21, t: 4.3, frame: 'M25 60 L65 60 L49 34 L25 60 M65 60 L95 32 L120 60 L65 60 M49 34 L95 32', bar: 'M95 32 L102 17 Q118 13 116 25 L108 32', fork: 'M95 32 L120 60', extra: '<path d="M12 23 H52 L56 28 H15 Z M105 21 H130 L134 27 H109 Z M14 25 V43 H31 V25 M109 25 V43 H127 V25 M13 17 H54 M106 15 H133" class="accent"/>' },
        'Folding Bike': { r: 17, t: 4.5, frame: 'M25 60 L66 60 L53 42 L25 60 M66 60 L88 37 L120 60 L66 60 M53 42 L88 37 M66 60 L80 42', bar: 'M88 37 L95 16 L113 16 M95 16 L119 20', fork: 'M88 37 L120 60', extra: '<circle cx="70" cy="49" r="4.6" class="accent"/><path d="M66 45 L74 53" class="metal"/>' },
        'Fat Bike': { r: 21, t: 8, frame: 'M25 60 L65 60 L49 34 L25 60 M65 60 L95 32 L120 60 L65 60 M49 34 L95 32', bar: 'M95 32 L101 17 L119 17 M101 17 L124 20', fork: 'M95 32 L120 60', extra: '<path d="M12 46 L16 43 M12 54 L16 51 M129 46 L133 43 M129 54 L133 51" class="accent"/>' },
        'Track / Fixed Gear': { r: 20, t: 3.2, frame: 'M25 60 L65 60 L48 30 L25 60 M65 60 L97 29 L120 60 L65 60 M48 30 L97 29', bar: 'M97 29 L104 16 Q119 12 117 25 L108 31', fork: 'M97 29 L120 60', extra: '<circle cx="65" cy="60" r="8" class="accent"/><path d="M65 60 L80 70 M75 70 H87" class="accent"/>' },
        'Electric Bike': { r: 21, t: 5.1, frame: 'M25 60 L65 60 L49 34 L25 60 M65 60 L95 32 L120 60 L65 60 M49 34 L95 32', bar: 'M95 32 L101 17 L119 17 M101 17 L124 20', fork: 'M95 32 L120 60', extra: '<path d="M51 37 L77 37 L72 58 L61 58 Z" class="accent"/><circle cx="65" cy="60" r="8" class="accent"/><path d="M64 42 L71 46 L65 51 L73 54" class="metal"/>' },
        'Cargo Bike': { r: 20, t: 4.6, frame: 'M25 60 L65 60 L49 34 L25 60 M65 60 L95 32 L120 60 L65 60 M49 34 L95 32 M65 60 L105 58', bar: 'M95 32 L101 17 L119 17 M101 17 L124 20', fork: 'M95 32 L120 60', extra: '<path d="M100 8 H139 L145 28 H105 Z" fill="#b76d36" stroke="#f0b84e" stroke-width="2.5"/><path d="M107 28 V42 M139 28 V42 M105 14 H139" class="accent"/>' },
        'Custom Build': { r: 21, t: 4.3, frame: 'M25 60 L65 60 L46 36 L25 60 M65 60 L96 28 L120 60 L65 60 M46 36 L96 28', bar: 'M96 28 L105 13 L122 14 M105 13 L126 17', fork: 'M96 28 L120 60', extra: '<path d="M51 25 L58 32 L51 39 L44 32 Z" class="accent"/>' }
    };
    const svgFor = type => {
        const p = profiles[type] || profiles['Custom Build'];
        const r = p.r;
        const inner = r - 4;
        return `<svg class="bicycle-type-svg bicycle-type-svg-garage" viewBox="0 0 150 82" width="58" height="48" role="img" aria-label="${type} illustration" xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="25" cy="60" r="${r}" class="tire" style="stroke-width:${p.t}px"/><circle cx="120" cy="60" r="${r}" class="tire" style="stroke-width:${p.t}px"/><circle cx="25" cy="60" r="${inner}" class="rim"/><circle cx="120" cy="60" r="${inner}" class="rim"/><path d="M25 60 V${60-r+4} M25 60 H${25+r-4} M25 60 L${25-r+4} ${60-r+4} M25 60 L${25-r+4} ${60+r-4} M120 60 V${60-r+4} M120 60 H${120+r-4} M120 60 L${120-r+4} ${60-r+4} M120 60 L${120-r+4} ${60+r-4}" class="spoke"/><path d="${p.frame}" class="frame"/><path d="${p.fork} ${p.bar}" class="metal"/><path d="M49 34 L45 18 M37 17 H57 Q61 17 59 21 L55 24 H39 Q35 22 37 17 Z" class="seat"/><path d="M65 60 L81 70 M77 70 H88" class="metal"/><circle cx="65" cy="60" r="7" class="accent"/><circle cx="25" cy="60" r="2.4" fill="#d2e4ed" stroke="none"/><circle cx="120" cy="60" r="2.4" fill="#d2e4ed" stroke="none"/>${p.extra}</g><style>.bicycle-type-svg-garage .tire{stroke:#172d3d}.bicycle-type-svg-garage .rim{stroke:#8ba5b5;stroke-width:1.7}.bicycle-type-svg-garage .spoke{stroke:#718da2;stroke-width:.9}.bicycle-type-svg-garage .frame{stroke:#168b61;stroke-width:4.2}.bicycle-type-svg-garage .metal{stroke:#d2e4ed;stroke-width:2.4}.bicycle-type-svg-garage .seat{fill:#172d3d;stroke:#d2e4ed;stroke-width:1.4}.bicycle-type-svg-garage .accent{stroke:#d9a23c;stroke-width:3}</style></svg>`;
    };
    const findType = element => {
        const direct = element.dataset.bicycleType || element.dataset.bikeType;
        if (direct && profiles[direct]) return direct;
        const parent = element.parentElement;
        const card = element.closest('[data-bicycle-card], .garage-bicycle-card, .bicycle-card, .project-card, article') || parent;
        const labelled = card && card.querySelector('[data-bike-type-label], .bicycle-meta, .garage-bike-details p, .project-card-details p');
        const detailText = labelled ? labelled.textContent : [parent && parent.textContent, card && card.textContent].filter(Boolean).join(' ');
        const lower = detailText.toLowerCase();
        return types.find(type => lower.indexOf(type.toLowerCase()) !== -1) || '';
    };
    document.querySelectorAll('.project-bike-icon, .garage-bike-icon').forEach(element => {
        const type = findType(element);
        if (!type) return;
        element.innerHTML = svgFor(type);
        element.setAttribute('role', 'img');
        element.setAttribute('aria-label', type);
        element.dataset.bicycleType = type;
    });
});