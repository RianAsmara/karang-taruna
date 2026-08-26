// Archivo 400 / 600 / 800 only. No italic, no 300, no 700.
export const type = {
  display: { fontFamily: 'Archivo_800ExtraBold', fontSize: 40, lineHeight: 42, letterSpacing: -1.2 },
  h1:      { fontFamily: 'Archivo_800ExtraBold', fontSize: 30, lineHeight: 33, letterSpacing: -0.8 },
  h2:      { fontFamily: 'Archivo_800ExtraBold', fontSize: 26, lineHeight: 30, letterSpacing: -0.6 },
  title:   { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, lineHeight: 22 },
  section: { fontFamily: 'Archivo_800ExtraBold', fontSize: 11, lineHeight: 14, letterSpacing: 1.0, textTransform: 'uppercase' as const },
  body:    { fontFamily: 'Archivo_400Regular',   fontSize: 15, lineHeight: 23 },
  bodySm:  { fontFamily: 'Archivo_400Regular',   fontSize: 13, lineHeight: 19 },
  caption: { fontFamily: 'Archivo_600SemiBold',  fontSize: 11, lineHeight: 15, letterSpacing: 0.7 },
  // money — tabular-nums is mandatory on every one of these
  numHero: { fontFamily: 'Archivo_800ExtraBold', fontSize: 44, lineHeight: 46, letterSpacing: -1.3, fontVariant: ['tabular-nums' as const] },
  numLg:   { fontFamily: 'Archivo_800ExtraBold', fontSize: 20, lineHeight: 24, fontVariant: ['tabular-nums' as const] },
  numRow:  { fontFamily: 'Archivo_800ExtraBold', fontSize: 15, lineHeight: 20, fontVariant: ['tabular-nums' as const] },
} as const;
