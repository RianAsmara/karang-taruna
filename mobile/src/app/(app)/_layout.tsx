import { Redirect } from 'expo-router';
import { Tabs } from 'expo-router/js-tabs';
import { View } from 'react-native';

import { CustomTabBar } from '@/navigation/CustomTabBar';
import { ApiError } from '@/lib/api';
import { useCurrentOrganization, useNotifications } from '@/lib/queries';
import { useTheme } from '@/theme/ThemeProvider';

/**
 * Every tab assumes a current organization exists — gate here, once,
 * rather than in each of the 11 screens. A freshly registered user (see
 * RegisterScreen) has no membership yet; ResolveCurrentOrganization
 * answers that with a 422, which is this app's *only* production of
 * that status on this endpoint — safe to read as "no org" rather than
 * a generic fetch failure.
 */
export default function AppTabsLayout() {
  const { theme } = useTheme();
  const organization = useCurrentOrganization();
  const notifications = useNotifications();
  const unreadCount = notifications.data?.data.filter((n) => !n.readAt).length ?? 0;

  if (organization.isPending) {
    return <View style={{ flex: 1, backgroundColor: theme.color.bg }} />;
  }

  if (organization.isError && organization.error instanceof ApiError && organization.error.status === 422) {
    return <Redirect href="/buat-organisasi" />;
  }

  return (
    <Tabs
      screenOptions={{ headerShown: false }}
      tabBar={(props) => <CustomTabBar {...props} pendingActionCount={unreadCount} />}
    >
      <Tabs.Screen name="home" />
      <Tabs.Screen name="kegiatan" />
      <Tabs.Screen name="kas" />
      <Tabs.Screen name="notifikasi" />
    </Tabs>
  );
}
