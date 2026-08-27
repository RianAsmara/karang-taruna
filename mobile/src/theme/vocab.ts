// Closed status vocabularies from mobile-design-system.md § Status vocabularies.
// Engineers must not add values; designers must not rename them (doc's own rule).
// Centralized here because MemberItem and RoleRow both need the Role table
// unchanged, and screens built later (16 Iuran, 18 Inventaris, 23 Sponsor)
// must not rederive tone per-screen and risk drift.
import type { TagTone } from '@/components/Tag';

export const ROLE_TONE: Record<string, TagTone> = {
  Ketua: 'accent',
  Bendahara: 'solid',
  Sekretaris: 'solid',
  Panitia: 'tint',
  Anggota: 'outline',
};

export type DuesStatus = 'paid' | 'pending' | 'partial' | 'unpaid' | 'exempt';

export const DUES_TAG: Record<DuesStatus, { label: string; tone: TagTone; mutedFill?: boolean }> = {
  paid: { label: 'Sudah bayar', tone: 'solid' },
  pending: { label: 'Menunggu konfirmasi', tone: 'outline' },
  // "Sebagian" — a treasurer already recorded part of the amount; a
  // distinct state from "pending" (member notified, nothing recorded
  // yet) — see mobile-screens.md § 16 edge cases. Stays in the "Belum
  // bayar" filter per the same doc.
  partial: { label: 'Sebagian', tone: 'outline' },
  unpaid: { label: 'Belum bayar', tone: 'tint' },
  exempt: { label: 'Dibebaskan', tone: 'outline', mutedFill: true },
};

/** One due's summary fields → the closed DuesStatus vocabulary above. Centralized so screens 12/13/16 never re-derive this and risk drift. */
export function resolveDuesStatus(due: {
  isPaid: boolean;
  isExempt?: boolean;
  isAwaitingConfirmation?: boolean;
  isPartiallyPaid?: boolean;
}): DuesStatus {
  if (due.isExempt) return 'exempt';
  if (due.isPaid) return 'paid';
  if (due.isPartiallyPaid) return 'partial';
  if (due.isAwaitingConfirmation) return 'pending';
  return 'unpaid';
}

export type InventoryAvailability = 'available' | 'borrowed' | 'unavailable';

export const INVENTORY_AVAILABILITY_TAG: Record<InventoryAvailability, { label: string; tone: TagTone; mutedFill?: boolean }> = {
  available: { label: 'Tersedia', tone: 'solid' },
  borrowed: { label: 'Dipinjam', tone: 'tint' },
  unavailable: { label: 'Tidak tersedia', tone: 'outline', mutedFill: true },
};

export type SponsorStatus = 'proposed' | 'agreed' | 'received' | 'cancelled';

export const SPONSOR_STATUS_TAG: Record<SponsorStatus, { label: string; tone: TagTone; mutedFill?: boolean }> = {
  proposed: { label: 'Diajukan', tone: 'outline' },
  agreed: { label: 'Setuju', tone: 'tint' },
  received: { label: 'Diterima', tone: 'solid' },
  cancelled: { label: 'Batal', tone: 'outline', mutedFill: true },
};

/** Item quantity 0 (fully out on loan) → 'borrowed'; RUSAK → 'unavailable' regardless of quantity (screen 18 edge case: damaged items excluded from available counts). */
export function resolveInventoryAvailability(item: { availableQuantity: number; condition: string }): InventoryAvailability {
  if (item.condition === 'RUSAK') return 'unavailable';
  return item.availableQuantity > 0 ? 'available' : 'borrowed';
}

const SPONSOR_STATUS_FROM_API: Record<string, SponsorStatus> = {
  DIAJUKAN: 'proposed',
  SETUJU: 'agreed',
  DITERIMA: 'received',
  BATAL: 'cancelled',
};

export function resolveSponsorStatus(status: string): SponsorStatus {
  return SPONSOR_STATUS_FROM_API[status] ?? 'proposed';
}
