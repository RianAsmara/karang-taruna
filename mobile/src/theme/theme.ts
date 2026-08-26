// RukunMuda design tokens — single source of truth. Do not duplicate values elsewhere.
export const light = {
  color: {
    bg:         '#f3f2f2',
    surface:    '#ffffff',
    surfaceAlt: '#eae9e9',
    text:       '#201e1d',
    textMuted:  '#6b6867',
    textFaint:  '#8b8887',
    divider:    '#c9c6c5', // 1px row rules
    rule:       '#8d8988', // 2px section rules
    accent:     '#ec3013',
    accent200:  '#ffe0d9',
    accent700:  '#ae1800',
    accent800:  '#7c1405',
    neutral300: '#d7d3d3',
    skeleton:   '#e3e0e0',
    // finance semantics — never rely on these alone, always pair with sign + label
    income:     '#201e1d',
    expense:    '#ae1800',
    balance:    '#201e1d',
    onAccent:   '#f3f2f2',
    onInk:      '#f3f2f2',
  },
  space: { xs: 4, sm: 8, md: 12, lg: 16, xl: 24, xxl: 32, xxxl: 48 },
  radius: 0, // zero everywhere, including avatars, inputs, sheets, dialogs
  hitSlop: { top: 12, bottom: 12, left: 12, right: 12 },
  layout: {
    screenPadding: 16,
    headerHeight: 56,
    tabBarHeight: 56,
    rowMinHeight: 64,
    buttonHeight: 48,
    minTouch: 48,
  },
  shadow: {
    sm: { shadowColor: '#2d2b2b', shadowOpacity: 0.14, shadowRadius: 2,  shadowOffset: { width: 0, height: 1 },  elevation: 2 },
    md: { shadowColor: '#2d2b2b', shadowOpacity: 0.16, shadowRadius: 10, shadowOffset: { width: 0, height: 3 },  elevation: 6 },
    lg: { shadowColor: '#2d2b2b', shadowOpacity: 0.22, shadowRadius: 32, shadowOffset: { width: 0, height: 12 }, elevation: 16 },
  },
  motion: { sheetIn: 240, sheetOut: 180, fade: 160, toast: 2600, toastUndo: 6000 },
} as const;

export const dark = {
  ...light,
  color: {
    ...light.color,
    bg: '#1a1918', surface: '#242221', surfaceAlt: '#2d2b2a',
    text: '#f3f2f2', textMuted: '#a9a5a4', textFaint: '#8b8887',
    divider: '#3d3a39', rule: '#5c5857',
    accent: '#ff563c', accent200: '#7c1405', accent700: '#ff9783', accent800: '#ffc4b8',
    skeleton: '#332f2e',
    income: '#f3f2f2', expense: '#ff9783', balance: '#f3f2f2',
    onAccent: '#1a1918', onInk: '#1a1918',
  },
} as const;

export type Theme = typeof light;
