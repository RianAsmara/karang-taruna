import {
  Archivo_400Regular,
  Archivo_600SemiBold,
  Archivo_800ExtraBold,
  useFonts,
} from "@expo-google-fonts/archivo";
import { QueryClient, QueryClientProvider } from "@tanstack/react-query";
import { Stack } from "expo-router";
import * as SplashScreen from "expo-splash-screen";
import { useEffect } from "react";
import { Platform, View } from "react-native";
import { GestureHandlerRootView } from "react-native-gesture-handler";
import { SafeAreaProvider, SafeAreaView } from "react-native-safe-area-context";

import { SessionExpiredModal } from "@/components/SessionExpiredModal";
import { ToastProvider } from "@/components/Toast";
import { useAuth } from "@/store/useAuth";
import { ThemeProvider, useTheme } from "@/theme/ThemeProvider";

const queryClient = new QueryClient();

SplashScreen.preventAutoHideAsync();

function RootStack() {
  const { theme } = useTheme();

  return (
    <Stack
      screenOptions={{
        headerShown: false,
        contentStyle: { backgroundColor: theme.color.bg },
      }}
    >
      <Stack.Screen name="index" />
      <Stack.Screen name="login" />
      <Stack.Screen name="(app)" />
      <Stack.Screen name="profil" options={{ presentation: "modal" }} />
      <Stack.Screen name="anggota" />
      <Stack.Screen name="inventaris" />
      <Stack.Screen name="dokumen" />
      <Stack.Screen name="sponsor" />
      <Stack.Screen name="voting" />
      <Stack.Screen name="pencarian" />
      <Stack.Screen name="iuran-saya" options={{ presentation: "modal" }} />
      <Stack.Screen name="belum-tersedia/[topic]" />
    </Stack>
  );
}

/**
 * Web-only phone-width frame — a desktop browser tab has no notion of a
 * 390px device viewport on its own. This is purely a preview affordance
 * for viewing/screenshotting the app on web; it plays no role on iOS/
 * Android, where the physical device already constrains the viewport.
 */
function WebFrame({ children }: { children: React.ReactNode }) {
  if (Platform.OS !== "web") return children as React.ReactElement;
  return (
    <SafeAreaView style={{ flex: 1 }}>
      <View
        style={{ flex: 1, alignItems: "center", backgroundColor: "#00000022" }}
      >
        <View
          style={{
            width: 390,
            height: "100%",
            maxHeight: 844,
            overflow: "hidden",
          }}
        >
          {children}
        </View>
      </View>
    </SafeAreaView>
  );
}

export default function RootLayout() {
  const [fontsLoaded] = useFonts({
    Archivo_400Regular,
    Archivo_600SemiBold,
    Archivo_800ExtraBold,
  });
  const authStatus = useAuth((state) => state.status);
  const bootstrap = useAuth((state) => state.bootstrap);

  useEffect(() => {
    bootstrap();
  }, [bootstrap]);

  useEffect(() => {
    if (fontsLoaded && authStatus !== "loading") SplashScreen.hideAsync();
  }, [fontsLoaded, authStatus]);

  if (!fontsLoaded || authStatus === "loading") return null;

  return (
    <GestureHandlerRootView style={{ flex: 1 }}>
      <QueryClientProvider client={queryClient}>
        <SafeAreaProvider>
          <ThemeProvider>
            <ToastProvider>
              <WebFrame>
                <RootStack />
              </WebFrame>
              <SessionExpiredModal />
            </ToastProvider>
          </ThemeProvider>
        </SafeAreaProvider>
      </QueryClientProvider>
    </GestureHandlerRootView>
  );
}
