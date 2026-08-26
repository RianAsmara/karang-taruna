import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';

import { apiFetch } from '@/lib/api';

export interface ApiAccount {
  id: string;
  name: string;
  balance: number;
}

export interface ApiTransaction {
  id: string;
  amount: number;
  transactionType: 'INCOME' | 'EXPENSE' | 'TRANSFER';
  transactionTypeLabel: string;
  status: 'DRAFT' | 'PENDING' | 'APPROVED' | 'REJECTED';
  statusLabel: string;
  description: string | null;
  transactionDate: string;
  accountName?: string;
  categoryName?: string | null;
  eventTitle?: string | null;
  creatorName?: string;
  hasEvidence?: boolean;
}

export interface ApiDue {
  id: string;
  memberName?: string;
  userId?: string;
  period: string;
  type: 'MONTHLY' | 'EVENT' | 'SPECIAL' | 'DONATION';
  typeLabel: string;
  amountDue: number;
  amountPaid: number;
  amountOutstanding: number;
  isPaid: boolean;
}

export interface ApiTransparencySummary {
  balance: number;
  monthIncome: number;
  monthExpense: number;
  monthSurplus: number;
  recentTransactions: {
    amount: number;
    transactionType: 'INCOME' | 'EXPENSE' | 'TRANSFER';
    description: string | null;
    transactionDate: string;
  }[];
  publishedReports: { id: string; title: string; periodStart: string; closingBalance: number }[];
}

export interface ApiEvent {
  id: string;
  title: string;
  status: string;
  statusLabel: string;
  location: string | null;
  startAt: string;
  endAt: string | null;
  committeeCount?: number;
  participantCount?: number;
}

export interface ApiAnnouncement {
  id: string;
  title: string;
  body: string;
  publishedAt: string | null;
  authorName?: string;
}

export interface ApiTask {
  id: string;
  title: string;
  status: 'TODO' | 'IN_PROGRESS' | 'BLOCKED' | 'DONE';
  statusLabel: string;
  priority: 'LOW' | 'MEDIUM' | 'HIGH';
  priorityLabel: string;
  dueDate: string | null;
  event?: { id: string; title: string };
  assignee?: { id: string; name: string } | null;
}

export interface ApiCommittee {
  id: string;
  membershipId: string;
  name: string;
  roleTitle: string | null;
}

export interface ApiParticipant {
  id: string;
  membershipId: string;
  name: string;
  status: string;
  statusLabel: string;
}

export interface ApiEventDetail extends ApiEvent {
  description: string | null;
  pic?: { id: string; name: string } | null;
  tasks: ApiTask[];
  committees: ApiCommittee[];
  participants: ApiParticipant[];
}

interface Collection<T> {
  data: T[];
}

export interface ApiOrganization {
  id: string;
  name: string;
  slug: string;
  requireTransactionApproval: boolean;
  publicTransparencyEnabled: boolean;
}

export interface ApiCurrentOrganizationResponse {
  data: ApiOrganization;
  /** The authenticated user's own membership id — not the User id — for matching against assignee/committee/participant ids elsewhere in the API. */
  membership: { id: string; role: string; roleLabel: string; participatedEventsCount: number };
}

export function useCurrentOrganization() {
  return useQuery({
    queryKey: ['organization', 'current'],
    queryFn: () => apiFetch<ApiCurrentOrganizationResponse>('/organizations/current'),
  });
}

export function useAccounts() {
  return useQuery({
    queryKey: ['accounts'],
    queryFn: () => apiFetch<Collection<ApiAccount>>('/finance/accounts'),
  });
}

export function useTransactions(eventId?: string) {
  return useQuery({
    queryKey: ['transactions', eventId ?? null],
    queryFn: () => apiFetch<Collection<ApiTransaction>>(`/finance/transactions${eventId ? `?event_id=${eventId}` : ''}`),
  });
}

export function useDues() {
  return useQuery({
    queryKey: ['dues'],
    queryFn: () => apiFetch<Collection<ApiDue>>('/finance/dues'),
  });
}

export function useTransparency() {
  return useQuery({
    queryKey: ['transparency'],
    queryFn: () => apiFetch<ApiTransparencySummary>('/transparency'),
  });
}

export function useEvents() {
  return useQuery({
    queryKey: ['events'],
    queryFn: () => apiFetch<Collection<ApiEvent>>('/events'),
  });
}

export interface ApiReportDetail {
  id: string;
  title: string;
  reportTypeLabel: string;
  periodStart: string;
  periodEnd: string;
  status: 'DRAFT' | 'PUBLISHED' | 'ARCHIVED';
  statusLabel: string;
  visibilityLabel: string;
  openingBalance: number;
  totalIncome: number;
  totalExpense: number;
  closingBalance: number;
  publishedAt: string | null;
  publisherName: string | null;
  revisionCount: number;
  organizationName: string;
}

export interface ApiReportShowResponse {
  data: ApiReportDetail;
  meta: {
    canPublish: boolean;
    canArchive: boolean;
    canRevise: boolean;
    canDelete: boolean;
    shareUrl: string;
    qrUrl: string;
    pdfUrl: string;
  };
  categoryBreakdown: {
    income: { label: string; amount: number }[];
    expense: { label: string; amount: number }[];
  };
}

export function useReport(id: string) {
  return useQuery({
    queryKey: ['report', id],
    queryFn: () => apiFetch<ApiReportShowResponse>(`/finance/reports/${id}`),
    enabled: Boolean(id),
  });
}

export function useEvent(id: string) {
  return useQuery({
    queryKey: ['event', id],
    queryFn: () => apiFetch<{ data: ApiEventDetail }>(`/events/${id}`),
    enabled: Boolean(id),
  });
}

export function useUpdateTaskStatus(eventId: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: ({ taskId, status }: { taskId: string; status: string }) =>
      apiFetch(`/events/${eventId}/tasks/${taskId}/status`, { method: 'PATCH', body: { status } }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['event', eventId] }),
  });
}

export interface ApiMember {
  id: string;
  name: string;
  email: string;
  role: string;
  roleLabel: string;
  joinedAt: string;
  isOwner: boolean;
}

export interface ApiNotification {
  id: string;
  type: string;
  data: Record<string, string | number | null>;
  readAt: string | null;
  createdAt: string;
}

export function useNotifications() {
  return useQuery({
    queryKey: ['notifications'],
    queryFn: () => apiFetch<Collection<ApiNotification>>('/notifications'),
  });
}

export function useMarkNotificationRead() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (id: string) => apiFetch(`/notifications/${id}/read`, { method: 'POST' }),
    onSuccess: () => queryClient.invalidateQueries({ queryKey: ['notifications'] }),
  });
}

export function useMembers() {
  return useQuery({
    queryKey: ['members'],
    queryFn: () => apiFetch<Collection<ApiMember>>('/members'),
  });
}

export function useAnnouncements() {
  return useQuery({
    queryKey: ['announcements'],
    queryFn: () => apiFetch<Collection<ApiAnnouncement>>('/announcements'),
  });
}

export function useMyTasks() {
  return useQuery({
    queryKey: ['my-tasks'],
    queryFn: () => apiFetch<Collection<ApiTask>>('/my/tasks'),
  });
}
