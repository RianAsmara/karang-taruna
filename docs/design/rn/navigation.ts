// Structure only. Four tabs, never a fifth. Max 3 levels deep from a tab.
export const routes = {
  RootStack: {
    Splash: {},
    Login: {},
    App: {
      BottomTabs: {
        HomeTab:       ['Home', 'EventDetail'],
        KegiatanTab:   ['Kegiatan', 'EventDetail'],
        KasTab:        ['Kas', 'Transparansi', 'ReportDetail'],
        NotifikasiTab: ['Notifikasi'],
      },
    },
    // presentation: 'modal'
    Profil: {},
    IuranSaya: {},
  },
} as const;

export const tabs = [
  { name: 'HomeTab',       label: 'Home',       icon: 'home' },
  { name: 'KegiatanTab',   label: 'Kegiatan',   icon: 'calendar' },
  { name: 'KasTab',        label: 'Kas',        icon: 'wallet' },
  { name: 'NotifikasiTab', label: 'Notifikasi', icon: 'bell', badge: 'pendingActionCount' },
] as const;

// Tab bar: height 56 + insets.bottom, bg = color.bg, 2px top rule = color.rule.
// Active tab: icon + label in color.text, plus a 2px color.accent line flush to the tab's top edge.
// Inactive: color.textFaint. Label 10px / weight 800.
// Badge: square, color.accent fill, onAccent text 9px/800, hidden at 0. Notifikasi tab only.
// EventDetail is registered in both HomeStack and KegiatanStack so the tab matches where you came from.
// Deep link: rukunmuda.id/<org>/<month> -> ReportDetail (public web page for people without the app).
