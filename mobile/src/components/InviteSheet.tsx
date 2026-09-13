import { Share, StyleSheet, Text, View } from 'react-native';

import { Button } from '@/components/Button';
import { useToast } from '@/components/Toast';
import { ApiError } from '@/lib/api';
import { useCreateInvite, useInvites, useRevokeInvite } from '@/lib/queries';
import { formatDateShort } from '@/theme/format';
import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { BottomSheet } from './BottomSheet';
import { Skeleton } from './Skeleton';

/**
 * Generate and share a join link (ADR-0021). The chair sends it wherever the
 * organization already talks — almost always a WhatsApp group — so the primary
 * action is the OS share sheet, not a copy button.
 *
 * Accepting a link is a web route by design: the invitee may have no account
 * and no app installed yet, so the link has to work in a browser.
 */
export function InviteSheet({ visible, onClose }: { visible: boolean; onClose: () => void }) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const { showToast } = useToast();

  const invites = useInvites(visible);
  const createInvite = useCreateInvite();
  const revokeInvite = useRevokeInvite();

  const active = invites.data?.data.filter((invite) => invite.isActive) ?? [];

  const share = async (url: string) => {
    try {
      await Share.share({
        message: `Gabung ke organisasi kami di RukunMuda: ${url}`,
      });
    } catch {
      showToast('Tidak bisa membuka menu berbagi di perangkat ini.');
    }
  };

  const generate = () => {
    createInvite.mutate(undefined, {
      onSuccess: (result) => share(result.data.url),
      onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal membuat tautan undangan.'),
    });
  };

  return (
    <BottomSheet visible={visible} title="Undang anggota" onClose={onClose}>
      <View style={styles.body}>
        <Text style={styles.intro}>
          Buat tautan undangan, lalu bagikan ke grup WhatsApp. Siapa pun yang membuka tautan akan bergabung sebagai anggota.
        </Text>

        {invites.isPending ? (
          <Skeleton width="100%" height={64} />
        ) : active.length === 0 ? (
          <Text style={styles.empty}>Belum ada tautan undangan aktif.</Text>
        ) : (
          active.map((invite) => (
            <View key={invite.id} style={styles.row}>
              <View style={styles.rowText}>
                <Text style={styles.url} numberOfLines={1}>
                  {invite.url}
                </Text>
                <Text style={styles.meta}>
                  Berlaku sampai {formatDateShort(invite.expiresAt)} · {invite.uses} kali dipakai
                </Text>
              </View>
              <View style={styles.rowActions}>
                <Button variant="secondary" label="Bagikan" onPress={() => share(invite.url)} />
                <Button
                  variant="ghost"
                  label="Cabut"
                  onPress={() =>
                    revokeInvite.mutate(invite.id, {
                      onError: (err) => showToast(err instanceof ApiError ? err.message : 'Gagal mencabut tautan.'),
                    })
                  }
                />
              </View>
            </View>
          ))
        )}

        <Button
          variant="primary"
          label={createInvite.isPending ? 'Membuat…' : 'Buat tautan undangan'}
          onPress={generate}
          block
        />
      </View>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    body: { paddingHorizontal: theme.layout.screenPadding, paddingBottom: theme.space.lg, gap: theme.space.md },
    intro: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textMuted },
    empty: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.textFaint },
    row: { borderWidth: 1, borderColor: theme.color.divider, padding: theme.space.md, gap: theme.space.sm },
    rowText: { gap: 2 },
    url: { fontFamily: 'Archivo_400Regular', fontSize: 13, color: theme.color.text },
    meta: { fontFamily: 'Archivo_400Regular', fontSize: 12, color: theme.color.textMuted },
    rowActions: { flexDirection: 'row', gap: theme.space.sm },
  });
}
