import { Tabs } from 'expo-router/js-tabs';

import { CustomTabBar } from '@/navigation/CustomTabBar';
import { useNotifications } from '@/lib/queries';

export default function AppTabsLayout() {
  const notifications = useNotifications();
  const unreadCount = notifications.data?.data.filter((n) => !n.readAt).length ?? 0;

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
