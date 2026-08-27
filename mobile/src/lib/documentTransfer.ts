import { File, Paths } from 'expo-file-system';
import * as Sharing from 'expo-sharing';

import { authorizedDownloadUrl } from '@/lib/api';

/**
 * Downloads a private document (auth header required, so it can't be a
 * plain <a>/Linking URL) and hands it to the native share sheet — the
 * closest RN equivalent to "save to device" without Storage Access
 * Framework permissions, and what the design calls both "Bagikan" and
 * "Unduh" resolve to on this platform.
 */
export async function downloadAndShareDocument(documentId: string, filename: string): Promise<void> {
  const { url, headers } = authorizedDownloadUrl(`/documents/${documentId}/download`);
  const destination = new File(Paths.cache, filename);
  const task = File.createDownloadTask(url, destination, { headers });
  const file = await task.downloadAsync();

  if (!file) {
    throw new Error('Gagal mengunduh dokumen.');
  }

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(file.uri, { dialogTitle: filename });
  }
}
