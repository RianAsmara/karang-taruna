import { Stack } from 'expo-router';

export default function KegiatanStackLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="buat" />
      <Stack.Screen name="event/[id]" />
    </Stack>
  );
}
