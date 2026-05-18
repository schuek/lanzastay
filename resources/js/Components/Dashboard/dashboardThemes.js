/** Paleta LanzaStay — acentos sutiles (filete superior), texto castaño */
export const VOLCANIC = '#2F2A26';

export const DEPARTMENT_COLORS = {
    kitchen: '#A64B35',
    cleaning: '#5FC34B',
    reception: '#0A6ACF',
    maintenance: '#2F2A26',
    admin: '#0A6ACF',
};

/** Filete superior por departamento — sin marcos de color completos */
export const accentTop = (department) => {
    const map = {
        kitchen: 'border-t-2 border-t-[#A64B35]',
        cleaning: 'border-t-2 border-t-[#5FC34B]',
        reception: 'border-t-2 border-t-[#0A6ACF]',
        maintenance: 'border-t-2 border-t-[#2F2A26]',
        admin: 'border-t-2 border-t-[#0A6ACF]',
    };
    return map[department] ?? map.reception;
};

export const CARD_SHELL =
    'rounded-xl border border-[#2F2A26]/8 bg-white shadow-sm transition-shadow duration-200 hover:shadow-md';

export const CARD_INTERACTIVE =
    'cursor-pointer transition-shadow duration-200 hover:shadow-md';

export const DEPARTMENT_THEMES = {
    kitchen: { iconHoverBg: 'group-hover:bg-[#2F2A26]/5' },
    cleaning: { iconHoverBg: 'group-hover:bg-[#2F2A26]/5' },
    reception: { iconHoverBg: 'group-hover:bg-[#2F2A26]/5' },
    maintenance: { iconHoverBg: 'group-hover:bg-[#2F2A26]/5' },
    admin: { iconHoverBg: 'group-hover:bg-[#2F2A26]/5' },
};

export const ACTION_ICON_CLASS =
    'text-base text-[#2F2A26]/75 transition duration-200 group-hover:text-[#2F2A26]';

export const themeFor = (department) => DEPARTMENT_THEMES[department] ?? DEPARTMENT_THEMES.reception;

export const metricCardClass = (department) =>
    [CARD_SHELL, accentTop(department), CARD_INTERACTIVE].join(' ');

export const panelCardClass = (department) =>
    [CARD_SHELL, accentTop(department), CARD_INTERACTIVE].join(' ');
