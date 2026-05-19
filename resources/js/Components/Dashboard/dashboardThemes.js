/** Paleta LanzaStay — tarjetas del panel unificadas */
export const VOLCANIC = '#2F2A26';
export const OXIDE = '#A64B35';

/** @deprecated Sin filete parcial */
export const accentTop = () => '';

export const CARD_SHELL =
    'rounded-2xl border border-[#A64B35]/35 bg-white shadow-[0_1px_3px_rgba(47,42,38,0.05)] transition-all duration-200 hover:border-[#A64B35] hover:shadow-[0_6px_20px_rgba(166,75,53,0.1)]';

export const CARD_INTERACTIVE = 'cursor-pointer';

export const ICON_RING_CLASS =
    'flex shrink-0 items-center justify-center rounded-2xl bg-[#A64B35]/[0.08] ring-1 ring-[#A64B35]/15';

export const ICON_METRIC_SIZE = 'h-12 w-12';
export const ICON_NAV_SIZE = 'h-14 w-14';
export const ICON_STRIP_SIZE = 'h-11 w-11';

export const ACTION_ICON_CLASS = 'text-[#A64B35]';

export const DEPARTMENT_THEMES = {
    kitchen: { iconHoverBg: '' },
    cleaning: { iconHoverBg: '' },
    reception: { iconHoverBg: '' },
    maintenance: { iconHoverBg: '' },
    admin: { iconHoverBg: '' },
};

export const themeFor = (department) => DEPARTMENT_THEMES[department] ?? DEPARTMENT_THEMES.reception;

export const metricCardClass = () => [CARD_SHELL, CARD_INTERACTIVE].join(' ');

export const panelCardClass = () => [CARD_SHELL, CARD_INTERACTIVE].join(' ');
