import { Stack } from 'expo-router';

export default function KasStackLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="transparansi" />
      <Stack.Screen name="report/[id]" />
      <Stack.Screen name="iuran" />
      <Stack.Screen name="susun-laporan" />
      <Stack.Screen name="periksa-laporan/[id]" />
    </Stack>
  );
}
