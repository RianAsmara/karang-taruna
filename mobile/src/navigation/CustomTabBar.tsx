import type { BottomTabBarProps } from 'expo-router/js-tabs';
import { Bell, Calendar, Home, Wallet } from 'lucide-react-native';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

const TAB_CONFIG = [
  { name: 'home', label: 'Home', Icon: Home },
  { name: 'kegiatan', label: 'Kegiatan', Icon: Calendar },
  { name: 'kas', label: 'Kas', Icon: Wallet },
  { name: 'notifikasi', label: 'Notifikasi', Icon: Bell },
] as const;

export function CustomTabBar({ state, navigation, pendingActionCount }: BottomTabBarProps & { pendingActionCount: number }) {
  const { theme } = useTheme();
  const insets = useSafeAreaInsets();
  const styles = makeStyles(theme);

  return (
    <View style={[styles.bar, { height: theme.layout.tabBarHeight + insets.bottom, paddingBottom: insets.bottom }]}>
      {state.routes.map((route, index) => {
        const config = TAB_CONFIG.find((t) => t.name === route.name) ?? TAB_CONFIG[0];
        const focused = state.index === index;
        const Icon = config.Icon;
        const showBadge = config.name === 'notifikasi' && pendingActionCount > 0;

        return (
          <Pressable
            key={route.key}
            onPress={() => {
              const event = navigation.emit({ type: 'tabPress', target: route.key, canPreventDefault: true });
              if (!focused && !event.defaultPrevented) navigation.navigate(route.name);
            }}
            hitSlop={theme.hitSlop}
            android_ripple={null}
            accessibilityRole="button"
            accessibilityState={{ selected: focused }}
            accessibilityLabel={config.label}
            style={styles.tab}
          >
            {focused ? <View style={styles.activeLine} /> : null}
            <View style={styles.iconWrap}>
              <Icon size={22} strokeWidth={1.75} color={focused ? theme.color.text : theme.color.textFaint} />
              {showBadge ? (
                <View style={styles.badge}>
                  <Text style={styles.badgeLabel}>{pendingActionCount}</Text>
                </View>
              ) : null}
            </View>
            <Text style={[styles.label, { color: focused ? theme.color.text : theme.color.textFaint }]}>
              {config.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    bar: {
      flexDirection: 'row',
      backgroundColor: theme.color.bg,
      borderTopWidth: 2,
      borderTopColor: theme.color.rule,
    },
    tab: { flex: 1, alignItems: 'center', justifyContent: 'center', paddingTop: 6 },
    activeLine: { position: 'absolute', top: 0, left: 0, right: 0, height: 2, backgroundColor: theme.color.accent },
    iconWrap: { position: 'relative' },
    badge: {
      position: 'absolute',
      top: -4,
      right: -8,
      minWidth: 14,
      height: 14,
      paddingHorizontal: 2,
      backgroundColor: theme.color.accent,
      alignItems: 'center',
      justifyContent: 'center',
      borderRadius: theme.radius,
    },
    badgeLabel: { fontFamily: 'Archivo_800ExtraBold', fontSize: 9, color: theme.color.onAccent },
    label: { fontFamily: 'Archivo_800ExtraBold', fontSize: 10, marginTop: 3 },
  });
}
